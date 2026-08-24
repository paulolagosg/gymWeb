<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Diagnóstico de una instalación de gymWeb.
 *
 *   php artisan app:doctor
 *   php artisan app:doctor --mail=tu@correo.cl   (además envía un correo de prueba)
 *
 * Correlo ANTES y DESPUÉS de cada despliegue, y en toda instalación nueva.
 * Devuelve código de salida 1 si hay algún FALLA, para poder encadenarlo en un script.
 *
 * Para que la verificación del cron funcione, agrega esto en routes/console.php:
 *
 *   Schedule::call(fn () => @touch(storage_path('app/scheduler-heartbeat')))
 *       ->everyMinute()
 *       ->name('scheduler-heartbeat');
 */
class AppDoctor extends Command
{
    protected $signature = 'app:doctor {--mail= : Dirección a la que enviar un correo de prueba}';

    protected $description = 'Verifica entorno, esquema, tareas programadas, correo y salud de datos';

    private int $fallas = 0;
    private int $avisos = 0;

    /**
     * Columnas que el código actual da por existentes.
     * Cada desajuste acá es un HTTP 500 esperando a ocurrir.
     */
    private const ESQUEMA_CRITICO = [
        'gimnasios'                     => ['features'],
        'clientes'                      => ['id_gimnasio', 'estado', 'fecha_baja'],
        'users'                         => ['id_gimnasio', 'id_tipo_usuario'],
        'agendas'                       => ['id_gimnasio', 'estado', 'fecha_inicio'],
        'agendas_ejercicios'            => ['orden'],
        'cuentas_corrientes'            => ['id_cliente', 'fecha_vencimiento', 'monto_pagado', 'saldo', 'fecha_pago', 'id_estado_pago', 'fecha_ultimo_abono'],
        'movimientos_financieros'       => ['id_gimnasio'],
        'puntos_clientes'               => [],
        'evaluaciones_iniciales'        => ['id_cliente', 'completada_en'],
        'evaluacion_inicial_secciones'  => ['id_gimnasio'],
        'evaluacion_inicial_preguntas'  => ['id_gimnasio', 'es_sensible', 'depende_pregunta_id', 'depende_opcion_id'],
        'evaluacion_inicial_opciones'   => ['id_gimnasio', 'genera_alerta'],
        'evaluacion_inicial_respuestas' => ['id_gimnasio'],
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=cyan;options=bold>app:doctor</> — ' . config('app.name') . ' @ ' . gethostname());

        $this->entorno();
        $this->baseDeDatos();
        $this->esquema();
        $this->programador();
        $this->correo();
        $this->saludDeDatos();

        return $this->resumen();
    }

    // =================================================================
    // Bloques de verificación
    // =================================================================

    private function entorno(): void
    {
        $this->seccion('Entorno');

        $this->check(
            'Versión de PHP',
            version_compare(PHP_VERSION, '8.2', '>='),
            PHP_VERSION,
            'El proyecto espera PHP 8.2 o superior'
        );

        $env = config('app.env');
        $this->check('APP_ENV', $env === 'production', $env, "En el servidor debería ser 'production'", aviso: true);

        $this->check(
            'APP_DEBUG apagado',
            config('app.debug') === false,
            config('app.debug') ? 'true' : 'false',
            'Con APP_DEBUG=true expones rutas, consultas y variables de entorno en cada error'
        );

        $url = (string) config('app.url');
        $urlOk = $url !== ''
            && ! str_contains($url, 'localhost')
            && ! str_contains($url, '127.0.0.1');

        $this->check(
            'APP_URL apunta al dominio público',
            $urlOk,
            $url ?: '(vacío)',
            'Sin esto el enlace de recuperación de contraseña no es alcanzable'
        );

        $this->check(
            'APP_URL usa https',
            str_starts_with($url, 'https://'),
            $url ?: '(vacío)',
            'Los clientes de correo y los deep links esperan https',
            aviso: true
        );

        $esquema = env('APP_MOBILE_URL_SCHEME');
        $this->check(
            'APP_MOBILE_URL_SCHEME definido',
            ! empty($esquema),
            $esquema ?: '(no definido)',
            'Es el esquema nativo al que redirige la página intermedia de reset'
        );

        $this->check(
            'storage/ escribible',
            is_writable(storage_path()),
            storage_path(),
            'Sin esto fallan logs, PDFs y caché'
        );

        $this->check(
            'Enlace público de storage',
            file_exists(public_path('storage')),
            public_path('storage'),
            'Las fotos de perfil y comprobantes no se verán',
            aviso: true
        );
    }

    private function baseDeDatos(): void
    {
        $this->seccion('Base de datos');

        try {
            DB::connection()->getPdo();
            $this->ok('Conexión', config('database.connections.' . config('database.default') . '.database'));
        } catch (Throwable $e) {
            $this->falla('Conexión', 'no se pudo conectar', $e->getMessage());

            return;
        }

        try {
            $migrator = app('migrator');
            $migrator->setConnection(config('database.default'));

            if (! $migrator->repositoryExists()) {
                $this->falla('Migraciones', 'sin tabla de migraciones', 'Corre php artisan migrate');

                return;
            }

            $archivos  = $migrator->getMigrationFiles($migrator->paths() ?: [database_path('migrations')]);
            $corridas  = $migrator->getRepository()->getRan();
            $pendientes = array_diff(array_keys($archivos), $corridas);

            $this->check(
                'Migraciones al día',
                $pendientes === [],
                $pendientes === [] ? count($corridas) . ' aplicadas' : count($pendientes) . ' pendientes',
                'Corre php artisan migrate. Pendientes: ' . implode(', ', array_slice($pendientes, 0, 5))
            );
        } catch (Throwable $e) {
            $this->aviso('Migraciones', 'no verificable', $e->getMessage());
        }
    }

    private function esquema(): void
    {
        $this->seccion('Esquema crítico');

        $problemas = 0;

        foreach (self::ESQUEMA_CRITICO as $tabla => $columnas) {
            if (! Schema::hasTable($tabla)) {
                $this->falla("Tabla {$tabla}", 'no existe', 'Falta correr una migración');
                $problemas++;

                continue;
            }

            $faltantes = array_values(array_filter(
                $columnas,
                fn($col) => ! Schema::hasColumn($tabla, $col)
            ));

            if ($faltantes !== []) {
                $this->falla(
                    "Tabla {$tabla}",
                    'faltan columnas: ' . implode(', ', $faltantes),
                    'El código las usa; cualquier consulta que las toque devuelve HTTP 500'
                );
                $problemas++;
            }
        }

        if ($problemas === 0) {
            $this->ok('Esquema', count(self::ESQUEMA_CRITICO) . ' tablas verificadas');
        }
    }

    private function programador(): void
    {
        $this->seccion('Tareas programadas');

        $latido = storage_path('app/scheduler-heartbeat');

        if (! file_exists($latido)) {
            $this->falla(
                'Cron del servidor',
                'sin señal',
                "Falta '* * * * * php artisan schedule:run' en el cron, o falta registrar el latido en routes/console.php. Sin cron los recordatorios de morosidad NO se envían solos."
            );
        } else {
            $minutos = (int) round((time() - filemtime($latido)) / 60);

            $this->check(
                'Cron del servidor',
                $minutos <= 5,
                "último latido hace {$minutos} min",
                "El cron dejó de correr hace {$minutos} minutos"
            );
        }

        foreach (['recordatorios:proximas', 'recordatorios:vencidas'] as $comando) {
            $this->check(
                "Comando {$comando}",
                array_key_exists($comando, $this->getApplication()->all()),
                array_key_exists($comando, $this->getApplication()->all()) ? 'registrado' : 'no existe',
                'Revisa que el archivo esté subido y que no haya nombres de comando duplicados'
            );
        }
    }

    private function correo(): void
    {
        $this->seccion('Correo');

        $mailer = config('mail.default');
        $this->check('Driver', $mailer !== 'log' && $mailer !== 'array', $mailer, "Con '{$mailer}' no sale ningún correo real");

        $desde = config('mail.from.address');
        $this->check('Remitente configurado', ! empty($desde), $desde ?: '(vacío)', 'Sin remitente los correos se rechazan');

        if (! $destino = $this->option('mail')) {
            return;
        }

        try {
            Mail::raw(
                'Correo de prueba de app:doctor — ' . now()->toDateTimeString() . ' — ' . config('app.url'),
                fn($m) => $m->to($destino)->subject('[app:doctor] Prueba de correo')
            );
            $this->ok('Envío de prueba', "enviado a {$destino}");
        } catch (Throwable $e) {
            $this->falla('Envío de prueba', 'falló', $e->getMessage());
        }
    }

    private function saludDeDatos(): void
    {
        $this->seccion('Salud de datos');

        // El dato del que cuelga racha, ejercicios, progreso y calorías.
        $this->contar(
            'Sesiones marcadas como Realizada (estado=4)',
            fn() => DB::table('agendas')->where('estado', 4)->count(),
            fn($n) => $n > 0,
            'Ninguna sesión marcada: racha, "Mis ejercicios", evolución de carga y calorías salen vacíos para TODOS los clientes'
        );

        $this->contar(
            'Estado de pago "Parcial" en el catálogo',
            fn() => DB::table('estados_pagos')->where('nombre', 'like', '%arcial%')->count(),
            fn($n) => $n > 0,
            'Falta la migración del Bloque 16; el pago parcial no puede cerrar estado'
        );

        $this->contar(
            'Secciones del cuestionario de evaluación inicial',
            fn() => DB::table('evaluacion_inicial_secciones')->where('estado', 1)->count(),
            fn($n) => $n >= 11,
            'Corre php artisan db:seed --class=EvaluacionInicialSeeder'
        );

        $this->contar(
            'Cuotas con saldo sin calcular',
            fn() => DB::table('cuentas_corrientes')->whereNull('saldo')->count(),
            fn($n) => $n === 0,
            'Falta el backfill de saldo; morosos y aging van a mostrar montos equivocados'
        );

        $this->contar(
            'Clientes activos sin gimnasio asignado',
            fn() => DB::table('clientes')->where('estado', 1)->where(fn($q) => $q->whereNull('id_gimnasio')->orWhere('id_gimnasio', 0))->count(),
            fn($n) => $n === 0,
            'Quedan invisibles para su admin y son un riesgo de fuga entre tenants'
        );

        $this->contar(
            'Usuarios no super-admin sin gimnasio',
            fn() => DB::table('users')->where('id_tipo_usuario', '!=', 10)->where(fn($q) => $q->whereNull('id_gimnasio')->orWhere('id_gimnasio', 0))->count(),
            fn($n) => $n === 0,
            'No van a poder ver datos, o peor, van a ver los de otro gimnasio'
        );

        $this->contar(
            'Respuestas de evaluación sin gimnasio real',
            fn() => DB::table('evaluacion_inicial_respuestas')->where(fn($q) => $q->whereNull('id_gimnasio')->orWhere('id_gimnasio', 0))->count(),
            fn($n) => $n === 0,
            'Acá id_gimnasio es tenant real, no centinela de catálogo (Bloque 10)'
        );

        $this->contar(
            'Gimnasios sin feature flags configurados',
            fn() => DB::table('gimnasios')->where(fn($q) => $q->whereNull('features')->orWhere('features', ''))->count(),
            fn($n) => $n === 0,
            'Nacen con los 11 módulos apagados: actívalos desde Gimnasios → editar → Funcionalidades',
            aviso: true
        );
    }

    // =================================================================
    // Presentación
    // =================================================================

    private function seccion(string $titulo): void
    {
        $this->newLine();
        $this->line("  <options=bold>{$titulo}</>");
    }

    private function check(string $nombre, bool $ok, string $valor, string $ayuda, bool $aviso = false): void
    {
        if ($ok) {
            $this->ok($nombre, $valor);
        } elseif ($aviso) {
            $this->aviso($nombre, $valor, $ayuda);
        } else {
            $this->falla($nombre, $valor, $ayuda);
        }
    }

    /** @param callable():int $consulta */
    private function contar(string $nombre, callable $consulta, callable $esperado, string $ayuda, bool $aviso = false): void
    {
        try {
            $n = $consulta();
        } catch (Throwable $e) {
            $this->aviso($nombre, 'no verificable', $e->getMessage());

            return;
        }

        $this->check($nombre, $esperado($n), (string) $n, $ayuda, $aviso);
    }

    private function ok(string $nombre, string $valor): void
    {
        $this->line("    <fg=green>✔</> {$nombre}: <fg=gray>{$valor}</>");
    }

    private function aviso(string $nombre, string $valor, string $ayuda): void
    {
        $this->avisos++;
        $this->line("    <fg=yellow>▲</> {$nombre}: {$valor}");
        $this->line("      <fg=gray>{$ayuda}</>");
    }

    private function falla(string $nombre, string $valor, string $ayuda): void
    {
        $this->fallas++;
        $this->line("    <fg=red>✘</> {$nombre}: {$valor}");
        $this->line("      <fg=gray>{$ayuda}</>");
    }

    private function resumen(): int
    {
        $this->newLine();

        if ($this->fallas === 0 && $this->avisos === 0) {
            $this->line('  <fg=black;bg=green> TODO EN ORDEN </>');
            $this->newLine();

            return self::SUCCESS;
        }

        if ($this->fallas === 0) {
            $this->line("  <fg=black;bg=yellow> {$this->avisos} aviso(s) </> — nada bloqueante");
            $this->newLine();

            return self::SUCCESS;
        }

        $this->line("  <fg=white;bg=red> {$this->fallas} falla(s) </> y {$this->avisos} aviso(s)");
        $this->newLine();

        return self::FAILURE;
    }
}
