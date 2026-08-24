<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Cuestionario de Evaluación Inicial (anamnesis del cliente).
 *
 * Requiere que evaluacion_inicial_ddl.sql se haya ejecutado antes.
 *
 * Es idempotente: se puede correr las veces que sea. Actualiza lo existente
 * (buscando por `codigo`) y conserva `created_at` de las filas ya creadas,
 * así no se pierden las respuestas ya asociadas a una pregunta.
 *
 *   php artisan db:seed --class=EvaluacionInicialSeeder
 *
 * VOCABULARIO DEL CAMPO `tipo` (varchar 20):
 *   texto      -> input de una línea            -> valor_texto
 *   textarea   -> texto largo                   -> valor_texto
 *   numero     -> numérico                      -> valor_texto (casteado en la app)
 *   fecha      -> date picker                   -> valor_texto (Y-m-d)
 *   si_no      -> dos opciones (si / no)        -> opcion_id
 *   unica      -> radio / select                -> opcion_id
 *   multiple   -> checkbox                      -> N filas, una por opcion_id
 *   escala     -> slider                        -> opcion_id
 *
 * Cuando `permite_otro = 1` el seeder agrega automáticamente una opción con
 * es_otro = 1. Al responderla se guarda opcion_id (la del "otro") + valor_texto.
 *
 * COLUMNAS OPCIONALES QUE APROVECHA SI EXISTEN:
 *   preguntas.es_sensible                          -> dato de salud
 *   preguntas.depende_pregunta_id / depende_opcion_id -> lógica condicional
 *   opciones.genera_alerta                         -> bandera roja
 *   *.id_gimnasio                                  -> 0 = catálogo base
 * Si alguna no existe, se omite en silencio en vez de reventar.
 */
class EvaluacionInicialSeeder extends Seeder
{
    /** Gimnasio 0 = catálogo base del producto, visible para todos los tenants. */
    private const GIMNASIO_BASE = 0;

    private const TABLAS = [
        'evaluacion_inicial_secciones',
        'evaluacion_inicial_preguntas',
        'evaluacion_inicial_opciones',
    ];

    private const TIPOS_VALIDOS = [
        'texto',
        'textarea',
        'numero',
        'fecha',
        'si_no',
        'unica',
        'multiple',
        'escala',
    ];

    /** Cache de Schema::hasColumn para no consultar el esquema en cada fila. */
    private array $columnas = [];

    /** codigo de pregunta => id, poblado durante el pase 1. */
    private array $idsPreguntas = [];

    // ------------------------------------------------------------------
    // Ejecución
    // ------------------------------------------------------------------

    public function run(): void
    {
        $this->verificarTablas();
        $this->verificarEstructura();

        DB::transaction(function () {
            $ahora = Carbon::now();

            // --- Pase 1: secciones, preguntas y opciones ---
            foreach ($this->estructura() as $iSeccion => $seccion) {
                $seccionId = $this->upsertSeccion($seccion, $iSeccion + 1, $ahora);

                foreach ($seccion['preguntas'] as $iPregunta => $pregunta) {
                    $preguntaId = $this->upsertPregunta($pregunta, $seccionId, $iPregunta + 1, $ahora);

                    $this->idsPreguntas[$pregunta['codigo']] = $preguntaId;

                    $opciones = $pregunta['opciones'] ?? $this->opcionesPorTipo($pregunta);

                    if (! empty($pregunta['permite_otro'])) {
                        $opciones[] = [
                            'codigo'   => 'otro',
                            'etiqueta' => 'Otro (especificar)',
                            'es_otro'  => 1,
                        ];
                    }

                    foreach ($opciones as $iOpcion => $opcion) {
                        $this->upsertOpcion($opcion, $preguntaId, $iOpcion + 1, $ahora);
                    }
                }
            }

            // --- Pase 2: dependencias condicionales ---
            // Va en un segundo pase para que una pregunta pueda depender de otra
            // definida más abajo, sin importar el orden del arreglo.
            $this->resolverDependencias($ahora);
        });

        $this->informar();
    }

    // ------------------------------------------------------------------
    // Verificaciones previas (fallar temprano y con un mensaje útil)
    // ------------------------------------------------------------------

    private function verificarTablas(): void
    {
        $faltantes = array_filter(
            self::TABLAS,
            fn($tabla) => ! Schema::hasTable($tabla)
        );

        if ($faltantes !== []) {
            throw new RuntimeException(
                'Faltan tablas del módulo: ' . implode(', ', $faltantes) . '. '
                    . 'Ejecuta primero evaluacion_inicial_ddl.sql.'
            );
        }
    }

    /** Valida el arreglo antes de tocar la base de datos. */
    private function verificarEstructura(): void
    {
        $codigosPregunta = [];
        $codigosSeccion  = [];
        $errores         = [];

        foreach ($this->estructura() as $seccion) {
            if (in_array($seccion['codigo'], $codigosSeccion, true)) {
                $errores[] = "Sección duplicada: {$seccion['codigo']}";
            }
            $codigosSeccion[] = $seccion['codigo'];

            foreach ($seccion['preguntas'] as $pregunta) {
                $codigo = $pregunta['codigo'];

                if (in_array($codigo, $codigosPregunta, true)) {
                    $errores[] = "Pregunta duplicada: {$codigo}";
                }
                $codigosPregunta[] = $codigo;

                if (! in_array($pregunta['tipo'], self::TIPOS_VALIDOS, true)) {
                    $errores[] = "Tipo inválido en {$codigo}: {$pregunta['tipo']}";
                }

                if (mb_strlen($pregunta['pregunta']) > 500) {
                    $errores[] = "Enunciado sobre 500 caracteres en {$codigo}";
                }

                $necesitaOpciones = in_array($pregunta['tipo'], ['unica', 'multiple'], true);

                if ($necesitaOpciones && empty($pregunta['opciones']) && empty($pregunta['permite_otro'])) {
                    $errores[] = "La pregunta {$codigo} es de tipo {$pregunta['tipo']} y no tiene opciones";
                }

                foreach ($pregunta['opciones'] ?? [] as $opcion) {
                    if (mb_strlen($opcion['etiqueta']) > 255) {
                        $errores[] = "Etiqueta sobre 255 caracteres en {$codigo}/{$opcion['codigo']}";
                    }
                }
            }
        }

        // Las dependencias deben apuntar a preguntas que existan en el arreglo.
        foreach ($this->estructura() as $seccion) {
            foreach ($seccion['preguntas'] as $pregunta) {
                if (
                    isset($pregunta['depende_de'])
                    && ! in_array($pregunta['depende_de'], $codigosPregunta, true)
                ) {
                    $errores[] = "{$pregunta['codigo']} depende de {$pregunta['depende_de']}, que no existe";
                }
            }
        }

        if ($errores !== []) {
            throw new RuntimeException("Errores en el cuestionario:\n - " . implode("\n - ", $errores));
        }
    }

    // ------------------------------------------------------------------
    // Persistencia
    // ------------------------------------------------------------------

    /**
     * Upsert manual en vez de updateOrInsert(): así `created_at` no se
     * sobrescribe cuando la fila ya existe.
     */
    private function upsert(string $tabla, array $claves, array $datos, Carbon $ahora): int
    {
        $existente = DB::table($tabla)->where($claves)->first();

        if ($existente) {
            DB::table($tabla)
                ->where('id', $existente->id)
                ->update($datos + ['updated_at' => $ahora]);

            return (int) $existente->id;
        }

        return (int) DB::table($tabla)->insertGetId(
            $claves + $datos + ['created_at' => $ahora, 'updated_at' => $ahora]
        );
    }

    private function upsertSeccion(array $seccion, int $orden, Carbon $ahora): int
    {
        $claves = $this->conTenant('evaluacion_inicial_secciones', [
            'codigo' => $seccion['codigo'],
        ]);

        return $this->upsert('evaluacion_inicial_secciones', $claves, [
            'titulo'      => $seccion['titulo'],
            'descripcion' => $seccion['descripcion'] ?? null,
            'orden'       => $orden,
            'estado'      => 1,
        ], $ahora);
    }

    private function upsertPregunta(array $pregunta, int $seccionId, int $orden, Carbon $ahora): int
    {
        $claves = $this->conTenant('evaluacion_inicial_preguntas', [
            'codigo' => $pregunta['codigo'],
        ]);

        $datos = [
            'seccion_id'   => $seccionId,
            'pregunta'     => $pregunta['pregunta'],
            'descripcion'  => $pregunta['descripcion'] ?? null,
            'tipo'         => $pregunta['tipo'],
            'es_requerida' => $pregunta['es_requerida'] ?? 0,
            'permite_otro' => $pregunta['permite_otro'] ?? 0,
            'orden'        => $orden,
            'estado'       => 1,
        ];

        $datos = $this->conColumnasOpcionales('evaluacion_inicial_preguntas', $datos, [
            'es_sensible' => $pregunta['es_sensible'] ?? 0,
        ]);

        return $this->upsert('evaluacion_inicial_preguntas', $claves, $datos, $ahora);
    }

    private function upsertOpcion(array $opcion, int $preguntaId, int $orden, Carbon $ahora): int
    {
        $datos = [
            'etiqueta' => $opcion['etiqueta'],
            'orden'    => $orden,
            'es_otro'  => $opcion['es_otro'] ?? 0,
            'estado'   => 1,
        ];

        $datos = $this->conColumnasOpcionales('evaluacion_inicial_opciones', $datos, [
            'genera_alerta' => $opcion['alerta'] ?? 0,
            'id_gimnasio'   => self::GIMNASIO_BASE,
        ]);

        return $this->upsert('evaluacion_inicial_opciones', [
            'pregunta_id' => $preguntaId,
            'codigo'      => $opcion['codigo'],
        ], $datos, $ahora);
    }

    /**
     * Segundo pase: ahora que todas las preguntas y opciones existen, se
     * resuelven los `depende_de` a sus ids.
     */
    private function resolverDependencias(Carbon $ahora): void
    {
        if (! $this->tieneColumna('evaluacion_inicial_preguntas', 'depende_pregunta_id')) {
            return;
        }

        $usaOpcion = $this->tieneColumna('evaluacion_inicial_preguntas', 'depende_opcion_id');

        foreach ($this->estructura() as $seccion) {
            foreach ($seccion['preguntas'] as $pregunta) {
                if (empty($pregunta['depende_de'])) {
                    continue;
                }

                $padreId = $this->idsPreguntas[$pregunta['depende_de']] ?? null;

                if ($padreId === null) {
                    continue;
                }

                $datos = [
                    'depende_pregunta_id' => $padreId,
                    'updated_at'          => $ahora,
                ];

                if ($usaOpcion) {
                    $datos['depende_opcion_id'] = isset($pregunta['depende_valor'])
                        ? DB::table('evaluacion_inicial_opciones')
                        ->where('pregunta_id', $padreId)
                        ->where('codigo', $pregunta['depende_valor'])
                        ->value('id')
                        : null;
                }

                DB::table('evaluacion_inicial_preguntas')
                    ->where('id', $this->idsPreguntas[$pregunta['codigo']])
                    ->update($datos);
            }
        }
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Agrega id_gimnasio a las claves de búsqueda solo si la columna existe. */
    private function conTenant(string $tabla, array $claves): array
    {
        if ($this->tieneColumna($tabla, 'id_gimnasio')) {
            $claves['id_gimnasio'] = self::GIMNASIO_BASE;
        }

        return $claves;
    }

    /** Agrega al arreglo solo las columnas opcionales que existen en la tabla. */
    private function conColumnasOpcionales(string $tabla, array $datos, array $opcionales): array
    {
        foreach ($opcionales as $columna => $valor) {
            if ($this->tieneColumna($tabla, $columna)) {
                $datos[$columna] = $valor;
            }
        }

        return $datos;
    }

    private function tieneColumna(string $tabla, string $columna): bool
    {
        if (! isset($this->columnas[$tabla][$columna])) {
            $this->columnas[$tabla][$columna] = Schema::hasColumn($tabla, $columna);
        }

        return $this->columnas[$tabla][$columna];
    }

    /** Opciones implícitas para si_no y escala. */
    private function opcionesPorTipo(array $pregunta): array
    {
        if ($pregunta['tipo'] === 'si_no') {
            return [
                ['codigo' => 'si', 'etiqueta' => 'Sí', 'alerta' => $pregunta['alerta_si'] ?? 0],
                ['codigo' => 'no', 'etiqueta' => 'No'],
            ];
        }

        if ($pregunta['tipo'] === 'escala') {
            $desde = $pregunta['escala_desde'] ?? 1;
            $hasta = $pregunta['escala_hasta'] ?? 5;

            $opciones = [];

            foreach (range($desde, $hasta) as $n) {
                $opciones[] = ['codigo' => (string) $n, 'etiqueta' => (string) $n];
            }

            return $opciones;
        }

        return [];
    }

    private function informar(): void
    {
        if (! isset($this->command)) {
            return;
        }

        $secciones = count($this->estructura());
        $preguntas = count($this->idsPreguntas);

        $this->command->info("Evaluación inicial: {$secciones} secciones y {$preguntas} preguntas cargadas.");

        foreach (['es_sensible', 'depende_pregunta_id'] as $columna) {
            if (! $this->tieneColumna('evaluacion_inicial_preguntas', $columna)) {
                $this->command->warn("Columna `{$columna}` no existe: esa funcionalidad quedó sin cargar.");
            }
        }

        if (! $this->tieneColumna('evaluacion_inicial_opciones', 'genera_alerta')) {
            $this->command->warn('Columna `genera_alerta` no existe: las banderas rojas quedaron sin marcar.');
        }
    }

    // ------------------------------------------------------------------
    // Contenido del cuestionario
    // ------------------------------------------------------------------

    private function estructura(): array
    {
        return [

            // =========================================================
            [
                'codigo'      => 'antecedentes',
                'titulo'      => 'Antecedentes generales',
                'descripcion' => 'Datos básicos para que tu entrenador pueda contactarte y adaptar tu plan.',
                'preguntas'   => [
                    [
                        'codigo' => 'ant_fecha_nac',
                        'pregunta' => '¿Cuál es tu fecha de nacimiento?',
                        'tipo' => 'fecha',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'ant_sexo',
                        'pregunta' => 'Sexo',
                        'descripcion' => 'Se usa solo para ajustar cargas de referencia y estimaciones de composición corporal.',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => 'femenino', 'etiqueta' => 'Femenino'],
                            ['codigo' => 'masculino', 'etiqueta' => 'Masculino'],
                            ['codigo' => 'no_responde', 'etiqueta' => 'Prefiero no responder'],
                        ],
                    ],
                    [
                        'codigo' => 'ant_ocupacion',
                        'pregunta' => '¿A qué te dedicas?',
                        'tipo' => 'texto',
                    ],
                    [
                        'codigo' => 'ant_jornada',
                        'pregunta' => '¿Cómo es físicamente tu jornada de trabajo o estudio?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => 'sentado', 'etiqueta' => 'Sentado la mayor parte del día'],
                            ['codigo' => 'de_pie', 'etiqueta' => 'De pie la mayor parte del día'],
                            ['codigo' => 'esfuerzo', 'etiqueta' => 'Con esfuerzo físico (cargar, subir, caminar mucho)'],
                            ['codigo' => 'mixta', 'etiqueta' => 'Mixta'],
                            ['codigo' => 'turnos', 'etiqueta' => 'Por turnos (incluye turnos de noche)'],
                        ],
                    ],
                    [
                        'codigo' => 'ant_emergencia_nombre',
                        'pregunta' => 'Nombre de tu contacto de emergencia',
                        'tipo' => 'texto',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'ant_emergencia_fono',
                        'pregunta' => 'Teléfono de tu contacto de emergencia',
                        'tipo' => 'texto',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'ant_prevision',
                        'pregunta' => 'Previsión de salud',
                        'descripcion' => 'Solo por si necesitamos derivarte o asistirte en una urgencia.',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'fonasa', 'etiqueta' => 'Fonasa'],
                            ['codigo' => 'isapre', 'etiqueta' => 'Isapre'],
                            ['codigo' => 'otra', 'etiqueta' => 'Otra'],
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => 'no_responde', 'etiqueta' => 'Prefiero no responder'],
                        ],
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'objetivos',
                'titulo'      => 'Objetivos y experiencia',
                'descripcion' => 'Para que tu plan apunte a lo que realmente te interesa lograr.',
                'preguntas'   => [
                    [
                        'codigo' => 'obj_principal',
                        'pregunta' => '¿Cuál es tu objetivo principal?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'permite_otro' => 1,
                        'opciones' => [
                            ['codigo' => 'bajar_grasa', 'etiqueta' => 'Bajar grasa corporal'],
                            ['codigo' => 'masa_muscular', 'etiqueta' => 'Aumentar masa muscular'],
                            ['codigo' => 'condicion', 'etiqueta' => 'Mejorar mi condición física general'],
                            ['codigo' => 'salud', 'etiqueta' => 'Salud y bienestar'],
                            ['codigo' => 'rendimiento', 'etiqueta' => 'Rendimiento deportivo'],
                            ['codigo' => 'post_lesion', 'etiqueta' => 'Volver a entrenar después de una lesión'],
                            ['codigo' => 'postura', 'etiqueta' => 'Corregir postura o dolores'],
                            ['codigo' => 'habito', 'etiqueta' => 'Crear el hábito de entrenar'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_secundarios',
                        'pregunta' => '¿Hay otros objetivos que también te interesen?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'opciones' => [
                            ['codigo' => 'fuerza', 'etiqueta' => 'Ganar fuerza'],
                            ['codigo' => 'flexibilidad', 'etiqueta' => 'Ganar movilidad y flexibilidad'],
                            ['codigo' => 'resistencia', 'etiqueta' => 'Mejorar resistencia cardiovascular'],
                            ['codigo' => 'energia', 'etiqueta' => 'Tener más energía en el día'],
                            ['codigo' => 'estres', 'etiqueta' => 'Manejar el estrés'],
                            ['codigo' => 'sueno', 'etiqueta' => 'Dormir mejor'],
                            ['codigo' => 'social', 'etiqueta' => 'Entrenar acompañado / socializar'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_experiencia',
                        'pregunta' => '¿Qué experiencia previa tienes entrenando?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => 'nula', 'etiqueta' => 'Nunca he entrenado de forma regular'],
                            ['codigo' => 'inicial', 'etiqueta' => 'Menos de 6 meses en total'],
                            ['codigo' => 'intermedia', 'etiqueta' => 'Entre 6 meses y 2 años'],
                            ['codigo' => 'avanzada', 'etiqueta' => 'Más de 2 años'],
                            ['codigo' => 'retomando', 'etiqueta' => 'Entrené antes y estoy retomando'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_actividad_actual',
                        'pregunta' => 'Hoy, ¿cuántas veces por semana haces actividad física?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => '1_2', 'etiqueta' => '1 a 2 veces'],
                            ['codigo' => '3_4', 'etiqueta' => '3 a 4 veces'],
                            ['codigo' => '5_mas', 'etiqueta' => '5 veces o más'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_deportes',
                        'pregunta' => '¿Practicas algún deporte actualmente?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguno', 'etiqueta' => 'Ninguno'],
                            ['codigo' => 'futbol', 'etiqueta' => 'Fútbol'],
                            ['codigo' => 'running', 'etiqueta' => 'Running / trote'],
                            ['codigo' => 'ciclismo', 'etiqueta' => 'Ciclismo'],
                            ['codigo' => 'natacion', 'etiqueta' => 'Natación'],
                            ['codigo' => 'trekking', 'etiqueta' => 'Trekking / montaña'],
                            ['codigo' => 'artes_marciales', 'etiqueta' => 'Artes marciales'],
                            ['codigo' => 'baile', 'etiqueta' => 'Baile'],
                            ['codigo' => 'voleibol', 'etiqueta' => 'Vóleibol / básquetbol'],
                            ['codigo' => 'tenis', 'etiqueta' => 'Tenis / pádel'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_dias_disponibles',
                        'pregunta' => '¿Cuántos días a la semana puedes entrenar de forma realista?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => '2', 'etiqueta' => '2 días'],
                            ['codigo' => '3', 'etiqueta' => '3 días'],
                            ['codigo' => '4', 'etiqueta' => '4 días'],
                            ['codigo' => '5', 'etiqueta' => '5 días'],
                            ['codigo' => '6_mas', 'etiqueta' => '6 días o más'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_horarios',
                        'pregunta' => '¿En qué horarios te acomoda entrenar?',
                        'tipo' => 'multiple',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => 'temprano', 'etiqueta' => 'Muy temprano (antes de las 8:00)'],
                            ['codigo' => 'manana', 'etiqueta' => 'Mañana (8:00 a 12:00)'],
                            ['codigo' => 'mediodia', 'etiqueta' => 'Mediodía (12:00 a 15:00)'],
                            ['codigo' => 'tarde', 'etiqueta' => 'Tarde (15:00 a 19:00)'],
                            ['codigo' => 'noche', 'etiqueta' => 'Noche (después de las 19:00)'],
                        ],
                    ],
                    [
                        'codigo' => 'obj_expectativa',
                        'pregunta' => '¿Qué esperas conseguir en los próximos 3 meses?',
                        'descripcion' => 'Cuéntalo con tus palabras. Esto le sirve a tu entrenador más de lo que crees.',
                        'tipo' => 'textarea',
                    ],
                    [
                        'codigo' => 'obj_intentos_previos',
                        'pregunta' => '¿Qué te ha hecho abandonar el entrenamiento antes?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'opciones' => [
                            ['codigo' => 'nunca_abandone', 'etiqueta' => 'Nunca he abandonado'],
                            ['codigo' => 'tiempo', 'etiqueta' => 'Falta de tiempo'],
                            ['codigo' => 'motivacion', 'etiqueta' => 'Falta de motivación'],
                            ['codigo' => 'lesion', 'etiqueta' => 'Una lesión o molestia'],
                            ['codigo' => 'resultados', 'etiqueta' => 'No veía resultados'],
                            ['codigo' => 'dinero', 'etiqueta' => 'Costo'],
                            ['codigo' => 'aburrimiento', 'etiqueta' => 'Me aburría la rutina'],
                            ['codigo' => 'verguenza', 'etiqueta' => 'Me sentía incómodo/a en el gimnasio'],
                        ],
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'tamizaje',
                'titulo'      => 'Tamizaje de salud',
                'descripcion' => 'Estas siete preguntas son las que determinan si puedes comenzar directamente o si necesitamos una autorización médica antes. Respóndelas con la mayor honestidad posible.',
                'preguntas'   => [
                    [
                        'codigo' => 'par_cardiaco',
                        'pregunta' => '¿Un médico te ha dicho alguna vez que tienes una condición cardíaca o presión arterial alta?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_dolor_pecho',
                        'pregunta' => '¿Sientes dolor en el pecho en reposo, en tus actividades diarias o al hacer ejercicio?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_mareo',
                        'pregunta' => 'En los últimos 12 meses, ¿has perdido el equilibrio por mareo o has perdido la conciencia?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_condicion_cronica',
                        'pregunta' => '¿Te han diagnosticado alguna otra condición crónica, además de una condición cardíaca o presión alta?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_medicamentos_cronicos',
                        'pregunta' => '¿Tomas actualmente medicamentos recetados para una condición crónica?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_musculoesqueletico',
                        'pregunta' => '¿Tienes actualmente algún problema de huesos, articulaciones o tejidos blandos que pueda empeorar con la actividad física?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_supervision_medica',
                        'pregunta' => '¿Un médico te ha indicado alguna vez que solo realices actividad física bajo supervisión médica?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                    [
                        'codigo' => 'par_detalle',
                        'pregunta' => 'Si respondiste "Sí" a alguna de las anteriores, cuéntanos brevemente de qué se trata',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'condiciones',
                'titulo'      => 'Enfermedades de base',
                'descripcion' => 'Nada de esto te impide entrenar: sirve para adaptar la intensidad y los ejercicios a tu caso.',
                'preguntas'   => [
                    [
                        'codigo' => 'cond_diagnosticadas',
                        'pregunta' => '¿Te han diagnosticado alguna de estas condiciones?',
                        'descripcion' => 'Puedes marcar más de una.',
                        'tipo' => 'multiple',
                        'es_requerida' => 1,
                        'permite_otro' => 1,
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => 'hipertension', 'etiqueta' => 'Hipertensión arterial', 'alerta' => 1],
                            ['codigo' => 'diabetes_1', 'etiqueta' => 'Diabetes tipo 1', 'alerta' => 1],
                            ['codigo' => 'diabetes_2', 'etiqueta' => 'Diabetes tipo 2', 'alerta' => 1],
                            ['codigo' => 'resistencia_insulina', 'etiqueta' => 'Resistencia a la insulina'],
                            ['codigo' => 'colesterol', 'etiqueta' => 'Colesterol o triglicéridos altos'],
                            ['codigo' => 'cardiopatia', 'etiqueta' => 'Enfermedad cardíaca', 'alerta' => 1],
                            ['codigo' => 'arritmia', 'etiqueta' => 'Arritmia', 'alerta' => 1],
                            ['codigo' => 'asma', 'etiqueta' => 'Asma'],
                            ['codigo' => 'epoc', 'etiqueta' => 'Otra enfermedad respiratoria', 'alerta' => 1],
                            ['codigo' => 'tiroides', 'etiqueta' => 'Problemas de tiroides'],
                            ['codigo' => 'epilepsia', 'etiqueta' => 'Epilepsia', 'alerta' => 1],
                            ['codigo' => 'renal', 'etiqueta' => 'Enfermedad renal', 'alerta' => 1],
                            ['codigo' => 'hepatica', 'etiqueta' => 'Enfermedad hepática', 'alerta' => 1],
                            ['codigo' => 'anemia', 'etiqueta' => 'Anemia'],
                            ['codigo' => 'artritis', 'etiqueta' => 'Artritis o artrosis'],
                            ['codigo' => 'osteoporosis', 'etiqueta' => 'Osteoporosis u osteopenia', 'alerta' => 1],
                            ['codigo' => 'circulatorio', 'etiqueta' => 'Problemas circulatorios o várices'],
                            ['codigo' => 'migrana', 'etiqueta' => 'Migrañas frecuentes'],
                        ],
                    ],
                    [
                        'codigo' => 'cond_en_control',
                        'pregunta' => '¿Estás actualmente en control médico por alguna de ellas?',
                        'tipo' => 'si_no',
                        'es_sensible' => 1,
                        'depende_de' => 'cond_diagnosticadas',
                    ],
                    [
                        'codigo' => 'cond_tratante',
                        'pregunta' => 'Si quieres, indícanos el nombre y teléfono de tu médico tratante',
                        'descripcion' => 'Opcional. Solo se usaría en caso de emergencia o si tu entrenador necesita coordinar contigo una autorización.',
                        'tipo' => 'texto',
                        'es_sensible' => 1,
                    ],
                    [
                        'codigo' => 'cond_alergias',
                        'pregunta' => '¿Tienes alergias relevantes?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => 'medicamentos', 'etiqueta' => 'A medicamentos', 'alerta' => 1],
                            ['codigo' => 'alimentos', 'etiqueta' => 'A alimentos', 'alerta' => 1],
                            ['codigo' => 'picaduras', 'etiqueta' => 'A picaduras de insectos', 'alerta' => 1],
                            ['codigo' => 'ambientales', 'etiqueta' => 'Ambientales (polen, polvo, ácaros)'],
                            ['codigo' => 'latex', 'etiqueta' => 'Al látex'],
                        ],
                    ],
                    [
                        'codigo' => 'cond_alergias_detalle',
                        'pregunta' => 'Detalla tus alergias y qué haces si tienes una reacción',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                        'depende_de' => 'cond_alergias',
                    ],
                    [
                        'codigo' => 'cond_observaciones',
                        'pregunta' => '¿Hay algo más sobre tu salud que tu entrenador deba saber?',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'medicamentos',
                'titulo'      => 'Medicamentos y suplementos',
                'descripcion' => 'Algunos medicamentos cambian tu frecuencia cardíaca o tu tolerancia al esfuerzo, y eso afecta cómo se diseña tu rutina.',
                'preguntas'   => [
                    [
                        'codigo' => 'med_toma',
                        'pregunta' => '¿Tomas actualmente algún medicamento?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                    ],
                    [
                        'codigo' => 'med_detalle',
                        'pregunta' => '¿Cuáles?',
                        'descripcion' => 'Basta el nombre y para qué lo tomas. No necesitamos dosis.',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                        'depende_de' => 'med_toma',
                        'depende_valor' => 'si',
                    ],
                    [
                        'codigo' => 'med_efectos',
                        'pregunta' => '¿Alguno te produce mareo, taquicardia, somnolencia o fatiga?',
                        'tipo' => 'si_no',
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                        'depende_de' => 'med_toma',
                        'depende_valor' => 'si',
                    ],
                    [
                        'codigo' => 'med_suplementos',
                        'pregunta' => '¿Consumes algún suplemento?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguno', 'etiqueta' => 'Ninguno'],
                            ['codigo' => 'proteina', 'etiqueta' => 'Proteína'],
                            ['codigo' => 'creatina', 'etiqueta' => 'Creatina'],
                            ['codigo' => 'vitaminas', 'etiqueta' => 'Multivitamínicos'],
                            ['codigo' => 'omega3', 'etiqueta' => 'Omega 3'],
                            ['codigo' => 'magnesio', 'etiqueta' => 'Magnesio o electrolitos'],
                            ['codigo' => 'preentreno', 'etiqueta' => 'Pre-entreno o cafeína en cápsulas'],
                            ['codigo' => 'colageno', 'etiqueta' => 'Colágeno'],
                            ['codigo' => 'hierro', 'etiqueta' => 'Hierro'],
                        ],
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'musculoesqueletico',
                'titulo'      => 'Lesiones, huesos y articulaciones',
                'descripcion' => 'Esta sección es la que más cambia tu rutina: nos permite reemplazar ejercicios que hoy te harían daño.',
                'preguntas'   => [
                    [
                        'codigo' => 'mus_zonas_dolor',
                        'pregunta' => '¿En qué zonas sientes dolor o molestias hoy?',
                        'tipo' => 'multiple',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => 'cuello', 'etiqueta' => 'Cuello'],
                            ['codigo' => 'hombro_der', 'etiqueta' => 'Hombro derecho'],
                            ['codigo' => 'hombro_izq', 'etiqueta' => 'Hombro izquierdo'],
                            ['codigo' => 'codo_muneca', 'etiqueta' => 'Codo o muñeca'],
                            ['codigo' => 'espalda_alta', 'etiqueta' => 'Espalda alta'],
                            ['codigo' => 'lumbar', 'etiqueta' => 'Espalda baja (zona lumbar)'],
                            ['codigo' => 'cadera', 'etiqueta' => 'Cadera'],
                            ['codigo' => 'rodilla_der', 'etiqueta' => 'Rodilla derecha'],
                            ['codigo' => 'rodilla_izq', 'etiqueta' => 'Rodilla izquierda'],
                            ['codigo' => 'tobillo_pie', 'etiqueta' => 'Tobillo o pie'],
                        ],
                    ],
                    [
                        'codigo' => 'mus_frecuencia',
                        'pregunta' => '¿Con qué frecuencia aparece ese dolor?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'depende_de' => 'mus_zonas_dolor',
                        'opciones' => [
                            ['codigo' => 'esfuerzo', 'etiqueta' => 'Solo con esfuerzo intenso'],
                            ['codigo' => 'ocasional', 'etiqueta' => 'Ocasionalmente'],
                            ['codigo' => 'frecuente', 'etiqueta' => 'Varias veces por semana'],
                            ['codigo' => 'constante', 'etiqueta' => 'Es constante', 'alerta' => 1],
                            ['codigo' => 'reposo', 'etiqueta' => 'Incluso en reposo o de noche', 'alerta' => 1],
                        ],
                    ],
                    [
                        'codigo' => 'mus_intensidad',
                        'pregunta' => 'En una escala de 0 a 10, ¿qué tan intenso es ese dolor en su peor momento?',
                        'tipo' => 'escala',
                        'escala_desde' => 0,
                        'escala_hasta' => 10,
                        'es_sensible' => 1,
                        'depende_de' => 'mus_zonas_dolor',
                    ],
                    [
                        'codigo' => 'mus_lesiones_previas',
                        'pregunta' => '¿Has tenido alguna de estas lesiones?',
                        'tipo' => 'multiple',
                        'es_requerida' => 1,
                        'permite_otro' => 1,
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => 'esguince', 'etiqueta' => 'Esguince'],
                            ['codigo' => 'desgarro', 'etiqueta' => 'Desgarro muscular'],
                            ['codigo' => 'fractura', 'etiqueta' => 'Fractura'],
                            ['codigo' => 'luxacion', 'etiqueta' => 'Luxación'],
                            ['codigo' => 'tendinitis', 'etiqueta' => 'Tendinitis'],
                            ['codigo' => 'menisco', 'etiqueta' => 'Lesión de menisco'],
                            ['codigo' => 'cruzado', 'etiqueta' => 'Ligamento cruzado'],
                            ['codigo' => 'manguito', 'etiqueta' => 'Manguito rotador'],
                            ['codigo' => 'hernia_discal', 'etiqueta' => 'Hernia discal', 'alerta' => 1],
                            ['codigo' => 'lumbago', 'etiqueta' => 'Lumbago'],
                            ['codigo' => 'escoliosis', 'etiqueta' => 'Escoliosis'],
                        ],
                    ],
                    [
                        'codigo' => 'mus_lesion_cuando',
                        'pregunta' => '¿Cuándo fue la lesión más reciente y cómo ocurrió?',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                        'depende_de' => 'mus_lesiones_previas',
                    ],
                    [
                        'codigo' => 'mus_kinesiologia',
                        'pregunta' => '¿Has hecho kinesiología o fisioterapia por alguna de estas lesiones?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'no', 'etiqueta' => 'No'],
                            ['codigo' => 'termine', 'etiqueta' => 'Sí, y ya terminé el tratamiento'],
                            ['codigo' => 'en_curso', 'etiqueta' => 'Sí, y estoy en tratamiento ahora', 'alerta' => 1],
                            ['codigo' => 'abandone', 'etiqueta' => 'Empecé pero no lo terminé'],
                        ],
                    ],
                    [
                        'codigo' => 'mus_limitaciones',
                        'pregunta' => '¿Hay movimientos que hoy no puedas hacer o que te duelan?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguno', 'etiqueta' => 'Ninguno'],
                            ['codigo' => 'sentadilla', 'etiqueta' => 'Agacharme o hacer sentadilla profunda'],
                            ['codigo' => 'sobre_cabeza', 'etiqueta' => 'Levantar los brazos sobre la cabeza'],
                            ['codigo' => 'correr', 'etiqueta' => 'Correr o saltar'],
                            ['codigo' => 'peso_suelo', 'etiqueta' => 'Levantar peso desde el suelo'],
                            ['codigo' => 'empujar', 'etiqueta' => 'Empujar (flexiones, press de pecho)'],
                            ['codigo' => 'tirar', 'etiqueta' => 'Tirar (dominadas, remo)'],
                            ['codigo' => 'girar', 'etiqueta' => 'Girar el tronco'],
                            ['codigo' => 'escaleras', 'etiqueta' => 'Subir o bajar escaleras'],
                        ],
                    ],
                    [
                        'codigo' => 'mus_implantes',
                        'pregunta' => '¿Tienes prótesis, placas, tornillos u otro material quirúrgico?',
                        'tipo' => 'si_no',
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'cirugias',
                'titulo'      => 'Cirugías y hospitalizaciones',
                'descripcion' => 'Solo lo de los últimos 5 años, o lo anterior si todavía te condiciona.',
                'preguntas'   => [
                    [
                        'codigo' => 'cir_tuvo',
                        'pregunta' => '¿Has tenido alguna cirugía u hospitalización en los últimos 5 años?',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                        'es_sensible' => 1,
                    ],
                    [
                        'codigo' => 'cir_tipo',
                        'pregunta' => '¿De qué tipo?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'es_sensible' => 1,
                        'depende_de' => 'cir_tuvo',
                        'depende_valor' => 'si',
                        'opciones' => [
                            ['codigo' => 'columna', 'etiqueta' => 'Columna', 'alerta' => 1],
                            ['codigo' => 'rodilla', 'etiqueta' => 'Rodilla'],
                            ['codigo' => 'hombro', 'etiqueta' => 'Hombro'],
                            ['codigo' => 'cadera', 'etiqueta' => 'Cadera'],
                            ['codigo' => 'abdominal', 'etiqueta' => 'Abdominal'],
                            ['codigo' => 'hernia', 'etiqueta' => 'Hernia'],
                            ['codigo' => 'cesarea', 'etiqueta' => 'Cesárea'],
                            ['codigo' => 'cardiaca', 'etiqueta' => 'Cardíaca', 'alerta' => 1],
                            ['codigo' => 'bariatrica', 'etiqueta' => 'Bariátrica', 'alerta' => 1],
                            ['codigo' => 'ligamentos', 'etiqueta' => 'Ligamentos o meniscos'],
                        ],
                    ],
                    [
                        'codigo' => 'cir_fecha',
                        'pregunta' => '¿Cuándo fue la más reciente?',
                        'tipo' => 'fecha',
                        'es_sensible' => 1,
                        'depende_de' => 'cir_tuvo',
                        'depende_valor' => 'si',
                    ],
                    [
                        'codigo' => 'cir_alta',
                        'pregunta' => '¿En qué situación estás hoy respecto de esa cirugía?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'depende_de' => 'cir_tuvo',
                        'depende_valor' => 'si',
                        'opciones' => [
                            ['codigo' => 'alta_total', 'etiqueta' => 'Dado de alta, sin restricciones'],
                            ['codigo' => 'alta_parcial', 'etiqueta' => 'Dado de alta, pero con restricciones', 'alerta' => 1],
                            ['codigo' => 'recuperacion', 'etiqueta' => 'Todavía en recuperación', 'alerta' => 1],
                            ['codigo' => 'no_se', 'etiqueta' => 'No estoy seguro/a', 'alerta' => 1],
                        ],
                    ],
                    [
                        'codigo' => 'cir_restricciones',
                        'pregunta' => '¿Qué te indicó el médico que no puedes hacer?',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                        'depende_de' => 'cir_tuvo',
                        'depende_valor' => 'si',
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'habitos',
                'titulo'      => 'Hábitos y estilo de vida',
                'descripcion' => 'El descanso y el estrés determinan cuánta carga puedes tolerar por semana.',
                'preguntas'   => [
                    [
                        'codigo' => 'hab_horas_sueno',
                        'pregunta' => '¿Cuántas horas duermes habitualmente?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => 'menos_5', 'etiqueta' => 'Menos de 5 horas'],
                            ['codigo' => '5_6', 'etiqueta' => 'Entre 5 y 6 horas'],
                            ['codigo' => '7_8', 'etiqueta' => 'Entre 7 y 8 horas'],
                            ['codigo' => 'mas_8', 'etiqueta' => 'Más de 8 horas'],
                        ],
                    ],
                    [
                        'codigo' => 'hab_calidad_sueno',
                        'pregunta' => 'Del 1 al 5, ¿qué tan bien duermes?',
                        'descripcion' => '1 = muy mal, 5 = excelente',
                        'tipo' => 'escala',
                        'escala_desde' => 1,
                        'escala_hasta' => 5,
                    ],
                    [
                        'codigo' => 'hab_estres',
                        'pregunta' => 'Del 1 al 5, ¿cuánto estrés sientes en tu día a día?',
                        'descripcion' => '1 = muy poco, 5 = muchísimo',
                        'tipo' => 'escala',
                        'escala_desde' => 1,
                        'escala_hasta' => 5,
                    ],
                    [
                        'codigo' => 'hab_sentado',
                        'pregunta' => '¿Cuántas horas pasas sentado/a al día?',
                        'tipo' => 'unica',
                        'opciones' => [
                            ['codigo' => 'menos_4', 'etiqueta' => 'Menos de 4'],
                            ['codigo' => '4_8', 'etiqueta' => 'Entre 4 y 8'],
                            ['codigo' => 'mas_8', 'etiqueta' => 'Más de 8'],
                        ],
                    ],
                    [
                        'codigo' => 'hab_tabaco',
                        'pregunta' => '¿Fumas?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'no', 'etiqueta' => 'No'],
                            ['codigo' => 'ex', 'etiqueta' => 'Ya no, pero fumaba antes'],
                            ['codigo' => 'ocasional', 'etiqueta' => 'Ocasionalmente'],
                            ['codigo' => 'diario', 'etiqueta' => 'A diario'],
                            ['codigo' => 'vapeo', 'etiqueta' => 'Vapeo'],
                        ],
                    ],
                    [
                        'codigo' => 'hab_alcohol',
                        'pregunta' => '¿Con qué frecuencia consumes alcohol?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'no', 'etiqueta' => 'No consumo'],
                            ['codigo' => 'ocasional', 'etiqueta' => 'Ocasionalmente'],
                            ['codigo' => 'finde', 'etiqueta' => 'Los fines de semana'],
                            ['codigo' => 'frecuente', 'etiqueta' => 'Varias veces por semana'],
                        ],
                    ],
                    [
                        'codigo' => 'hab_agua',
                        'pregunta' => '¿Cuánta agua tomas al día, aproximadamente?',
                        'tipo' => 'unica',
                        'opciones' => [
                            ['codigo' => 'menos_1', 'etiqueta' => 'Menos de 1 litro'],
                            ['codigo' => '1_2', 'etiqueta' => 'Entre 1 y 2 litros'],
                            ['codigo' => 'mas_2', 'etiqueta' => 'Más de 2 litros'],
                            ['codigo' => 'no_se', 'etiqueta' => 'No tengo idea'],
                        ],
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'alimentacion',
                'titulo'      => 'Alimentación',
                'descripcion' => 'Estas preguntas nos permiten orientarte. Si necesitas un plan alimentario individual, te derivamos con un nutricionista.',
                'preguntas'   => [
                    [
                        'codigo' => 'ali_comidas_dia',
                        'pregunta' => '¿Cuántas comidas haces al día?',
                        'tipo' => 'unica',
                        'es_requerida' => 1,
                        'opciones' => [
                            ['codigo' => '1_2', 'etiqueta' => '1 o 2'],
                            ['codigo' => '3', 'etiqueta' => '3'],
                            ['codigo' => '4_5', 'etiqueta' => '4 o 5'],
                            ['codigo' => 'mas_5', 'etiqueta' => 'Más de 5'],
                            ['codigo' => 'irregular', 'etiqueta' => 'Muy irregular, depende del día'],
                        ],
                    ],
                    [
                        'codigo' => 'ali_desayuno',
                        'pregunta' => '¿Desayunas habitualmente?',
                        'tipo' => 'si_no',
                    ],
                    [
                        'codigo' => 'ali_tipo_dieta',
                        'pregunta' => '¿Sigues algún tipo de alimentación en particular?',
                        'tipo' => 'unica',
                        'permite_otro' => 1,
                        'opciones' => [
                            ['codigo' => 'sin_restriccion', 'etiqueta' => 'Como de todo, sin restricciones'],
                            ['codigo' => 'vegetariana', 'etiqueta' => 'Vegetariana'],
                            ['codigo' => 'vegana', 'etiqueta' => 'Vegana'],
                            ['codigo' => 'sin_gluten', 'etiqueta' => 'Sin gluten'],
                            ['codigo' => 'sin_lactosa', 'etiqueta' => 'Sin lactosa'],
                            ['codigo' => 'baja_carbo', 'etiqueta' => 'Baja en carbohidratos'],
                            ['codigo' => 'indicada', 'etiqueta' => 'Una pauta indicada por un profesional de la salud'],
                        ],
                    ],
                    [
                        'codigo' => 'ali_intolerancias',
                        'pregunta' => '¿Tienes alergias o intolerancias alimentarias?',
                        'tipo' => 'multiple',
                        'permite_otro' => 1,
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'ninguna', 'etiqueta' => 'Ninguna'],
                            ['codigo' => 'lactosa', 'etiqueta' => 'Lactosa'],
                            ['codigo' => 'gluten', 'etiqueta' => 'Gluten'],
                            ['codigo' => 'frutos_secos', 'etiqueta' => 'Frutos secos', 'alerta' => 1],
                            ['codigo' => 'mariscos', 'etiqueta' => 'Mariscos o pescado', 'alerta' => 1],
                            ['codigo' => 'huevo', 'etiqueta' => 'Huevo'],
                            ['codigo' => 'soya', 'etiqueta' => 'Soya'],
                        ],
                    ],
                    [
                        'codigo' => 'ali_fuera_casa',
                        'pregunta' => '¿Con qué frecuencia comes fuera de casa o pides delivery?',
                        'tipo' => 'unica',
                        'opciones' => [
                            ['codigo' => 'nunca', 'etiqueta' => 'Casi nunca'],
                            ['codigo' => '1_2', 'etiqueta' => '1 o 2 veces por semana'],
                            ['codigo' => '3_4', 'etiqueta' => '3 o 4 veces por semana'],
                            ['codigo' => 'casi_diario', 'etiqueta' => 'Casi todos los días'],
                        ],
                    ],
                    [
                        'codigo' => 'ali_bebidas_azucaradas',
                        'pregunta' => '¿Con qué frecuencia tomas bebidas azucaradas o jugos envasados?',
                        'tipo' => 'unica',
                        'opciones' => [
                            ['codigo' => 'nunca', 'etiqueta' => 'Nunca'],
                            ['codigo' => 'ocasional', 'etiqueta' => 'Ocasionalmente'],
                            ['codigo' => 'diario', 'etiqueta' => 'Todos los días'],
                            ['codigo' => 'varias_diario', 'etiqueta' => 'Varias veces al día'],
                        ],
                    ],
                    [
                        'codigo' => 'ali_nutricionista',
                        'pregunta' => '¿Estás o has estado en control con un nutricionista?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'opciones' => [
                            ['codigo' => 'nunca', 'etiqueta' => 'Nunca'],
                            ['codigo' => 'antes', 'etiqueta' => 'Antes sí, ahora no'],
                            ['codigo' => 'actual', 'etiqueta' => 'Sí, actualmente'],
                        ],
                    ],
                    [
                        'codigo' => 'ali_orientacion',
                        'pregunta' => '¿Te interesa recibir orientación alimentaria de parte del centro?',
                        'descripcion' => 'La orientación general la entrega tu entrenador. Un plan alimentario individual requiere derivación a nutricionista.',
                        'tipo' => 'si_no',
                    ],
                    [
                        'codigo' => 'ali_observaciones',
                        'pregunta' => '¿Algo más sobre tu alimentación que quieras contarnos?',
                        'tipo' => 'textarea',
                        'es_sensible' => 1,
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'salud_femenina',
                'titulo'      => 'Salud femenina',
                'descripcion' => 'Esta sección aparece solo si corresponde y puedes omitirla si prefieres conversarla directamente con tu entrenadora o entrenador.',
                'preguntas'   => [
                    [
                        'codigo' => 'fem_embarazo',
                        'pregunta' => '¿Estás embarazada o existe la posibilidad de que lo estés?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'depende_de' => 'ant_sexo',
                        'depende_valor' => 'femenino',
                        'opciones' => [
                            ['codigo' => 'no', 'etiqueta' => 'No'],
                            ['codigo' => 'si', 'etiqueta' => 'Sí', 'alerta' => 1],
                            ['codigo' => 'posible', 'etiqueta' => 'Es posible', 'alerta' => 1],
                            ['codigo' => 'no_responde', 'etiqueta' => 'Prefiero no responder'],
                        ],
                    ],
                    [
                        'codigo' => 'fem_semanas',
                        'pregunta' => '¿De cuántas semanas?',
                        'tipo' => 'numero',
                        'es_sensible' => 1,
                        'depende_de' => 'fem_embarazo',
                        'depende_valor' => 'si',
                    ],
                    [
                        'codigo' => 'fem_postparto',
                        'pregunta' => '¿Diste a luz en los últimos 12 meses?',
                        'tipo' => 'unica',
                        'es_sensible' => 1,
                        'depende_de' => 'ant_sexo',
                        'depende_valor' => 'femenino',
                        'opciones' => [
                            ['codigo' => 'no', 'etiqueta' => 'No'],
                            ['codigo' => 'menos_3', 'etiqueta' => 'Sí, hace menos de 3 meses', 'alerta' => 1],
                            ['codigo' => '3_6', 'etiqueta' => 'Sí, hace 3 a 6 meses', 'alerta' => 1],
                            ['codigo' => '6_12', 'etiqueta' => 'Sí, hace 6 a 12 meses'],
                        ],
                    ],
                    [
                        'codigo' => 'fem_alta_postparto',
                        'pregunta' => '¿Tienes alta médica para hacer ejercicio?',
                        'tipo' => 'si_no',
                        'es_sensible' => 1,
                        'depende_de' => 'fem_postparto',
                    ],
                    [
                        'codigo' => 'fem_piso_pelvico',
                        'pregunta' => '¿Tienes molestias o pérdidas de orina al toser, saltar o correr?',
                        'descripcion' => 'Es más común de lo que se cree y se trabaja. Saberlo nos permite elegir mejor los ejercicios de impacto.',
                        'tipo' => 'si_no',
                        'es_sensible' => 1,
                        'alerta_si' => 1,
                        'depende_de' => 'ant_sexo',
                        'depende_valor' => 'femenino',
                    ],
                ],
            ],

            // =========================================================
            [
                'codigo'      => 'consentimiento',
                'titulo'      => 'Declaración y consentimiento',
                'descripcion' => 'Necesitamos tu autorización expresa para tratar la información de salud que acabas de entregar.',
                'preguntas'   => [
                    [
                        'codigo' => 'con_veracidad',
                        'pregunta' => 'Declaro que la información entregada es veraz y completa según mi mejor conocimiento.',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'con_datos_salud',
                        'pregunta' => 'Autorizo expresamente que mis datos de salud sean tratados con la finalidad exclusiva de diseñar y supervisar mi plan de entrenamiento.',
                        'descripcion' => 'Puedes revocar esta autorización en cualquier momento desde tu perfil o solicitándolo en recepción.',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'con_derivacion',
                        'pregunta' => 'Entiendo que, según mis respuestas, se me puede solicitar una autorización médica antes de comenzar a entrenar.',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'con_informar_cambios',
                        'pregunta' => 'Me comprometo a informar cualquier cambio en mi estado de salud, lesión o medicamento.',
                        'tipo' => 'si_no',
                        'es_requerida' => 1,
                    ],
                    [
                        'codigo' => 'con_imagen',
                        'pregunta' => 'Autorizo el uso de fotografías de mi progreso en las redes sociales del centro.',
                        'descripcion' => 'Opcional e independiente de lo anterior. Puedes entrenar igual si respondes que no.',
                        'tipo' => 'si_no',
                    ],
                    [
                        'codigo' => 'con_nombre',
                        'pregunta' => 'Escribe tu nombre completo para firmar esta declaración',
                        'tipo' => 'texto',
                        'es_requerida' => 1,
                    ],
                ],
            ],

        ];
    }
}
