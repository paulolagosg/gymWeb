<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_inicial_secciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('evaluacion_inicial_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('evaluacion_inicial_secciones')->cascadeOnDelete();
            $table->string('codigo')->unique();
            $table->string('pregunta', 500);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 20);
            $table->boolean('es_requerida')->default(true);
            $table->boolean('permite_otro')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('evaluacion_inicial_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('evaluacion_inicial_preguntas')->cascadeOnDelete();
            $table->string('codigo')->nullable();
            $table->string('etiqueta');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('es_otro')->default(false);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('evaluaciones_iniciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->unique()->constrained('clientes')->cascadeOnDelete();
            $table->timestamp('completada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluacion_inicial_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_inicial_id')->constrained('evaluaciones_iniciales')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('evaluacion_inicial_preguntas')->cascadeOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('evaluacion_inicial_opciones')->nullOnDelete();
            $table->text('valor_texto')->nullable();
            $table->timestamps();
        });

        $now = now();

        $sections = [
            [
                'codigo' => 'experiencia_actividad_previa',
                'titulo' => '1. Nivel de experiencia y actividad previa',
                'descripcion' => 'Contexto general sobre experiencia entrenando y actividad física reciente.',
                'orden' => 1,
            ],
            [
                'codigo' => 'salud_musculoesqueletica_lesiones',
                'titulo' => '2. Salud musculoesquelética y lesiones',
                'descripcion' => 'Molestias, lesiones previas y limitaciones de movimiento relevantes para entrenar.',
                'orden' => 2,
            ],
            [
                'codigo' => 'condiciones_medicas_comunes',
                'titulo' => '3. Condiciones médicas comunes',
                'descripcion' => 'Condiciones frecuentes, medicación y necesidad de autorización médica.',
                'orden' => 3,
            ],
            [
                'codigo' => 'habitos_vida_recuperacion',
                'titulo' => '4. Hábitos de vida y recuperación',
                'descripcion' => 'Sueño, estrés, trabajo y hábitos que impactan la recuperación.',
                'orden' => 4,
            ],
            [
                'codigo' => 'preferencias_logistica',
                'titulo' => '5. Preferencias y logística',
                'descripcion' => 'Disponibilidad horaria, formato preferido y foco principal del entrenamiento.',
                'orden' => 5,
            ],
            [
                'codigo' => 'motivacion_barreras',
                'titulo' => '6. Motivación y barreras',
                'descripcion' => 'Motivaciones, obstáculos percibidos y comentarios iniciales para el entrenador.',
                'orden' => 6,
            ],
        ];

        DB::table('evaluacion_inicial_secciones')->insert(array_map(
            fn(array $section) => array_merge($section, ['estado' => true, 'created_at' => $now, 'updated_at' => $now]),
            $sections,
        ));

        $sectionIds = DB::table('evaluacion_inicial_secciones')->pluck('id', 'codigo');

        $questions = [
            ['section' => 'experiencia_actividad_previa', 'codigo' => 'nivel_experiencia', 'pregunta' => '¿Cuál describe mejor tu experiencia previa entrenando?', 'tipo' => 'single', 'orden' => 1, 'permite_otro' => true],
            ['section' => 'experiencia_actividad_previa', 'codigo' => 'nivel_actividad_actual', 'pregunta' => '¿Cómo describirías tu nivel actual de actividad física?', 'tipo' => 'single', 'orden' => 2, 'permite_otro' => true],
            ['section' => 'experiencia_actividad_previa', 'codigo' => 'disciplinas_previas', 'pregunta' => '¿Qué disciplinas o actividades has practicado antes?', 'tipo' => 'multiple', 'orden' => 3, 'permite_otro' => true],
            ['section' => 'experiencia_actividad_previa', 'codigo' => 'tiempo_inactividad', 'pregunta' => 'Si has dejado de entrenar, ¿cuánto tiempo llevas sin una rutina estable?', 'tipo' => 'single', 'orden' => 4, 'permite_otro' => true],
            ['section' => 'salud_musculoesqueletica_lesiones', 'codigo' => 'zonas_molestia_actual', 'pregunta' => '¿Tienes molestias o dolor actual en alguna zona del cuerpo?', 'tipo' => 'multiple', 'orden' => 1, 'permite_otro' => true],
            ['section' => 'salud_musculoesqueletica_lesiones', 'codigo' => 'lesiones_previas', 'pregunta' => '¿Has tenido lesiones importantes anteriormente?', 'tipo' => 'multiple', 'orden' => 2, 'permite_otro' => true],
            ['section' => 'salud_musculoesqueletica_lesiones', 'codigo' => 'cirugias_previas', 'pregunta' => '¿Has tenido cirugías que debamos considerar?', 'tipo' => 'single', 'orden' => 3, 'permite_otro' => true],
            ['section' => 'salud_musculoesqueletica_lesiones', 'codigo' => 'limitaciones_movimiento', 'pregunta' => '¿Notas limitaciones en algún tipo de movimiento?', 'tipo' => 'multiple', 'orden' => 4, 'permite_otro' => true],
            ['section' => 'condiciones_medicas_comunes', 'codigo' => 'condiciones_medicas', 'pregunta' => '¿Tienes alguna condición médica diagnosticada que debamos considerar?', 'tipo' => 'multiple', 'orden' => 1, 'permite_otro' => true],
            ['section' => 'condiciones_medicas_comunes', 'codigo' => 'uso_medicacion', 'pregunta' => '¿Tomas medicación actualmente?', 'tipo' => 'single', 'orden' => 2, 'permite_otro' => true],
            ['section' => 'condiciones_medicas_comunes', 'codigo' => 'autorizacion_medica', 'pregunta' => 'Respecto a la autorización médica para entrenar, ¿en qué situación estás?', 'tipo' => 'single', 'orden' => 3, 'permite_otro' => true],
            ['section' => 'habitos_vida_recuperacion', 'codigo' => 'horas_sueno', 'pregunta' => 'En promedio, ¿cuántas horas duermes por noche?', 'tipo' => 'single', 'orden' => 1, 'permite_otro' => true],
            ['section' => 'habitos_vida_recuperacion', 'codigo' => 'nivel_estres', 'pregunta' => '¿Cómo percibes tu nivel de estrés actual?', 'tipo' => 'single', 'orden' => 2, 'permite_otro' => true],
            ['section' => 'habitos_vida_recuperacion', 'codigo' => 'actividad_laboral', 'pregunta' => '¿Cómo describirías tu actividad laboral o del día a día?', 'tipo' => 'single', 'orden' => 3, 'permite_otro' => true],
            ['section' => 'habitos_vida_recuperacion', 'codigo' => 'habitos_recuperacion', 'pregunta' => '¿Qué hábitos de recuperación sueles mantener?', 'tipo' => 'multiple', 'orden' => 4, 'permite_otro' => true],
            ['section' => 'preferencias_logistica', 'codigo' => 'horarios_preferidos', 'pregunta' => '¿En qué horarios te acomoda más entrenar?', 'tipo' => 'multiple', 'orden' => 1, 'permite_otro' => true],
            ['section' => 'preferencias_logistica', 'codigo' => 'modalidad_preferida', 'pregunta' => '¿Qué modalidad prefieres para entrenar?', 'tipo' => 'single', 'orden' => 2, 'permite_otro' => true],
            ['section' => 'preferencias_logistica', 'codigo' => 'foco_preferido', 'pregunta' => '¿Qué foco te interesa priorizar al inicio?', 'tipo' => 'multiple', 'orden' => 3, 'permite_otro' => true],
            ['section' => 'motivacion_barreras', 'codigo' => 'motivaciones_principales', 'pregunta' => '¿Qué te motiva principalmente a comenzar o retomar el entrenamiento?', 'tipo' => 'multiple', 'orden' => 1, 'permite_otro' => true],
            ['section' => 'motivacion_barreras', 'codigo' => 'barreras_percibidas', 'pregunta' => '¿Qué barreras sientes que podrían dificultar tu constancia?', 'tipo' => 'multiple', 'orden' => 2, 'permite_otro' => true],
            ['section' => 'motivacion_barreras', 'codigo' => 'nivel_compromiso', 'pregunta' => '¿Cómo describirías tu disposición actual para empezar?', 'tipo' => 'single', 'orden' => 3, 'permite_otro' => true],
            ['section' => 'motivacion_barreras', 'codigo' => 'comentarios_entrenador', 'pregunta' => '¿Hay algo más que quieras que tu entrenador sepa antes de empezar?', 'tipo' => 'text', 'orden' => 4, 'permite_otro' => false, 'es_requerida' => false],
        ];

        DB::table('evaluacion_inicial_preguntas')->insert(array_map(function (array $question) use ($sectionIds, $now) {
            return [
                'seccion_id' => $sectionIds[$question['section']],
                'codigo' => $question['codigo'],
                'pregunta' => $question['pregunta'],
                'descripcion' => $question['descripcion'] ?? null,
                'tipo' => $question['tipo'],
                'es_requerida' => $question['es_requerida'] ?? true,
                'permite_otro' => $question['permite_otro'],
                'orden' => $question['orden'],
                'estado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $questions));

        $questionIds = DB::table('evaluacion_inicial_preguntas')->pluck('id', 'codigo');

        $optionsByQuestion = [
            'nivel_experiencia' => ['Nunca he entrenado de forma estructurada', 'He entrenado antes, pero de forma esporádica', 'Entreno de forma regular hace menos de 1 año', 'Entreno de forma regular hace más de 1 año'],
            'nivel_actividad_actual' => ['Sedentario/a', '1 a 2 días por semana', '3 a 4 días por semana', '5 o más días por semana'],
            'disciplinas_previas' => ['Musculación', 'Running', 'Deportes de equipo', 'Yoga o pilates', 'Cross training'],
            'tiempo_inactividad' => ['Sin pausa relevante', '1 a 3 meses', '3 a 6 meses', '6 a 12 meses', 'Más de 1 año'],
            'zonas_molestia_actual' => ['Cuello u hombros', 'Espalda', 'Caderas', 'Rodillas', 'Tobillos o pies', 'Ninguna'],
            'lesiones_previas' => ['Esguinces', 'Tendinitis', 'Desgarros musculares', 'Hernias', 'Fracturas', 'Ninguna'],
            'cirugias_previas' => ['No', 'Sí, articular', 'Sí, muscular', 'Sí, de columna'],
            'limitaciones_movimiento' => ['No tengo limitaciones', 'Al agacharme', 'Al empujar o tirar', 'Al cargar peso', 'Al hacer movimientos por sobre la cabeza'],
            'condiciones_medicas' => ['Hipertensión', 'Diabetes', 'Asma', 'Hipotiroidismo', 'Embarazo o postparto', 'Colesterol alto', 'Ninguna'],
            'uso_medicacion' => ['No', 'Sí, ocasionalmente', 'Sí, de forma diaria'],
            'autorizacion_medica' => ['No la necesito', 'Sí, ya la tengo', 'Me la solicitaron', 'Estoy en evaluación'],
            'horas_sueno' => ['Menos de 5 horas', '5 a 6 horas', '6 a 7 horas', '7 a 8 horas', 'Más de 8 horas'],
            'nivel_estres' => ['Bajo', 'Medio', 'Alto', 'Muy alto'],
            'actividad_laboral' => ['Mayormente sentado/a', 'Mayormente de pie', 'Trabajo físico moderado', 'Trabajo físico intenso', 'Turnos rotativos'],
            'habitos_recuperacion' => ['Hidratación adecuada', 'Estiramientos', 'Movilidad', 'Pausas activas', 'Ninguno'],
            'horarios_preferidos' => ['Mañana', 'Mediodía', 'Tarde', 'Noche', 'Fin de semana'],
            'modalidad_preferida' => ['Presencial individual', 'Presencial dúo', 'Grupal', 'Plan mixto'],
            'foco_preferido' => ['Fuerza', 'Pérdida de grasa', 'Resistencia', 'Movilidad', 'Salud general'],
            'motivaciones_principales' => ['Salud', 'Energía', 'Estética', 'Rendimiento', 'Reducir dolor', 'Crear hábito'],
            'barreras_percibidas' => ['Falta de tiempo', 'Dolor o molestias', 'Falta de motivación', 'Inseguridad al entrenar', 'Organización', 'Ninguna'],
            'nivel_compromiso' => ['Estoy listo/a para empezar', 'Necesito acompañamiento cercano', 'Quiero probar primero', 'Tengo dudas todavía'],
        ];

        foreach ($optionsByQuestion as $questionCode => $labels) {
            $questionId = $questionIds[$questionCode];
            $rows = [];

            foreach (array_values($labels) as $index => $label) {
                $rows[] = [
                    'pregunta_id' => $questionId,
                    'codigo' => Str::slug($label, '_'),
                    'etiqueta' => $label,
                    'orden' => $index + 1,
                    'es_otro' => false,
                    'estado' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $rows[] = [
                'pregunta_id' => $questionId,
                'codigo' => 'otro',
                'etiqueta' => 'Otro/a',
                'orden' => count($labels) + 1,
                'es_otro' => true,
                'estado' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            DB::table('evaluacion_inicial_opciones')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_inicial_respuestas');
        Schema::dropIfExists('evaluaciones_iniciales');
        Schema::dropIfExists('evaluacion_inicial_opciones');
        Schema::dropIfExists('evaluacion_inicial_preguntas');
        Schema::dropIfExists('evaluacion_inicial_secciones');
    }
};
