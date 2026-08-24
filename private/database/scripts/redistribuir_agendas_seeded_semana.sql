START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_seeded_agenda_schedule;

CREATE TEMPORARY TABLE tmp_seeded_agenda_schedule AS
SELECT
    base.id,
    TIMESTAMP(
        DATE_ADD(base.monday_of_week, INTERVAL MOD(base.trainer_slot + base.client_slot + base.sequence_slot, 6) DAY),
        MAKETIME(
            7 + MOD((base.trainer_slot * 2) + base.client_slot + FLOOR(base.sequence_slot / 2), 11),
            CASE
                WHEN MOD(base.trainer_slot + base.client_slot + base.sequence_slot, 2) = 0 THEN 0
                ELSE 30
            END,
            0
        )
    ) AS new_start,
    CASE
        WHEN base.duration_minutes > 0 THEN base.duration_minutes
        ELSE 60
    END AS duration_minutes
FROM (
    SELECT
        a.id,
        DATE_SUB(DATE(a.fecha_inicio), INTERVAL WEEKDAY(a.fecha_inicio) DAY) AS monday_of_week,
        TIMESTAMPDIFF(MINUTE, a.fecha_inicio, a.fecha_fin) AS duration_minutes,
        MOD(GREATEST(a.id_usuario - 2, 0), 6) AS trainer_slot,
        (
            SELECT COUNT(*)
            FROM clientes c2
            WHERE c2.id_usuario = c.id_usuario
              AND c2.id <= c.id
        ) - 1 AS client_slot,
        CASE
            WHEN a.slug LIKE 'rutina-pasada-seeder-%' THEN 10 - CAST(SUBSTRING_INDEX(a.slug, '-', -1) AS UNSIGNED)
            ELSE CAST(SUBSTRING_INDEX(a.slug, '-', -1) AS UNSIGNED) - 1
        END AS sequence_slot
    FROM agendas a
    INNER JOIN clientes c ON c.id = a.id_cliente
    WHERE a.slug LIKE 'rutina-pasada-seeder-%'
       OR a.slug LIKE 'rutina-futura-seeder-%'
) base;

UPDATE agendas a
INNER JOIN tmp_seeded_agenda_schedule s ON s.id = a.id
SET
    a.fecha_inicio = s.new_start,
    a.fecha_fin = DATE_ADD(s.new_start, INTERVAL s.duration_minutes MINUTE),
    a.updated_at = NOW();

SELECT
    a.id_usuario AS entrenador_id,
    DAYNAME(a.fecha_inicio) AS dia,
    COUNT(*) AS total_agendas
FROM agendas a
WHERE a.slug LIKE 'rutina-pasada-seeder-%'
   OR a.slug LIKE 'rutina-futura-seeder-%'
GROUP BY a.id_usuario, DAYNAME(a.fecha_inicio)
ORDER BY a.id_usuario,
    FIELD(DAYNAME(a.fecha_inicio), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

COMMIT;