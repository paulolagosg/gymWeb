<?php

namespace Database\Seeders;

use App\Models\Gimnasios;
use App\Models\Survey;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConsistentTestDataSeeder extends Seeder
{
    private array $columnsCache = [];

    private string $defaultPassword = '12345678';

    public function run(): void
    {
        $this->seedReferenceCatalogs();

        Survey::firstOrCreate(
            ['title' => 'Encuesta de Satisfacción del Gimnasio'],
            ['is_active' => true]
        );

        $gyms = $this->ensureGyms();
        $exerciseIds = $this->ensureExerciseCatalog();

        foreach ($gyms as $gymIndex => $gym) {
            $plans = $this->seedPlansForGym($gym, $gymIndex + 1);
            $trainers = $this->seedTrainersForGym($gym, $gymIndex + 1);

            for ($clientIndex = 1; $clientIndex <= 10; $clientIndex++) {
                $plan = $plans[($clientIndex - 1) % count($plans)];
                $trainer = $trainers[($clientIndex - 1) % count($trainers)];

                $client = $this->seedClientForGym($gym, $plan, $trainer, $gymIndex + 1, $clientIndex);

                $this->seedClientUser($client, $gym, $gymIndex + 1, $clientIndex);
                $this->seedPaidFeesForClient($client, $plan, $clientIndex);
                $this->seedTasksForClient($client, $trainer, $clientIndex);
                $this->seedCoursesForClientContext($client, $trainer, $clientIndex);
                $this->seedAgendasForClient($client, $trainer, $gym, $exerciseIds, $clientIndex);
            }
        }

        $this->seedFutureAgendasForAllClients($exerciseIds);
        $this->seedPastAgendasForAllClients($exerciseIds);
        $this->ensureSupportUser($gyms[0]);

        if ($this->command) {
            $this->command->info('Datos de prueba consistentes generados correctamente.');
            $this->command->line('Credencial común para clientes y entrenadores: 12345678');
        }
    }

    private function seedReferenceCatalogs(): void
    {
        if (Schema::hasTable('tipos_usuarios')) {
            foreach (
                [
                    ['id' => 1, 'nombre' => 'Administrador', 'slug' => 'administrador', 'descripcion' => 'Admin del gimnasio', 'estado' => 1],
                    ['id' => 2, 'nombre' => 'Entrenador', 'slug' => 'entrenador', 'descripcion' => 'Entrenador personal', 'estado' => 1],
                    ['id' => 3, 'nombre' => 'Recepcionista', 'slug' => 'recepcionista', 'descripcion' => 'Apoyo operativo', 'estado' => 1],
                    ['id' => 4, 'nombre' => 'Cliente', 'slug' => 'cliente', 'descripcion' => 'Cliente del gimnasio', 'estado' => 1],
                    ['id' => 5, 'nombre' => 'Open Gym', 'slug' => 'open-gym', 'descripcion' => 'Cliente autoguiado', 'estado' => 1],
                    ['id' => 10, 'nombre' => 'Superadmin', 'slug' => 'superadmin', 'descripcion' => 'Administrador global', 'estado' => 1],
                ] as $role
            ) {
                $this->upsertRow('tipos_usuarios', ['id' => $role['id']], $role);
            }
        }

        if (Schema::hasTable('generos')) {
            foreach (
                [
                    ['nombre' => 'Femenino', 'slug' => 'femenino', 'estado' => 1],
                    ['nombre' => 'Masculino', 'slug' => 'masculino', 'estado' => 1],
                    ['nombre' => 'No binario', 'slug' => 'no-binario', 'estado' => 1],
                ] as $gender
            ) {
                $this->syncCatalogRow('generos', $gender);
            }
        }

        if (Schema::hasTable('motivos')) {
            foreach (
                [
                    ['nombre' => 'Salud y bienestar', 'slug' => 'salud-bienestar', 'tipo' => 1, 'estado' => 1],
                    ['nombre' => 'Rendimiento deportivo', 'slug' => 'rendimiento-deportivo', 'tipo' => 1, 'estado' => 1],
                    ['nombre' => 'Falta de tiempo', 'slug' => 'falta-de-tiempo', 'tipo' => 2, 'estado' => 1],
                    ['nombre' => 'Cambio de domicilio', 'slug' => 'cambio-de-domicilio', 'tipo' => 2, 'estado' => 1],
                ] as $reason
            ) {
                $this->syncCatalogRow('motivos', $reason);
            }
        }

        if (Schema::hasTable('estados_pagos')) {
            foreach (
                [
                    ['nombre' => 'Pendiente', 'slug' => 'pendiente', 'color' => '#f59e0b', 'icono' => 'clock'],
                    ['nombre' => 'Pagado', 'slug' => 'pagado', 'color' => '#10b981', 'icono' => 'check'],
                    ['nombre' => 'Vencido', 'slug' => 'vencido', 'color' => '#ef4444', 'icono' => 'alert'],
                ] as $status
            ) {
                $this->syncCatalogRow('estados_pagos', $status);
            }
        }

        if (Schema::hasTable('formas_pagos')) {
            foreach (
                [
                    ['nombre' => 'Efectivo', 'slug' => 'efectivo', 'icono' => 'money-bill', 'color' => '#22c55e', 'estado' => 1],
                    ['nombre' => 'Transferencia', 'slug' => 'transferencia', 'icono' => 'building-columns', 'color' => '#3b82f6', 'estado' => 1],
                    ['nombre' => 'Tarjeta', 'slug' => 'tarjeta', 'icono' => 'credit-card', 'color' => '#8b5cf6', 'estado' => 1],
                ] as $paymentMethod
            ) {
                $this->syncCatalogRow('formas_pagos', $paymentMethod);
            }
        }

        if (Schema::hasTable('tipos_cuotas')) {
            foreach (
                [
                    ['nombre' => 'Activación', 'slug' => 'activacion', 'estado' => 1],
                    ['nombre' => 'Mensualidad', 'slug' => 'mensualidad', 'estado' => 1],
                    ['nombre' => 'Evaluación', 'slug' => 'evaluacion', 'estado' => 1],
                ] as $feeType
            ) {
                $this->syncCatalogRow('tipos_cuotas', $feeType);
            }
        }
    }

    private function ensureGyms(): array
    {
        $templates = [
            [
                'slug' => 'gimnasio-ampaya',
                'nombre' => 'Gimnasio Ampaya',
                'direccion' => 'Av. Principal 123',
                'descripcion' => 'Casa matriz con foco en fuerza y evaluación integral.',
                'telefono' => '+56 9 1111 1111',
                'correo_electronico' => 'ampaya@gym.local',
                'sitio_web' => 'https://ampaya.local',
                'instagram' => '@ampaya.gym',
                'facebook' => 'ampaya.gym',
                'tiktok' => '@ampaya.gym',
                'estado' => 1,
            ],
            [
                'slug' => 'gimnasio-centro',
                'nombre' => 'Gimnasio Centro',
                'direccion' => 'Calle Comercio 456',
                'descripcion' => 'Sucursal urbana orientada a acondicionamiento general.',
                'telefono' => '+56 9 2222 2222',
                'correo_electronico' => 'centro@gym.local',
                'sitio_web' => 'https://centro.gym.local',
                'instagram' => '@centro.gym',
                'facebook' => 'centro.gym',
                'tiktok' => '@centro.gym',
                'estado' => 1,
            ],
            [
                'slug' => 'gimnasio-norte',
                'nombre' => 'Gimnasio Norte',
                'direccion' => 'Ruta Norte 789',
                'descripcion' => 'Sucursal enfocada en movilidad, cardio y clases funcionales.',
                'telefono' => '+56 9 3333 3333',
                'correo_electronico' => 'norte@gym.local',
                'sitio_web' => 'https://norte.gym.local',
                'instagram' => '@norte.gym',
                'facebook' => 'norte.gym',
                'tiktok' => '@norte.gym',
                'estado' => 1,
            ],
        ];

        foreach ($templates as $template) {
            if (Gimnasios::query()->count() >= 3 && Gimnasios::query()->where('slug', $template['slug'])->doesntExist()) {
                continue;
            }

            Gimnasios::firstOrCreate(['slug' => $template['slug']], $template);
        }

        $gyms = Gimnasios::query()->orderBy('id')->take(3)->get()->all();

        if (count($gyms) < 3) {
            foreach ($templates as $template) {
                $gym = Gimnasios::firstOrCreate(['slug' => $template['slug']], $template);
                $gyms = Gimnasios::query()->orderBy('id')->take(3)->get()->all();
                if (count($gyms) >= 3) {
                    break;
                }
            }
        }

        return $gyms;
    }

    private function ensureExerciseCatalog(): array
    {
        if (Schema::hasTable('tipos_ejercicios')) {
            foreach (
                [
                    ['nombre' => 'Fuerza', 'icono' => 'dumbbell'],
                    ['nombre' => 'Cardio', 'icono' => 'heart-pulse'],
                    ['nombre' => 'Piernas', 'icono' => 'person-running'],
                    ['nombre' => 'Movilidad', 'icono' => 'arrows-up-down-left-right'],
                ] as $type
            ) {
                $this->upsertRow('tipos_ejercicios', ['nombre' => $type['nombre']], $type);
            }
        }

        $typeIds = [
            'Fuerza' => (int) DB::table('tipos_ejercicios')->where('nombre', 'Fuerza')->value('id'),
            'Cardio' => (int) DB::table('tipos_ejercicios')->where('nombre', 'Cardio')->value('id'),
            'Piernas' => (int) DB::table('tipos_ejercicios')->where('nombre', 'Piernas')->value('id'),
            'Movilidad' => (int) DB::table('tipos_ejercicios')->where('nombre', 'Movilidad')->value('id'),
        ];

        $exerciseSeeds = [
            ['nombre' => 'Sentadilla goblet', 'tipo' => 'Piernas', 'descripcion' => 'Trabajo de tren inferior y core.'],
            ['nombre' => 'Peso muerto rumano', 'tipo' => 'Piernas', 'descripcion' => 'Fortalece cadena posterior.'],
            ['nombre' => 'Press de pecho con mancuernas', 'tipo' => 'Fuerza', 'descripcion' => 'Empuje horizontal controlado.'],
            ['nombre' => 'Remo sentado', 'tipo' => 'Fuerza', 'descripcion' => 'Fortalecimiento dorsal y escapular.'],
            ['nombre' => 'Plancha frontal', 'tipo' => 'Movilidad', 'descripcion' => 'Estabilidad del core.'],
            ['nombre' => 'Zancadas caminando', 'tipo' => 'Piernas', 'descripcion' => 'Trabajo unilateral y equilibrio.'],
            ['nombre' => 'Bicicleta estática', 'tipo' => 'Cardio', 'descripcion' => 'Resistencia cardiovascular.'],
            ['nombre' => 'Caminata inclinada', 'tipo' => 'Cardio', 'descripcion' => 'Cardio de intensidad moderada.'],
            ['nombre' => 'Movilidad de hombros', 'tipo' => 'Movilidad', 'descripcion' => 'Activación y rango de movimiento.'],
            ['nombre' => 'Face pull con banda', 'tipo' => 'Fuerza', 'descripcion' => 'Estabilidad escapular.'],
        ];

        foreach ($exerciseSeeds as $exercise) {
            $slug = Str::slug($exercise['nombre']);

            $this->upsertRow('ejercicios', ['slug' => $slug], [
                'nombre' => $exercise['nombre'],
                'icono' => null,
                'id_tipo' => $typeIds[$exercise['tipo']] ?: null,
                'slug' => $slug,
                'descripcion' => $exercise['descripcion'],
                'estado' => 1,
            ]);
        }

        return DB::table('ejercicios')
            ->where('estado', 1)
            ->orderBy('id')
            ->limit(10)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function seedPlansForGym(object $gym, int $gymNumber): array
    {
        $definitions = [
            ['nombre' => "Plan Inicio G{$gymNumber}", 'descripcion' => 'Acceso general y evaluación inicial.', 'valor' => 24990, 'porcentaje' => 0],
            ['nombre' => "Plan Progreso G{$gymNumber}", 'descripcion' => 'Seguimiento mensual y rutina base.', 'valor' => 31990, 'porcentaje' => 5],
            ['nombre' => "Plan Fuerza G{$gymNumber}", 'descripcion' => 'Entrenamiento enfocado en fuerza.', 'valor' => 38990, 'porcentaje' => 10],
            ['nombre' => "Plan Funcional G{$gymNumber}", 'descripcion' => 'Trabajo metabólico y movilidad.', 'valor' => 35990, 'porcentaje' => 8],
            ['nombre' => "Plan Premium G{$gymNumber}", 'descripcion' => 'Atención integral con control avanzado.', 'valor' => 45990, 'porcentaje' => 12],
        ];

        $plans = [];

        foreach ($definitions as $index => $definition) {
            $slug = Str::slug("{$gym->slug}-plan-" . ($index + 1));
            $plans[] = $this->upsertRow('planes', ['slug' => $slug], [
                'nombre' => $definition['nombre'],
                'descripcion' => $definition['descripcion'],
                'valor' => $definition['valor'],
                'porcentaje' => $definition['porcentaje'],
                'slug' => $slug,
                'estado' => 1,
                'id_gimnasio' => $gym->id,
            ]);
        }

        return $plans;
    }

    private function seedTrainersForGym(object $gym, int $gymNumber): array
    {
        $blueprints = [
            ['name' => 'Camila Torres', 'titulo' => 'Coach de fuerza'],
            ['name' => 'Sebastián Muñoz', 'titulo' => 'Preparador físico'],
            ['name' => 'Fernanda Silva', 'titulo' => 'Coach funcional'],
            ['name' => 'Matías Riquelme', 'titulo' => 'Especialista en recomposición'],
            ['name' => 'Josefa Pérez', 'titulo' => 'Coach de movilidad'],
            ['name' => 'Nicolás Herrera', 'titulo' => 'Entrenador de resistencia'],
        ];

        $trainers = [];

        foreach ($blueprints as $index => $blueprint) {
            $email = sprintf('entrenador.g%d.%d@gym.local', $gymNumber, $index + 1);
            $slug = sprintf('entrenador-g%d-%d', $gymNumber, $index + 1);

            $trainers[] = $this->upsertRow('users', ['email' => $email], [
                'name' => $blueprint['name'] . " - {$gym->nombre}",
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $this->passwordHash(),
                'remember_token' => Str::random(10),
                'id_tipo_usuario' => 2,
                'id_cliente' => null,
                'id_gimnasio' => $gym->id,
                'slug' => $slug,
                'titulo' => $blueprint['titulo'],
                'porcentaje' => 35 + (($index + 1) * 5),
                'individual' => 18000 + ($index * 1500),
                'duo' => 12000 + ($index * 1000),
                'id_clasificacion' => (($index % 3) + 1),
            ]);
        }

        return $trainers;
    }

    private function seedClientForGym(object $gym, object $plan, object $trainer, int $gymNumber, int $clientIndex): object
    {
        $profiles = [
            ['nombres' => 'Valentina', 'paterno' => 'Rojas', 'materno' => 'Soto', 'genero' => 'femenino', 'edad' => 19, 'perfil' => 'Quiere mejorar resistencia y hábitos.'],
            ['nombres' => 'Martín', 'paterno' => 'González', 'materno' => 'Vera', 'genero' => 'masculino', 'edad' => 24, 'perfil' => 'Busca aumentar masa muscular.'],
            ['nombres' => 'Antonia', 'paterno' => 'López', 'materno' => 'Díaz', 'genero' => 'femenino', 'edad' => 28, 'perfil' => 'Quiere tonificación y movilidad.'],
            ['nombres' => 'Benjamín', 'paterno' => 'Martínez', 'materno' => 'Castro', 'genero' => 'masculino', 'edad' => 33, 'perfil' => 'Prioriza fuerza y postura.'],
            ['nombres' => 'Isidora', 'paterno' => 'Fuentes', 'materno' => 'Reyes', 'genero' => 'femenino', 'edad' => 37, 'perfil' => 'Desea constancia y bajar estrés.'],
            ['nombres' => 'Tomás', 'paterno' => 'Araya', 'materno' => 'Morales', 'genero' => 'masculino', 'edad' => 41, 'perfil' => 'Enfocado en salud metabólica.'],
            ['nombres' => 'Catalina', 'paterno' => 'Navarro', 'materno' => 'Pizarro', 'genero' => 'femenino', 'edad' => 46, 'perfil' => 'Objetivo: fuerza funcional y energía.'],
            ['nombres' => 'Joaquín', 'paterno' => 'Sanhueza', 'materno' => 'Campos', 'genero' => 'masculino', 'edad' => 52, 'perfil' => 'Requiere progresión segura y guiada.'],
            ['nombres' => 'Amelia', 'paterno' => 'Contreras', 'materno' => 'Peña', 'genero' => 'femenino', 'edad' => 58, 'perfil' => 'Entrena por movilidad y calidad de vida.'],
            ['nombres' => 'Gaspar', 'paterno' => 'Jara', 'materno' => 'León', 'genero' => 'masculino', 'edad' => 64, 'perfil' => 'Busca mantenerse activo y fuerte.'],
        ];

        $profile = $profiles[$clientIndex - 1];
        $genderId = (int) DB::table('generos')->where('slug', $profile['genero'])->value('id');
        $motivoIngresoId = (int) DB::table('motivos')->where('tipo', 1)->orderBy('id')->value('id');
        $fechaIngreso = Carbon::today()->subMonths(2 + (($clientIndex + $gymNumber) % 6));
        $fechaPago = $fechaIngreso->copy()->addDays(7 + $clientIndex);
        $fechaNacimiento = Carbon::today()->subYears($profile['edad'])->subDays($clientIndex * 11);
        $email = sprintf('cliente.g%d.%d@gym.local', $gymNumber, $clientIndex);
        $ci = (string) (10000000 + ($gymNumber * 100) + $clientIndex);

        return $this->upsertRow('clientes', ['ci' => $ci], [
            'ci' => $ci,
            'nombres' => $profile['nombres'],
            'paterno' => $profile['paterno'],
            'materno' => $profile['materno'],
            'telefono' => '+56 9 ' . str_pad((string) (70000000 + ($gymNumber * 100) + $clientIndex), 8, '0', STR_PAD_LEFT),
            'id_genero' => $genderId ?: 1,
            'altura' => 1.55 + (($clientIndex % 6) * 0.05),
            'email' => $email,
            'direccion' => "Dirección {$clientIndex}, {$gym->nombre}",
            'ciudad' => 'Santiago',
            'fecha_nacimiento' => $fechaNacimiento->toDateString(),
            'fecha_ingreso' => $fechaIngreso->toDateString(),
            'fecha_pago' => $fechaPago->toDateString(),
            'fecha_fin' => $fechaIngreso->copy()->addYear()->toDateString(),
            'slug' => sprintf('cliente-g%d-%d', $gymNumber, $clientIndex),
            'id_plan' => $plan->id,
            'id_usuario' => $trainer->id,
            'estado' => 1,
            'id_motivo_ingreso' => $motivoIngresoId ?: null,
            'otro_ingreso' => null,
            'perfil' => $profile['perfil'],
            'id_gimnasio' => $gym->id,
        ]);
    }

    private function seedClientUser(object $client, object $gym, int $gymNumber, int $clientIndex): void
    {
        $email = sprintf('cliente.g%d.%d@gym.local', $gymNumber, $clientIndex);

        $this->upsertRow('users', ['email' => $email], [
            'name' => trim($client->nombres . ' ' . $client->paterno . ' ' . ($client->materno ?? '')),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $this->passwordHash(),
            'remember_token' => Str::random(10),
            'id_tipo_usuario' => 4,
            'id_cliente' => $client->id,
            'id_gimnasio' => $gym->id,
            'slug' => sprintf('usuario-cliente-g%d-%d', $gymNumber, $clientIndex),
            'porcentaje' => 0,
        ]);
    }

    private function seedPaidFeesForClient(object $client, object $plan, int $clientIndex): void
    {
        $paidFees = 2 + ($clientIndex % 2);
        $planValue = (int) ($plan->valor ?? 30000);
        $paidStatusId = (int) (DB::table('estados_pagos')->where('slug', 'pagado')->value('id')
            ?: DB::table('estados_pagos')->where('nombre', 'Pagado')->value('id'));
        $feeTypeId = (int) (DB::table('tipos_cuotas')->where('slug', 'mensualidad')->value('id')
            ?: DB::table('tipos_cuotas')->where('nombre', 'Mensualidad')->value('id'));
        $paymentMethodIds = DB::table('formas_pagos')->orderBy('id')->pluck('id')->map(fn($id) => (int) $id)->all();

        for ($i = 1; $i <= $paidFees; $i++) {
            $dueDate = Carbon::today()->subMonths($paidFees - $i + 1)->day(min(5 + $clientIndex, 28));
            $discount = $i === 3 ? 3000 : (($clientIndex % 3) * 1000);
            $amountToPay = max($planValue - $discount, 0);
            $paymentMethodId = $paymentMethodIds[($i - 1) % max(count($paymentMethodIds), 1)] ?? null;

            $this->upsertRow('cuentas_corrientes', [
                'id_cliente' => $client->id,
                'fecha_vencimiento' => $dueDate->toDateString(),
            ], [
                'id_cliente' => $client->id,
                'fecha_vencimiento' => $dueDate->toDateString(),
                'monto' => $planValue,
                'descuento' => $discount,
                'monto_pagar' => $amountToPay,
                'id_estado_pago' => $paidStatusId ?: null,
                'id_forma_pago' => $paymentMethodId,
                'fecha_pago' => $dueDate->copy()->subDays(1)->toDateString(),
                'monto_pagado' => $amountToPay,
                'saldo' => 0,
                'id_tipo_cuota' => $feeTypeId ?: null,
            ]);
        }
    }

    private function seedTasksForClient(object $client, object $trainer, int $clientIndex): void
    {
        $taskCount = 2 + ($clientIndex % 2);

        for ($i = 1; $i <= $taskCount; $i++) {
            $slug = sprintf('tarea-cliente-%d-%d', $client->id, $i);

            $this->upsertRow('tareas', ['slug' => $slug], [
                'nombre' => "Seguimiento {$i} de {$client->nombres}",
                'descripcion' => "Revisión de avances, adherencia y técnica de {$client->nombres} {$client->paterno}.",
                'completada' => $i % 2 === 0,
                'fecha_limite' => Carbon::today()->addDays($i * 7)->toDateString(),
                'id_usuario' => $trainer->id,
                'id_cliente' => $client->id,
                'slug' => $slug,
            ]);
        }
    }

    private function seedCoursesForClientContext(object $client, object $trainer, int $clientIndex): void
    {
        $courseCount = 2 + (($clientIndex + 1) % 2);
        $courseNames = [
            'Evaluación inicial aplicada',
            'Movilidad y técnica básica',
            'Progresión de fuerza segura',
        ];

        for ($i = 1; $i <= $courseCount; $i++) {
            $slug = sprintf('curso-cliente-%d-%d', $client->id, $i);
            $start = Carbon::today()->subMonths(4 - $i)->startOfMonth();
            $end = $start->copy()->addWeeks(3);

            $this->upsertRow('entrenadores_cursos', ['slug' => $slug], [
                'id_entrenador' => $trainer->id,
                'curso' => $courseNames[$i - 1] . " · {$client->nombres}",
                'fecha_inicio' => $start->toDateString(),
                'fecha_fin' => $end->toDateString(),
                'institucion' => 'Gym Performance Lab',
                'modalidad' => ($i % 2) + 1,
                'slug' => $slug,
                'id_cliente' => $client->id,
            ]);
        }
    }

    private function seedAgendasForClient(object $client, object $trainer, object $gym, array $exerciseIds, int $clientIndex): void
    {
        $agendaCount = 5 + (($clientIndex + ((int) $gym->id)) % 6);
        $exerciseIds = array_values($exerciseIds);

        for ($i = 1; $i <= $agendaCount; $i++) {
            $start = Carbon::today()
                ->addDays((($i - 1) * 2) + (($clientIndex - 1) % 3))
                ->setTime(7 + (($i + $clientIndex) % 10), ($i % 2) * 30);
            $end = $start->copy()->addHour();
            $slug = sprintf('agenda-cliente-%d-%d', $client->id, $i);

            $agenda = $this->upsertRow('agendas', ['slug' => $slug], [
                'id_cliente' => $client->id,
                'id_usuario' => $trainer->id,
                'id_gimnasio' => $gym->id,
                'fecha_inicio' => $start->format('Y-m-d H:i:s'),
                'fecha_fin' => $end->format('Y-m-d H:i:s'),
                'titulo' => "Sesión futura {$i} · {$client->nombres}",
                'descripcion' => 'Sesión futura programada para validación y seguimiento.',
                'estado' => 1,
                'slug' => $slug,
            ]);

            DB::table('agendas_ejercicios')->where('id_agenda', $agenda->id)->delete();

            $exerciseCount = 3 + (($i + $clientIndex) % 2);
            $startAt = ($i + $clientIndex) % max(count($exerciseIds), 1);
            $selected = [];

            for ($offset = 0; $offset < $exerciseCount; $offset++) {
                $selected[] = $exerciseIds[($startAt + $offset) % count($exerciseIds)];
            }

            foreach ($selected as $exerciseOrder => $exerciseId) {
                DB::table('agendas_ejercicios')->insert($this->withTimestamps('agendas_ejercicios', $this->filterColumns('agendas_ejercicios', [
                    'id_agenda' => $agenda->id,
                    'id_ejercicio' => $exerciseId,
                    'serie' => 3 + ($exerciseOrder % 2),
                    'repeticiones' => ['10-12', '12-15', '8-10', '30 seg'][$exerciseOrder % 4],
                    'rir' => '2',
                    'rpe' => '7',
                    'rm' => 'N/A',
                    'metodo' => 1,
                    'progresion' => 1,
                    'fundamento' => 'Sesión de prueba consistente para validación funcional',
                    'carga' => (15 + ($exerciseOrder * 5)) . ' kg',
                    'descanso' => (60 + ($exerciseOrder * 15)) . ' s',
                ])));
            }
        }
    }

    private function seedFutureAgendasForAllClients(array $exerciseIds): void
    {
        $clients = DB::table('clientes')
            ->where('estado', 1)
            ->orderBy('id')
            ->get();

        foreach ($clients as $index => $client) {
            $trainer = DB::table('users')
                ->where('id', $client->id_usuario)
                ->where('id_tipo_usuario', 2)
                ->first();

            if (! $trainer) {
                $trainer = DB::table('users')
                    ->where('id_tipo_usuario', 2)
                    ->when($client->id_gimnasio, function ($query) use ($client) {
                        $query->where('id_gimnasio', $client->id_gimnasio);
                    })
                    ->orderBy('id')
                    ->first();
            }

            $gym = DB::table('gimnasios')->where('id', $client->id_gimnasio)->first()
                ?? Gimnasios::query()->orderBy('id')->first();

            if (! $trainer || ! $gym) {
                continue;
            }

            $this->seedAgendasForClient($client, $trainer, $gym, $exerciseIds, ($index % 10) + 1);
        }
    }

    private function seedPastAgendasForAllClients(array $exerciseIds): void
    {
        $clients = DB::table('clientes')
            ->where('estado', 1)
            ->orderBy('id')
            ->get();

        $exercisePool = array_values($exerciseIds);

        foreach ($clients as $index => $client) {
            $trainer = DB::table('users')
                ->where('id', $client->id_usuario)
                ->where('id_tipo_usuario', 2)
                ->first();

            if (! $trainer) {
                $trainer = DB::table('users')
                    ->where('id_tipo_usuario', 2)
                    ->when($client->id_gimnasio, function ($query) use ($client) {
                        $query->where('id_gimnasio', $client->id_gimnasio);
                    })
                    ->orderBy('id')
                    ->first();
            }

            $gym = DB::table('gimnasios')->where('id', $client->id_gimnasio)->first()
                ?? Gimnasios::query()->orderBy('id')->first();

            if (! $trainer || ! $gym || count($exercisePool) === 0) {
                continue;
            }

            $agendaCount = 3 + ($index % 3);

            for ($i = 1; $i <= $agendaCount; $i++) {
                $start = Carbon::today()
                    ->subDays((($i + 1) * 4) + (($index % 4) * 2))
                    ->setTime(7 + (($i + $index) % 10), ($i % 2) * 30);
                $end = $start->copy()->addHour();
                $slug = sprintf('agenda-pasada-cliente-%d-%d', $client->id, $i);

                $agenda = $this->upsertRow('agendas', ['slug' => $slug], [
                    'id_cliente' => $client->id,
                    'id_usuario' => $trainer->id,
                    'id_gimnasio' => $gym->id,
                    'fecha_inicio' => $start->format('Y-m-d H:i:s'),
                    'fecha_fin' => $end->format('Y-m-d H:i:s'),
                    'titulo' => "Sesión realizada {$i} · {$client->nombres}",
                    'descripcion' => 'Sesión pasada marcada como realizada para historial.',
                    'estado' => 4,
                    'slug' => $slug,
                ]);

                DB::table('agendas_ejercicios')->where('id_agenda', $agenda->id)->delete();

                $exerciseCount = 3 + (($i + $index) % 2);
                $startAt = ($i + $index) % count($exercisePool);
                $selected = [];

                for ($offset = 0; $offset < $exerciseCount; $offset++) {
                    $selected[] = $exercisePool[($startAt + $offset) % count($exercisePool)];
                }

                foreach ($selected as $exerciseOrder => $exerciseId) {
                    DB::table('agendas_ejercicios')->insert($this->withTimestamps('agendas_ejercicios', $this->filterColumns('agendas_ejercicios', [
                        'id_agenda' => $agenda->id,
                        'id_ejercicio' => $exerciseId,
                        'serie' => 3 + ($exerciseOrder % 2),
                        'repeticiones' => ['10-12', '12-15', '8-10', '30 seg'][$exerciseOrder % 4],
                        'rir' => '2',
                        'rpe' => '7',
                        'rm' => 'N/A',
                        'metodo' => 1,
                        'progresion' => 1,
                        'fundamento' => 'Sesión histórica consistente para validación del panel',
                        'carga' => (15 + ($exerciseOrder * 5)) . ' kg',
                        'descanso' => (60 + ($exerciseOrder * 15)) . ' s',
                    ])));
                }
            }
        }
    }

    private function ensureSupportUser(object $gym): void
    {
        $this->upsertRow('users', ['email' => 'test@example.com'], [
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => $this->passwordHash(),
            'remember_token' => Str::random(10),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => $gym->id,
            'slug' => 'usuario-de-prueba',
        ]);
    }

    private function upsertRow(string $table, array $unique, array $data): object
    {
        $unique = $this->filterColumns($table, $unique);
        $payload = $this->withTimestamps($table, array_merge($unique, $data));

        DB::table($table)->updateOrInsert($unique, $this->filterColumns($table, $payload));

        $query = DB::table($table);
        foreach ($unique as $column => $value) {
            $query->where($column, $value);
        }

        return $query->first();
    }

    private function syncCatalogRow(string $table, array $data): object
    {
        $identityColumns = ['id', 'slug', 'nombre'];
        $existing = DB::table($table)
            ->where(function ($query) use ($table, $data, $identityColumns) {
                foreach ($identityColumns as $index => $column) {
                    if (! array_key_exists($column, $data) || ! $this->hasColumn($table, $column)) {
                        continue;
                    }

                    if ($index === 0) {
                        $query->where($column, $data[$column]);
                    } else {
                        $query->orWhere($column, $data[$column]);
                    }
                }
            })
            ->first();

        $payload = $this->filterColumns($table, $this->withTimestamps($table, $data));

        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update($payload);
            return DB::table($table)->where('id', $existing->id)->first();
        }

        DB::table($table)->insert($payload);

        if (isset($payload['slug'])) {
            return DB::table($table)->where('slug', $payload['slug'])->first();
        }

        return DB::table($table)->where('nombre', $payload['nombre'])->first();
    }

    private function filterColumns(string $table, array $data): array
    {
        if (! isset($this->columnsCache[$table])) {
            $this->columnsCache[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_intersect_key($data, $this->columnsCache[$table]);
    }

    private function withTimestamps(string $table, array $data): array
    {
        $now = now();

        if ($this->hasColumn($table, 'created_at') && ! array_key_exists('created_at', $data)) {
            $data['created_at'] = $now;
        }

        if ($this->hasColumn($table, 'updated_at')) {
            $data['updated_at'] = $now;
        }

        return $data;
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (! isset($this->columnsCache[$table])) {
            $this->columnsCache[$table] = array_flip(Schema::getColumnListing($table));
        }

        return isset($this->columnsCache[$table][$column]);
    }

    private function passwordHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make($this->defaultPassword);
    }
}
