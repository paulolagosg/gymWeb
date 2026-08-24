<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class TrainerClientsRoutinesSeeder extends Seeder
{
    private array $columnsCache = [];

    private string $defaultPassword = '12345678';

    public function run(): void
    {
        $this->assertRequiredTables();

        $this->ensureReferenceCatalogs();
        $exerciseIds = $this->ensureExerciseCatalog();

        $trainers = DB::table('users')
            ->whereIn('id', range(2, 7))
            ->orderBy('id')
            ->get();

        if ($trainers->count() !== 6) {
            throw new RuntimeException('Se esperaban 6 entrenadores en users con id del 2 al 7.');
        }

        $profiles = $this->clientProfiles();

        foreach ($trainers as $trainerIndex => $trainer) {
            $gym = $this->resolveGymForTrainer($trainer);
            $trainer = $this->syncTrainerCredentials($trainer, $gym);
            $plan = $this->resolvePlanForGym($gym, $trainerIndex + 1);

            for ($clientIndex = 1; $clientIndex <= 6; $clientIndex++) {
                $profile = $profiles[($trainerIndex * 6) + ($clientIndex - 1)];
                $client = $this->upsertClient($trainer, $gym, $plan, $profile, $clientIndex);

                $this->upsertClientUser($client, $gym, $profile, $clientIndex);
                $this->syncClientRoutines($client, $trainer, $gym, $exerciseIds, $clientIndex);
            }
        }

        if ($this->command) {
            $this->command->info('Seeder TrainerClientsRoutinesSeeder ejecutado correctamente.');
            $this->command->line('Entrenadores 2-7 actualizados con clave 12345678 y se generaron 36 clientes con 15 rutinas cada uno.');
        }
    }

    private function assertRequiredTables(): void
    {
        foreach (['users', 'clientes', 'agendas', 'agendas_ejercicios', 'ejercicios', 'planes', 'generos', 'gimnasios'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Falta la tabla requerida: {$table}.");
            }
        }
    }

    private function ensureReferenceCatalogs(): void
    {
        if (Schema::hasTable('tipos_usuarios')) {
            foreach (
                [
                    ['id' => 2, 'nombre' => 'Entrenador', 'slug' => 'entrenador', 'descripcion' => 'Entrenador personal', 'estado' => 1],
                    ['id' => 4, 'nombre' => 'Cliente', 'slug' => 'cliente', 'descripcion' => 'Cliente del gimnasio', 'estado' => 1],
                ] as $role
            ) {
                $this->upsertRow('tipos_usuarios', ['id' => $role['id']], $role);
            }
        }

        foreach (
            [
                ['nombre' => 'Femenino', 'slug' => 'femenino', 'estado' => 1],
                ['nombre' => 'Masculino', 'slug' => 'masculino', 'estado' => 1],
            ] as $gender
        ) {
            $this->upsertRow('generos', ['slug' => $gender['slug']], $gender);
        }

        $this->ensureDefaultGym();
    }

    private function ensureDefaultGym(): object
    {
        return $this->upsertRow('gimnasios', ['slug' => 'gimnasio-ampaya'], [
            'nombre' => 'Gimnasio Ampaya',
            'slug' => 'gimnasio-ampaya',
            'direccion' => 'Dirección referencial 123',
            'descripcion' => 'Gimnasio base para seeders de clientes y rutinas.',
            'telefono' => '+56 9 1234 5678',
            'correo_electronico' => 'contacto@ampaya.cl',
            'sitio_web' => 'https://www.ampaya.cl',
            'instagram' => '@gimnasioampaya',
            'facebook' => 'gimnasioampaya',
            'tiktok' => '@gimnasioampaya',
            'estado' => 1,
        ]);
    }

    private function resolveGymForTrainer(object $trainer): object
    {
        if ($this->hasColumn('users', 'id_gimnasio') && ! empty($trainer->id_gimnasio)) {
            $gym = DB::table('gimnasios')->where('id', $trainer->id_gimnasio)->first();
            if ($gym) {
                return $gym;
            }
        }

        $gym = DB::table('gimnasios')->orderBy('id')->first() ?? $this->ensureDefaultGym();

        if ($this->hasColumn('users', 'id_gimnasio')) {
            DB::table('users')->where('id', $trainer->id)->update(['id_gimnasio' => $gym->id]);
        }

        return $gym;
    }

    private function resolvePlanForGym(object $gym, int $sequence): object
    {
        $query = DB::table('planes')->orderBy('id');

        if ($this->hasColumn('planes', 'id_gimnasio')) {
            $query->where('id_gimnasio', $gym->id);
        }

        $plan = $query->first();

        if ($plan) {
            return $plan;
        }

        return $this->upsertRow('planes', ['slug' => "plan-ampaya-base-g{$gym->id}"], [
            'nombre' => "Plan Ampaya Base G{$sequence}",
            'descripcion' => 'Plan base para clientes creados por seeder.',
            'valor' => 34990,
            'porcentaje' => 35,
            'slug' => "plan-ampaya-base-g{$gym->id}",
            'estado' => 1,
            'id_gimnasio' => $gym->id,
        ]);
    }

    private function ensureExerciseCatalog(): array
    {
        $groupIds = $this->ensureMuscleGroups();
        $typeDefinitions = [
            ['nombre' => 'Pecho', 'icono' => 'fitness-outline', 'grupo' => 'Pecho'],
            ['nombre' => 'Espalda', 'icono' => 'body-outline', 'grupo' => 'Espalda'],
            ['nombre' => 'Piernas', 'icono' => 'walk-outline', 'grupo' => 'Pierna'],
            ['nombre' => 'Hombros', 'icono' => 'accessibility-outline', 'grupo' => 'Hombro'],
            ['nombre' => 'Brazos', 'icono' => 'barbell-outline', 'grupo' => 'Brazos'],
            ['nombre' => 'Core', 'icono' => 'flame-outline', 'grupo' => 'Core'],
            ['nombre' => 'Cardio', 'icono' => 'heart-outline', 'grupo' => 'Cardio'],
            ['nombre' => 'Gluteos', 'icono' => 'pulse-outline', 'grupo' => 'Gluteos'],
        ];

        foreach ($typeDefinitions as $typeDefinition) {
            $this->upsertRow('tipos_ejercicios', ['nombre' => $typeDefinition['nombre']], [
                'nombre' => $typeDefinition['nombre'],
                'icono' => $typeDefinition['icono'],
                'id_grupo' => $groupIds[$typeDefinition['grupo']] ?? null,
            ]);
        }

        $typeIds = DB::table('tipos_ejercicios')
            ->pluck('id', 'nombre')
            ->mapWithKeys(fn($id, $name) => [$name => (int) $id])
            ->all();

        $exerciseDefinitions = [
            ['nombre' => 'Sentadilla goblet', 'tipo' => 'Piernas', 'descripcion' => 'Trabajo principal de tren inferior.'],
            ['nombre' => 'Hip thrust con barra', 'tipo' => 'Gluteos', 'descripcion' => 'Trabajo principal de gluteos y cadera.'],
            ['nombre' => 'Press de pecho con mancuernas', 'tipo' => 'Pecho', 'descripcion' => 'Empuje horizontal controlado.'],
            ['nombre' => 'Remo sentado', 'tipo' => 'Espalda', 'descripcion' => 'Trabajo dorsal y retracción escapular.'],
            ['nombre' => 'Elevaciones laterales', 'tipo' => 'Hombros', 'descripcion' => 'Trabajo accesorio de hombros.'],
            ['nombre' => 'Curl martillo', 'tipo' => 'Brazos', 'descripcion' => 'Trabajo de brazos y agarre.'],
            ['nombre' => 'Plancha frontal', 'tipo' => 'Core', 'descripcion' => 'Estabilidad lumbo-pélvica.'],
            ['nombre' => 'Bicicleta estática', 'tipo' => 'Cardio', 'descripcion' => 'Trabajo aeróbico moderado.'],
        ];

        $exerciseSlugs = [];

        foreach ($exerciseDefinitions as $exerciseDefinition) {
            $slug = Str::slug($exerciseDefinition['nombre']);
            $exerciseSlugs[] = $slug;

            $this->upsertRow('ejercicios', ['slug' => $slug], [
                'nombre' => $exerciseDefinition['nombre'],
                'icono' => null,
                'id_tipo' => $typeIds[$exerciseDefinition['tipo']] ?? null,
                'slug' => $slug,
                'descripcion' => $exerciseDefinition['descripcion'],
                'estado' => 1,
            ]);
        }

        return DB::table('ejercicios')
            ->whereIn('slug', $exerciseSlugs)
            ->orderBy('id')
            ->limit(8)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function ensureMuscleGroups(): array
    {
        if (! Schema::hasTable('grupos_musculares')) {
            return [];
        }

        $definitions = [
            ['nombre' => 'Pierna', 'icono' => 'walk-outline', 'color' => '#2ECC71', 'estado' => 1],
            ['nombre' => 'Pecho', 'icono' => 'fitness-outline', 'color' => '#FF6584', 'estado' => 1],
            ['nombre' => 'Espalda', 'icono' => 'body-outline', 'color' => '#3498DB', 'estado' => 1],
            ['nombre' => 'Hombro', 'icono' => 'accessibility-outline', 'color' => '#9B59B6', 'estado' => 1],
            ['nombre' => 'Brazos', 'icono' => 'barbell-outline', 'color' => '#F39C12', 'estado' => 1],
            ['nombre' => 'Core', 'icono' => 'flame-outline', 'color' => '#E67E22', 'estado' => 1],
            ['nombre' => 'Gluteos', 'icono' => 'pulse-outline', 'color' => '#16A085', 'estado' => 1],
            ['nombre' => 'Cardio', 'icono' => 'heart-outline', 'color' => '#E74C3C', 'estado' => 1],
            ['nombre' => 'Full Body', 'icono' => 'apps-outline', 'color' => '#6C63FF', 'estado' => 1],
        ];

        foreach ($definitions as $definition) {
            $this->upsertRow('grupos_musculares', ['nombre' => $definition['nombre']], $definition);
        }

        return DB::table('grupos_musculares')
            ->pluck('id', 'nombre')
            ->mapWithKeys(fn($id, $name) => [$name => (int) $id])
            ->all();
    }

    private function syncTrainerCredentials(object $trainer, object $gym): object
    {
        [$firstName, $surname] = $this->splitDisplayName($trainer->name ?? 'Entrenador');
        $emailBase = $this->emailBase($firstName, $surname);
        $email = $this->resolveUniqueEmail($emailBase, (int) $trainer->id, null);

        DB::table('users')
            ->where('id', $trainer->id)
            ->update($this->filterColumns('users', [
                'email' => $email,
                'password' => $this->passwordHash(),
                'email_verified_at' => now(),
                'id_tipo_usuario' => 2,
                'id_gimnasio' => $gym->id,
                'updated_at' => now(),
            ]));

        return DB::table('users')->where('id', $trainer->id)->first();
    }

    private function upsertClient(object $trainer, object $gym, object $plan, array $profile, int $clientIndex): object
    {
        $ci = (string) (78000000 + (((int) $trainer->id - 1) * 10) + $clientIndex);
        $slug = "cliente-seeder-{$trainer->id}-{$clientIndex}";
        $existingClient = DB::table('clientes')->where('ci', $ci)->first();
        $existingUser = $existingClient
            ? DB::table('users')->where('id_cliente', $existingClient->id)->orderBy('id')->first()
            : null;
        $emailBase = $this->emailBase($profile['nombres'], $profile['paterno']);
        $email = $this->resolveUniqueEmail($emailBase, $existingUser?->id ? (int) $existingUser->id : null, $existingClient?->id ? (int) $existingClient->id : null);

        $genderId = $this->resolveGenderId($profile['genero']);
        $fechaIngreso = Carbon::today()->subMonths(3)->subDays(($clientIndex - 1) * 3);
        $fechaPago = $fechaIngreso->copy()->addDays(7);
        $fechaNacimiento = Carbon::today()->subYears($profile['edad'])->subDays(($clientIndex + (int) $trainer->id) * 9);

        return $this->upsertRow('clientes', ['ci' => $ci], [
            'ci' => $ci,
            'nombres' => $profile['nombres'],
            'paterno' => $profile['paterno'],
            'materno' => $profile['materno'],
            'telefono' => '+56 9 ' . str_pad((string) (61000000 + (((int) $trainer->id - 2) * 100) + $clientIndex), 8, '0', STR_PAD_LEFT),
            'id_genero' => $genderId,
            'altura' => $profile['altura'],
            'email' => $email,
            'direccion' => "Dirección {$clientIndex} - {$gym->nombre}",
            'ciudad' => 'Santiago',
            'fecha_nacimiento' => $fechaNacimiento->toDateString(),
            'fecha_ingreso' => $fechaIngreso->toDateString(),
            'fecha_pago' => $fechaPago->toDateString(),
            'fecha_fin' => $fechaIngreso->copy()->addYear()->toDateString(),
            'slug' => $slug,
            'id_plan' => $plan->id,
            'id_usuario' => $trainer->id,
            'estado' => 1,
            'perfil' => $profile['perfil'],
            'id_gimnasio' => $gym->id,
        ]);
    }

    private function upsertClientUser(object $client, object $gym, array $profile, int $clientIndex): void
    {
        $existingUser = DB::table('users')->where('id_cliente', $client->id)->orderBy('id')->first();
        $emailBase = $this->emailBase($profile['nombres'], $profile['paterno']);
        $email = $this->resolveUniqueEmail($emailBase, $existingUser?->id ? (int) $existingUser->id : null, (int) $client->id);
        $payload = $this->withTimestamps('users', $this->filterColumns('users', [
            'name' => trim($client->nombres . ' ' . $client->paterno . ' ' . ($client->materno ?? '')),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $this->passwordHash(),
            'remember_token' => $existingUser?->remember_token ?: Str::random(10),
            'id_tipo_usuario' => 4,
            'id_cliente' => $client->id,
            'id_gimnasio' => $gym->id,
            'slug' => "usuario-cliente-seeder-{$client->id}",
            'porcentaje' => 0,
            'titulo' => null,
        ]));

        if ($existingUser) {
            DB::table('users')->where('id', $existingUser->id)->update($payload);
            return;
        }

        DB::table('users')->insert($payload);
    }

    private function syncClientRoutines(object $client, object $trainer, object $gym, array $exerciseIds, int $clientIndex): void
    {
        $expectedSlugs = [];

        for ($pastIndex = 1; $pastIndex <= 10; $pastIndex++) {
            $slug = "rutina-pasada-seeder-{$client->id}-{$pastIndex}";
            $expectedSlugs[] = $slug;
            $start = $this->sessionDate($trainer, $clientIndex, -1 * (11 - $pastIndex));

            $agenda = $this->upsertRow('agendas', ['slug' => $slug], [
                'id_cliente' => $client->id,
                'id_usuario' => $trainer->id,
                'id_gimnasio' => $gym->id,
                'fecha_inicio' => $start->format('Y-m-d H:i:s'),
                'fecha_fin' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
                'titulo' => "Rutina pasada {$pastIndex} · {$client->nombres}",
                'descripcion' => 'Sesión pasada creada por seeder para historial del cliente.',
                'estado' => 4,
                'slug' => $slug,
            ]);

            $this->syncAgendaExercises($agenda->id, $exerciseIds, $pastIndex + $clientIndex, true);
        }

        for ($futureIndex = 1; $futureIndex <= 5; $futureIndex++) {
            $slug = "rutina-futura-seeder-{$client->id}-{$futureIndex}";
            $expectedSlugs[] = $slug;
            $start = $this->sessionDate($trainer, $clientIndex, $futureIndex);

            $agenda = $this->upsertRow('agendas', ['slug' => $slug], [
                'id_cliente' => $client->id,
                'id_usuario' => $trainer->id,
                'id_gimnasio' => $gym->id,
                'fecha_inicio' => $start->format('Y-m-d H:i:s'),
                'fecha_fin' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
                'titulo' => "Rutina futura {$futureIndex} · {$client->nombres}",
                'descripcion' => 'Sesión futura creada por seeder para planificación del cliente.',
                'estado' => 1,
                'slug' => $slug,
            ]);

            $this->syncAgendaExercises($agenda->id, $exerciseIds, 100 + $futureIndex + $clientIndex, false);
        }

        $obsoleteAgendaIds = DB::table('agendas')
            ->where('id_cliente', $client->id)
            ->where('slug', 'like', 'rutina-%-seeder-' . $client->id . '-%')
            ->whereNotIn('slug', $expectedSlugs)
            ->pluck('id')
            ->all();

        if ($obsoleteAgendaIds !== []) {
            DB::table('agendas_ejercicios')->whereIn('id_agenda', $obsoleteAgendaIds)->delete();
            DB::table('agendas')->whereIn('id', $obsoleteAgendaIds)->delete();
        }
    }

    private function sessionDate(object $trainer, int $clientIndex, int $weekOffset): Carbon
    {
        $trainerSlot = max(((int) $trainer->id) - 2, 0) % 6;
        $clientSlot = max($clientIndex - 1, 0);
        $sequenceSlot = max(abs($weekOffset) - 1, 0);
        $dayOffset = ($trainerSlot + $clientSlot + $sequenceSlot) % 6;
        $hour = 7 + (($trainerSlot * 2 + $clientSlot + intdiv($sequenceSlot, 2)) % 11);
        $minutes = (($trainerSlot + $clientSlot + $sequenceSlot) % 2) === 0 ? 0 : 30;

        return Carbon::today()
            ->startOfWeek(Carbon::MONDAY)
            ->addWeeks($weekOffset)
            ->addDays($dayOffset)
            ->setTime($hour, $minutes);
    }

    private function syncAgendaExercises(int $agendaId, array $exerciseIds, int $rotationSeed, bool $isPast): void
    {
        DB::table('agendas_ejercicios')->where('id_agenda', $agendaId)->delete();

        $exerciseCount = min(4, count($exerciseIds));
        $startIndex = $rotationSeed % max(count($exerciseIds), 1);

        for ($offset = 0; $offset < $exerciseCount; $offset++) {
            $exerciseId = $exerciseIds[($startIndex + $offset) % count($exerciseIds)];

            DB::table('agendas_ejercicios')->insert($this->withTimestamps('agendas_ejercicios', $this->filterColumns('agendas_ejercicios', [
                'id_agenda' => $agendaId,
                'id_ejercicio' => $exerciseId,
                'serie' => 3 + ($offset % 2),
                'repeticiones' => ['10-12', '12-15', '8-10', '30 seg'][$offset % 4],
                'rir' => $isPast ? '1-2' : '2-3',
                'rpe' => $isPast ? '8' : '7',
                'rm' => 'N/A',
                'metodo' => 1,
                'progresion' => $isPast ? 2 : 1,
                'fundamento' => $isPast
                    ? 'Rutina histórica para seguimiento de progresión.'
                    : 'Rutina futura planificada para continuidad del cliente.',
                'carga' => (20 + ($rotationSeed % 6) + ($offset * 5)) . ' kg',
                'descanso' => (60 + ($offset * 15)) . ' s',
            ])));
        }
    }

    private function resolveGenderId(string $genderSlug): int
    {
        $genderId = (int) DB::table('generos')->where('slug', $genderSlug)->value('id');

        if ($genderId > 0) {
            return $genderId;
        }

        return (int) DB::table('generos')->orderBy('id')->value('id');
    }

    private function splitDisplayName(string $name): array
    {
        $normalized = Str::of($name)
            ->replaceMatches('/\s*-\s*.*/', '')
            ->ascii()
            ->squish()
            ->value();

        $parts = preg_split('/\s+/', trim($normalized)) ?: [];
        $firstName = $parts[0] ?? 'usuario';

        if (count($parts) >= 3) {
            $surname = $parts[count($parts) - 2];
        } elseif (count($parts) >= 2) {
            $surname = $parts[1];
        } else {
            $surname = $firstName;
        }

        return [$firstName, $surname];
    }

    private function emailBase(string $firstName, string $surname): string
    {
        $initial = Str::of($firstName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z]/', '')
            ->substr(0, 1)
            ->value();

        $lastName = Str::of($surname)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z]/', '')
            ->value();

        return ($initial ?: 'u') . ($lastName ?: 'usuario');
    }

    private function resolveUniqueEmail(string $base, ?int $exceptUserId = null, ?int $exceptClientId = null): string
    {
        $suffix = '';
        $attempt = 1;

        do {
            $email = $base . $suffix . '@gym.ampaya.cl';
            $takenInUsers = DB::table('users')
                ->where('email', $email)
                ->when($exceptUserId, fn($query) => $query->where('id', '!=', $exceptUserId))
                ->exists();
            $takenInClients = DB::table('clientes')
                ->where('email', $email)
                ->when($exceptClientId, fn($query) => $query->where('id', '!=', $exceptClientId))
                ->exists();

            if (! $takenInUsers && ! $takenInClients) {
                return $email;
            }

            $attempt++;
            $suffix = (string) $attempt;
        } while (true);
    }

    private function clientProfiles(): array
    {
        $firstNames = [
            'Ana',
            'Bruno',
            'Carla',
            'Diego',
            'Elisa',
            'Felipe',
            'Gabriela',
            'Hugo',
            'Ines',
            'Javier',
            'Karen',
            'Lautaro',
            'Marta',
            'Nicolas',
            'Olivia',
            'Pablo',
            'Renata',
            'Simon',
            'Tamara',
            'Ulises',
            'Valeria',
            'Wilson',
            'Yessenia',
            'Zoe',
            'Alonso',
            'Beatriz',
            'Cristobal',
            'Daniela',
            'Esteban',
            'Florencia',
            'Gerardo',
            'Helena',
            'Ignacio',
            'Julieta',
            'Kevin',
            'Lorena',
        ];
        $lastNames = [
            'Mendez',
            'Rojas',
            'Caceres',
            'Oyarzun',
            'Tapia',
            'Vergara',
            'Aravena',
            'Bustos',
            'Carrasco',
            'Donoso',
            'Espinoza',
            'Figueroa',
            'Gallardo',
            'Henriquez',
            'Ibarra',
            'Jorquera',
            'Krause',
            'Lagos',
            'Maureira',
            'Novoa',
            'Ortega',
            'Poblete',
            'Quezada',
            'Retamal',
            'Saez',
            'Toledo',
            'Ulloa',
            'Valdes',
            'Weber',
            'Yanez',
            'Zamorano',
            'Alarcon',
            'Bravo',
            'Cornejo',
            'Delgado',
            'Escobar',
        ];
        $maternalLastNames = [
            'Soto',
            'Diaz',
            'Castro',
            'Morales',
            'Fuentes',
            'Pizarro',
            'Navarro',
            'Reyes',
            'Silva',
            'Torres',
            'Vera',
            'Molina',
            'Peña',
            'Campos',
            'Leiva',
            'Contreras',
            'Salas',
            'Acuna',
            'Sepulveda',
            'León',
            'Nuñez',
            'Mardones',
            'Parra',
            'Cortes',
            'Llanos',
            'Pino',
            'Cifuentes',
            'Mella',
            'Godoy',
            'Sanhueza',
            'Guzman',
            'Canales',
            'Meza',
            'Paredes',
            'Arce',
            'Olivares',
        ];
        $profiles = [
            'Busca mejorar fuerza general y adherencia semanal.',
            'Quiere aumentar masa muscular sin perder movilidad.',
            'Necesita una base técnica sólida y progresiva.',
            'Prioriza salud metabólica y constancia mensual.',
            'Apunta a bajar grasa con entrenamiento guiado.',
            'Busca una planificación ordenada y sostenible.',
        ];

        $result = [];

        foreach ($firstNames as $index => $firstName) {
            $result[] = [
                'nombres' => $firstName,
                'paterno' => $lastNames[$index],
                'materno' => $maternalLastNames[$index],
                'genero' => $index % 2 === 0 ? 'femenino' : 'masculino',
                'edad' => 22 + ($index % 19),
                'altura' => 1.55 + (($index % 8) * 0.04),
                'perfil' => $profiles[$index % count($profiles)],
            ];
        }

        return $result;
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
