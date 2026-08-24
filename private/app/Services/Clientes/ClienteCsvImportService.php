<?php

namespace App\Services\Clientes;

use App\Models\Clientes;
use App\Models\Generos;
use App\Models\Motivos;
use App\Models\Planes;
use App\Rules\RutChileno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importador CSV/Excel de clientes (Columna 4 de 3columnas.txt).
 *
 * Solo CSV en esta primera versión (no .xlsx binario): pedirle al admin que
 * guarde su planilla como "CSV (delimitado por comas)" desde Excel/Sheets es
 * un paso trivial para el usuario y evita sumar una librería nueva
 * (PhpSpreadsheet) solo para leer un archivo que en la práctica casi siempre
 * viene de una exportación de Excel de todas formas.
 *
 * Decisión explícita de alcance: solo crea el registro de `clientes` + su
 * cuenta de acceso (con clave generada, sin enviar el correo de bienvenida —
 * eso queda para el botón "Enviar acceso" existente, para no mandar un
 * correo masivo de golpe). No genera cuotas ni historial de pagos: eso
 * pertenece a un import de cuentas corrientes aparte, si algún día hace
 * falta.
 */
class ClienteCsvImportService
{
    /**
     * Columnas reconocidas en el CSV. La clave es el nombre normalizado
     * (minúsculas, sin tildes, espacios->_) que se busca en el encabezado;
     * el valor indica si es obligatoria.
     */
    public const COLUMNAS = [
        'nombres' => true,
        'paterno' => true,
        'materno' => false,
        'email' => true,
        'telefono' => true,
        'ci' => true,
        'genero' => true,
        'fecha_nacimiento' => false,
        'fecha_ingreso' => false,
        'direccion' => false,
        'ciudad' => false,
        'altura' => false,
        'motivo_ingreso' => false,
        'plan' => false,
        'entrenador' => false,
    ];

    /**
     * Lee el archivo y devuelve las filas crudas como arrays asociativos con
     * las claves normalizadas de COLUMNAS. Tolera BOM de Excel, delimitador
     * `;` (típico de Excel en español/Chile) además de `,`, y encoding
     * Windows-1252/ISO-8859-1 (Excel en Windows no siempre exporta UTF-8).
     */
    public function parseFile(string $path): array
    {
        $contenido = file_get_contents($path);
        if ($contenido === false) {
            throw new \RuntimeException('No se pudo leer el archivo.');
        }

        // BOM UTF-8
        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $convertido = @mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
            if ($convertido !== false) {
                $contenido = $convertido;
            }
        }

        $primeraLinea = strtok($contenido, "\n") ?: '';
        $delimitador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $lineas = array_values(array_filter($lineas, fn ($l) => trim($l) !== ''));

        if (empty($lineas)) {
            return ['encabezados' => [], 'filas' => []];
        }

        $encabezadosCrudos = str_getcsv(array_shift($lineas), $delimitador);
        $encabezados = array_map([$this, 'normalizarEncabezado'], $encabezadosCrudos);

        $filas = [];
        foreach ($lineas as $linea) {
            $valores = str_getcsv($linea, $delimitador);
            $fila = [];
            foreach ($encabezados as $i => $clave) {
                if ($clave === null) {
                    continue;
                }
                $fila[$clave] = isset($valores[$i]) ? trim($valores[$i]) : null;
            }
            $filas[] = $fila;
        }

        return ['encabezados' => $encabezados, 'filas' => $filas];
    }

    private function normalizarEncabezado(string $encabezado): ?string
    {
        $normalizado = strtolower(trim($encabezado));
        $normalizado = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $normalizado,
        );
        $normalizado = preg_replace('/[^a-z0-9]+/', '_', $normalizado);
        $normalizado = trim($normalizado, '_');

        // Alias comunes que un admin podría escribir distinto a la clave interna.
        $alias = [
            'apellido_paterno' => 'paterno',
            'apellido' => 'paterno',
            'apellido_materno' => 'materno',
            'telefono_movil' => 'telefono',
            'celular' => 'telefono',
            'rut' => 'ci',
            'cedula' => 'ci',
            'cedula_de_identidad' => 'ci',
            'sexo' => 'genero',
            'id_genero' => 'genero',
            'fecha_de_nacimiento' => 'fecha_nacimiento',
            'nacimiento' => 'fecha_nacimiento',
            'fecha_de_ingreso' => 'fecha_ingreso',
            'ingreso' => 'fecha_ingreso',
            'motivo_de_ingreso' => 'motivo_ingreso',
            'motivo' => 'motivo_ingreso',
            'plan_de_membresia' => 'plan',
            'membresia' => 'plan',
            'entrenador_asignado' => 'entrenador',
        ];

        return $alias[$normalizado] ?? ($normalizado !== '' ? $normalizado : null);
    }

    /**
     * Valida cada fila cruda y la separa en válidas (listas para insertar) e
     * inválidas (con el detalle de qué falló). No escribe nada en la base de
     * datos — todas las consultas son de solo lectura (catálogos, unicidad).
     */
    public function validarFilas(array $filas, int $idGimnasio, ?int $idPlanPorDefecto): array
    {
        $generos = Generos::where('estado', 1)->pluck('nombre', 'id');
        $motivos = Motivos::where('estado', 1)->where('tipo', 1)->pluck('nombre', 'id');
        $planes = Planes::where('estado', 1)
            ->when($idGimnasio, fn ($q) => $q->where('id_gimnasio', $idGimnasio))
            ->pluck('id', 'nombre');
        $entrenadores = \App\Models\User::where('id_tipo_usuario', 2)
            ->when($idGimnasio, fn ($q) => $q->where('id_gimnasio', $idGimnasio))
            ->pluck('id', 'name');

        $cisExistentes = Clientes::pluck('ci')->map(fn ($c) => strtoupper(preg_replace('/[.\-]/', '', $c)))->flip();
        $emailsExistentes = Clientes::pluck('email')->map('strtolower')->flip();
        $emailsUsersExistentes = \App\Models\User::whereNotNull('email')->pluck('email')->map('strtolower')->flip();

        $validas = [];
        $invalidas = [];
        $cisVistosEnLote = [];
        $emailsVistosEnLote = [];
        $slugsVistosEnLote = [];

        foreach ($filas as $i => $fila) {
            $numeroFila = $i + 2; // +1 por índice base 0, +1 por la fila de encabezado
            $errores = [];

            $nombres = trim((string) ($fila['nombres'] ?? ''));
            $paterno = trim((string) ($fila['paterno'] ?? ''));
            $email = trim((string) ($fila['email'] ?? ''));
            $telefono = trim((string) ($fila['telefono'] ?? ''));
            $ci = trim((string) ($fila['ci'] ?? ''));
            $generoRaw = trim((string) ($fila['genero'] ?? ''));

            if ($nombres === '') $errores[] = 'Falta "nombres".';
            if ($paterno === '') $errores[] = 'Falta "paterno" (apellido).';
            if ($telefono === '') $errores[] = 'Falta "telefono".';

            if ($email === '') {
                $errores[] = 'Falta "email".';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El email no es válido.';
            } else {
                $emailNorm = strtolower($email);
                if (isset($emailsExistentes[$emailNorm]) || isset($emailsUsersExistentes[$emailNorm])) {
                    $errores[] = 'Ya existe un cliente o usuario con ese email.';
                } elseif (isset($emailsVistosEnLote[$emailNorm])) {
                    $errores[] = 'Email repetido en el archivo (fila ' . $emailsVistosEnLote[$emailNorm] . ').';
                }
            }

            if ($ci === '') {
                $errores[] = 'Falta "ci" (RUT/identificación).';
            } else {
                $rutFail = null;
                (new RutChileno())->validate('ci', $ci, function ($msg) use (&$rutFail) { $rutFail = $msg; });
                if ($rutFail) {
                    $errores[] = $rutFail;
                }
                $ciNorm = strtoupper(preg_replace('/[.\-]/', '', $ci));
                if (isset($cisExistentes[$ciNorm])) {
                    $errores[] = 'Ya existe un cliente con ese CI/RUT.';
                } elseif (isset($cisVistosEnLote[$ciNorm])) {
                    $errores[] = 'CI/RUT repetido en el archivo (fila ' . $cisVistosEnLote[$ciNorm] . ').';
                }
            }

            $idGenero = null;
            if ($generoRaw === '') {
                $errores[] = 'Falta "genero".';
            } elseif (is_numeric($generoRaw) && $generos->has((int) $generoRaw)) {
                $idGenero = (int) $generoRaw;
            } else {
                $match = $generos->first(fn ($nombre) => $this->similar($nombre, $generoRaw));
                if ($match !== null) {
                    $idGenero = $generos->search($match);
                } else {
                    $errores[] = 'Género "' . $generoRaw . '" no reconocido (usar Femenino, Masculino u Otro, o el ID).';
                }
            }

            $idPlan = $idPlanPorDefecto;
            $planRaw = trim((string) ($fila['plan'] ?? ''));
            if ($planRaw !== '') {
                $match = $planes->keys()->first(fn ($nombre) => $this->similar($nombre, $planRaw));
                if ($match !== null) {
                    $idPlan = $planes[$match];
                } else {
                    $errores[] = 'Plan "' . $planRaw . '" no existe en este gimnasio.';
                }
            }
            if (! $idPlan) {
                $errores[] = 'Falta el plan (no viene en la fila ni hay un plan por defecto elegido).';
            }

            $idMotivoIngreso = null;
            $motivoRaw = trim((string) ($fila['motivo_ingreso'] ?? ''));
            if ($motivoRaw !== '') {
                $match = $motivos->first(fn ($nombre) => $this->similar($nombre, $motivoRaw));
                if ($match !== null) {
                    $idMotivoIngreso = $motivos->search($match);
                }
                // Si no matchea ningún motivo del catálogo, se deja en null sin
                // fallar la fila — es metadata, no un dato crítico.
            }

            $idEntrenador = null;
            $entrenadorRaw = trim((string) ($fila['entrenador'] ?? ''));
            if ($entrenadorRaw !== '') {
                $match = $entrenadores->keys()->first(fn ($nombre) => $this->similar($nombre, $entrenadorRaw));
                if ($match !== null) {
                    $idEntrenador = $entrenadores[$match];
                }
                // Igual que el motivo: si no matchea, se deja sin entrenador
                // asignado en vez de fallar la fila completa.
            }

            $fechaNacimiento = $this->parsearFecha($fila['fecha_nacimiento'] ?? null);
            $fechaIngreso = $this->parsearFecha($fila['fecha_ingreso'] ?? null) ?? now()->toDateString();

            $altura = trim((string) ($fila['altura'] ?? ''));
            $alturaValida = null;
            if ($altura !== '') {
                $alturaNum = (float) str_replace(',', '.', $altura);
                if ($alturaNum >= 0.5 && $alturaNum <= 2.5) {
                    $alturaValida = $alturaNum;
                } else {
                    $errores[] = 'Altura fuera de rango (debe ser un número en metros, ej: 1.70).';
                }
            }

            if (! empty($errores)) {
                $invalidas[] = [
                    'fila' => $numeroFila,
                    'nombre' => trim($nombres . ' ' . $paterno),
                    'errores' => $errores,
                ];
                continue;
            }

            $base = Str::slug($nombres . '-' . $paterno);
            $slug = $base;
            $sufijo = 0;
            while (isset($slugsVistosEnLote[$slug]) || Clientes::where('slug', $slug)->exists()) {
                $slug = $base . '-' . (++$sufijo);
            }
            $slugsVistosEnLote[$slug] = true;

            $ciNorm = strtoupper(preg_replace('/[.\-]/', '', $ci));
            $cisVistosEnLote[$ciNorm] = $numeroFila;
            $emailsVistosEnLote[strtolower($email)] = $numeroFila;

            $validas[] = [
                'fila' => $numeroFila,
                'nombres' => $nombres,
                'paterno' => $paterno,
                'materno' => trim((string) ($fila['materno'] ?? '')) ?: null,
                'email' => $email,
                'telefono' => $telefono,
                'ci' => $ci,
                'id_genero' => $idGenero,
                'fecha_nacimiento' => $fechaNacimiento,
                'fecha_ingreso' => $fechaIngreso,
                'direccion' => trim((string) ($fila['direccion'] ?? '')) ?: null,
                'ciudad' => trim((string) ($fila['ciudad'] ?? '')) ?: null,
                'altura' => $alturaValida,
                'id_motivo_ingreso' => $idMotivoIngreso,
                'id_plan' => $idPlan,
                'id_usuario' => $idEntrenador,
                'slug' => $slug,
                'id_gimnasio' => $idGimnasio ?: null,
            ];
        }

        return ['validas' => $validas, 'invalidas' => $invalidas];
    }

    private function similar(string $a, string $b): bool
    {
        $normalizar = function (string $s) {
            $s = strtolower(trim($s));
            return str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $s);
        };

        return $normalizar($a) === $normalizar($b);
    }

    private function parsearFecha(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd/m/y'] as $formato) {
            $fecha = \DateTime::createFromFormat($formato, $valor);
            if ($fecha !== false) {
                return $fecha->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Crea los clientes de las filas ya validadas, cada uno en su propia
     * transacción — si una fila falla al insertar (condición de carrera con
     * otra fila del lote, por ejemplo), las demás no se ven afectadas.
     * Devuelve cuántas se crearon y el detalle de las que fallaron igual.
     */
    public function importarFilasValidas(array $filasValidas, \App\Services\Clientes\ClienteLifecycleService $lifecycle): array
    {
        $creadas = 0;
        $fallidas = [];

        foreach ($filasValidas as $fila) {
            try {
                DB::transaction(function () use ($fila, $lifecycle) {
                    $cliente = Clientes::create([
                        'nombres' => $fila['nombres'],
                        'paterno' => $fila['paterno'],
                        'materno' => $fila['materno'],
                        'email' => $fila['email'],
                        'telefono' => $fila['telefono'],
                        'ci' => $fila['ci'],
                        'id_genero' => $fila['id_genero'],
                        'fecha_nacimiento' => $fila['fecha_nacimiento'],
                        'fecha_ingreso' => $fila['fecha_ingreso'],
                        'fecha_pago' => $fila['fecha_ingreso'],
                        'direccion' => $fila['direccion'],
                        'ciudad' => $fila['ciudad'],
                        'altura' => $fila['altura'],
                        'id_motivo_ingreso' => $fila['id_motivo_ingreso'],
                        'id_plan' => $fila['id_plan'],
                        'id_usuario' => $fila['id_usuario'],
                        'slug' => $fila['slug'],
                        'estado' => 1,
                        'id_gimnasio' => $fila['id_gimnasio'],
                    ]);

                    // Se crea la cuenta de acceso (clave determinística por
                    // CI/email) pero NO se envía el correo de bienvenida —
                    // decisión explícita para no mandar un correo masivo de
                    // golpe. El admin activa el acceso cuando quiera con el
                    // botón "Enviar acceso" que ya existe por cliente.
                    $lifecycle->createAccessUserForCliente($cliente);
                });
                $creadas++;
            } catch (\Throwable $e) {
                $fallidas[] = [
                    'fila' => $fila['fila'],
                    'nombre' => trim($fila['nombres'] . ' ' . $fila['paterno']),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return ['creadas' => $creadas, 'fallidas' => $fallidas];
    }
}
