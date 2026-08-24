START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_seeded_exercise_pool;
DROP TEMPORARY TABLE IF EXISTS tmp_seeded_available_groups;
DROP TEMPORARY TABLE IF EXISTS tmp_seeded_group_exercises;
DROP TEMPORARY TABLE IF EXISTS tmp_seeded_agenda_rows;
DROP TEMPORARY TABLE IF EXISTS tmp_seeded_targets;

CREATE TEMPORARY TABLE tmp_seeded_exercise_pool AS
SELECT DISTINCT
    src.ejercicio_id,
    src.id_grupo,
    src.grupo_nombre
FROM (
    SELECT
        e.id AS ejercicio_id,
        COALESCE(gm.id, gm_inferido.id) AS id_grupo,
        COALESCE(gm.nombre, gm_inferido.nombre) AS grupo_nombre
    FROM ejercicios e
    LEFT JOIN tipos_ejercicios te ON te.id = e.id_tipo
    LEFT JOIN grupos_musculares gm ON gm.id = te.id_grupo
    LEFT JOIN grupos_musculares gm_inferido ON gm_inferido.nombre = CASE
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'glute|hip thrust|puente de gluteo' THEN 'Gluteos'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'pierna|sentadilla|prensa|zancada|femoral|extension de pierna|peso muerto' THEN 'Pierna'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'pecho|press de pecho|apertura|flexion' THEN 'Pecho'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'espalda|remo|jalon|dominada|pull' THEN 'Espalda'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'hombro|elevacion lateral|press militar|arnold press' THEN 'Hombro'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'brazo|bice|trice|curl|extension de triceps|fondos' THEN 'Brazos'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'core|abd|plancha' THEN 'Core'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'cardio|bicicleta|caminata|trote|eliptica' THEN 'Cardio'
        WHEN LOWER(CONCAT_WS(' ', COALESCE(te.nombre, ''), COALESCE(e.nombre, ''))) REGEXP 'full body|fullbody' THEN 'Full Body'
        ELSE NULL
    END
    WHERE COALESCE(e.estado, 1) = 1
      AND COALESCE(gm.id, gm_inferido.id) IS NOT NULL
) src;

SET @group_pos := 0;

CREATE TEMPORARY TABLE tmp_seeded_available_groups AS
SELECT
    (@group_pos := @group_pos + 1) AS group_pos,
    base.id_grupo,
    base.grupo_nombre,
    base.exercise_count
FROM (
    SELECT
        ep.id_grupo,
        ep.grupo_nombre,
        COUNT(*) AS exercise_count
    FROM tmp_seeded_exercise_pool ep
    GROUP BY ep.id_grupo, ep.grupo_nombre
    HAVING COUNT(*) > 0
    ORDER BY FIELD(ep.grupo_nombre, 'Pierna', 'Gluteos', 'Pecho', 'Espalda', 'Hombro', 'Brazos', 'Core', 'Cardio', 'Full Body'),
        ep.grupo_nombre,
        ep.id_grupo
) base;

SET @prev_group := 0;
SET @exercise_pos := 0;

CREATE TEMPORARY TABLE tmp_seeded_group_exercises AS
SELECT
    ordered.ejercicio_id,
    ordered.id_grupo,
    ordered.grupo_nombre,
    ordered.exercise_pos
FROM (
    SELECT
        ep.ejercicio_id,
        ep.id_grupo,
        ep.grupo_nombre,
        @exercise_pos := IF(@prev_group = ep.id_grupo, @exercise_pos + 1, 1) AS exercise_pos,
        @prev_group := ep.id_grupo AS _prev_group_marker
    FROM tmp_seeded_exercise_pool ep
    ORDER BY FIELD(ep.grupo_nombre, 'Pierna', 'Gluteos', 'Pecho', 'Espalda', 'Hombro', 'Brazos', 'Core', 'Cardio', 'Full Body'),
        ep.grupo_nombre,
        ep.id_grupo,
        ep.ejercicio_id
) ordered;

SET @prev_agenda := 0;
SET @exercise_slot := 0;
SET @total_groups := 0;

CREATE TEMPORARY TABLE tmp_seeded_agenda_rows AS
SELECT
    ordered.agenda_ejercicio_id,
    ordered.id_agenda,
    ordered.id_usuario,
    ordered.id_cliente,
    ordered.slug,
    ordered.exercise_slot,
    ordered.trainer_slot,
    ordered.client_slot,
    ordered.sequence_slot
FROM (
    SELECT
        ae.id AS agenda_ejercicio_id,
        a.id AS id_agenda,
        a.id_usuario,
        a.id_cliente,
        a.slug,
        @exercise_slot := IF(@prev_agenda = a.id, @exercise_slot + 1, 1) AS exercise_slot,
        @prev_agenda := a.id AS _prev_agenda_marker,
        MOD(GREATEST(a.id_usuario - 2, 0), 6) AS trainer_slot,
        (
            SELECT COUNT(*)
            FROM clientes c2
            WHERE c2.id_usuario = c.id_usuario
              AND c2.id <= c.id
        ) - 1 AS client_slot,
        CASE
            WHEN a.slug LIKE 'rutina-pasada-seeder-%' THEN CAST(SUBSTRING_INDEX(a.slug, '-', -1) AS UNSIGNED) - 1
            ELSE 10 + CAST(SUBSTRING_INDEX(a.slug, '-', -1) AS UNSIGNED) - 1
        END AS sequence_slot
    FROM agendas_ejercicios ae
    INNER JOIN agendas a ON a.id = ae.id_agenda
    INNER JOIN clientes c ON c.id = a.id_cliente
    WHERE a.slug LIKE 'rutina-pasada-seeder-%'
       OR a.slug LIKE 'rutina-futura-seeder-%'
    ORDER BY a.id, ae.id
) ordered;

SELECT COUNT(*) INTO @total_groups
FROM tmp_seeded_available_groups;

CREATE TEMPORARY TABLE tmp_seeded_targets AS
SELECT
    agenda_rows.agenda_ejercicio_id,
    agenda_rows.id_agenda,
    available_groups.id_grupo AS target_group_id,
    available_groups.grupo_nombre AS target_group_name,
    exercises.ejercicio_id AS new_ejercicio_id
FROM tmp_seeded_agenda_rows agenda_rows
INNER JOIN tmp_seeded_available_groups available_groups ON available_groups.group_pos = 1 + MOD(
    (agenda_rows.trainer_slot * 11) + (agenda_rows.client_slot * 5) + (agenda_rows.sequence_slot * 3) + (agenda_rows.exercise_slot - 1),
    @total_groups
)
INNER JOIN tmp_seeded_group_exercises exercises ON exercises.id_grupo = available_groups.id_grupo
    AND exercises.exercise_pos = 1 + MOD(
        (agenda_rows.trainer_slot * 7) + (agenda_rows.client_slot * 3) + agenda_rows.sequence_slot + (agenda_rows.exercise_slot - 1),
        available_groups.exercise_count
    );

UPDATE agendas_ejercicios ae
INNER JOIN tmp_seeded_targets t ON t.agenda_ejercicio_id = ae.id
SET
    ae.id_ejercicio = t.new_ejercicio_id,
    ae.updated_at = NOW();

SELECT
    a.id_usuario AS entrenador_id,
    gm.nombre AS grupo_muscular,
    COUNT(*) AS total_ejercicios
FROM agendas_ejercicios ae
INNER JOIN agendas a ON a.id = ae.id_agenda
INNER JOIN ejercicios e ON e.id = ae.id_ejercicio
LEFT JOIN tipos_ejercicios te ON te.id = e.id_tipo
LEFT JOIN grupos_musculares gm ON gm.id = te.id_grupo
WHERE a.slug LIKE 'rutina-pasada-seeder-%'
   OR a.slug LIKE 'rutina-futura-seeder-%'
GROUP BY a.id_usuario, gm.nombre
ORDER BY a.id_usuario,
    FIELD(gm.nombre, 'Pierna', 'Gluteos', 'Pecho', 'Espalda', 'Hombro', 'Brazos', 'Core', 'Cardio', 'Full Body'),
    gm.nombre;

COMMIT;