-- ============================================================================
-- SCRIPT: UPSERT DE PLANES NUTRICIONALES + DÍAS + COMIDAS
-- ============================================================================
-- Descripción:
-- 1) Si el cliente activo ya tiene plan activo, lo actualiza.
-- 2) Si no tiene plan activo, lo crea.
-- 3) Garantiza 7 días (Lunes-Domingo) por plan.
-- 4) Garantiza 6 comidas por día, sin duplicar.
-- 5) Evita error 1137 (Can't reopen table) separando reportes en consultas independientes.
-- Fecha: 2026-05-01
-- ============================================================================

START TRANSACTION;

SET @script_ts = NOW(6);

DROP TEMPORARY TABLE IF EXISTS tmp_clientes_objetivo;
CREATE TEMPORARY TABLE tmp_clientes_objetivo (
    id_cliente BIGINT PRIMARY KEY,
    nombre_plan VARCHAR(255),
    objetivo_nutricional TEXT,
    fecha_desde DATE,
    fecha_hasta DATE
);

INSERT INTO tmp_clientes_objetivo (id_cliente, nombre_plan, objetivo_nutricional, fecha_desde, fecha_hasta)
SELECT
    c.id,
    CONCAT('Plan Nutricional - ', c.nombres, ' ', c.paterno),
    CASE MOD(c.id, 4)
        WHEN 0 THEN 'Pérdida de peso y mejora de composición corporal'
        WHEN 1 THEN 'Ganancia muscular y aumento de volumen'
        WHEN 2 THEN 'Mantenimiento del peso y tonificación'
        WHEN 3 THEN 'Mejora de rendimiento deportivo y resistencia'
    END,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
FROM clientes c
WHERE c.estado = 1;

DROP TEMPORARY TABLE IF EXISTS tmp_planes_objetivo;
CREATE TEMPORARY TABLE tmp_planes_objetivo (
    id_cliente BIGINT PRIMARY KEY,
    id_plan BIGINT,
    accion ENUM('creado','actualizado')
);

DROP TEMPORARY TABLE IF EXISTS tmp_planes_existentes;
CREATE TEMPORARY TABLE tmp_planes_existentes (
    id_cliente BIGINT PRIMARY KEY,
    id_plan BIGINT
);

INSERT INTO tmp_planes_existentes (id_cliente, id_plan)
SELECT
    pa.id_cliente,
    MAX(pa.id) AS id_plan
FROM planes_alimentacion pa
INNER JOIN tmp_clientes_objetivo tco ON tco.id_cliente = pa.id_cliente
WHERE pa.estado = 'activo'
GROUP BY pa.id_cliente;

UPDATE planes_alimentacion pa
INNER JOIN tmp_planes_existentes tpe ON tpe.id_plan = pa.id
INNER JOIN tmp_clientes_objetivo tco ON tco.id_cliente = pa.id_cliente
SET
    pa.nombre = tco.nombre_plan,
    pa.objetivo_nutricional = tco.objetivo_nutricional,
    pa.fecha_desde = tco.fecha_desde,
    pa.fecha_hasta = tco.fecha_hasta,
    pa.version = COALESCE(pa.version, 1),
    pa.updated_at = @script_ts;

INSERT INTO tmp_planes_objetivo (id_cliente, id_plan, accion)
SELECT
    tpe.id_cliente,
    tpe.id_plan,
    'actualizado'
FROM tmp_planes_existentes tpe;

INSERT INTO planes_alimentacion (
    id_cliente,
    nombre,
    objetivo_nutricional,
    fecha_desde,
    fecha_hasta,
    estado,
    version,
    created_at,
    updated_at
)
SELECT
    tco.id_cliente,
    tco.nombre_plan,
    tco.objetivo_nutricional,
    tco.fecha_desde,
    tco.fecha_hasta,
    'activo',
    1,
    @script_ts,
    @script_ts
FROM tmp_clientes_objetivo tco
LEFT JOIN tmp_planes_existentes tpe ON tpe.id_cliente = tco.id_cliente
WHERE tpe.id_plan IS NULL;

INSERT INTO tmp_planes_objetivo (id_cliente, id_plan, accion)
SELECT
    pa.id_cliente,
    pa.id,
    'creado'
FROM planes_alimentacion pa
WHERE pa.created_at = @script_ts
    AND pa.estado = 'activo';

INSERT INTO planes_alimentacion_dias (
    id_plan_alimentacion,
    dia_semana,
    nombre_dia,
    orden,
    observaciones,
    created_at,
    updated_at
)
SELECT
    tpo.id_plan,
    dias.numero_dia,
    dias.nombre_dia,
    dias.numero_dia,
    CASE dias.numero_dia
        WHEN 1 THEN 'Inicio de semana - Enfoque en proteína'
        WHEN 2 THEN 'Día de carbohidratos moderados'
        WHEN 3 THEN 'Miércoles de proteína y grasas saludables'
        WHEN 4 THEN 'Jueves con énfasis en verduras'
        WHEN 5 THEN 'Viernes variado'
        WHEN 6 THEN 'Sábado de descanso relativo'
        WHEN 7 THEN 'Domingo - Planificación para la semana'
    END,
    @script_ts,
    @script_ts
FROM tmp_planes_objetivo tpo
CROSS JOIN (
    SELECT 1 AS numero_dia, 'Lunes' AS nombre_dia
    UNION ALL SELECT 2, 'Martes'
    UNION ALL SELECT 3, 'Miércoles'
    UNION ALL SELECT 4, 'Jueves'
    UNION ALL SELECT 5, 'Viernes'
    UNION ALL SELECT 6, 'Sábado'
    UNION ALL SELECT 7, 'Domingo'
) dias
LEFT JOIN planes_alimentacion_dias pad
    ON pad.id_plan_alimentacion = tpo.id_plan
   AND pad.dia_semana = dias.numero_dia
WHERE pad.id IS NULL;

UPDATE planes_alimentacion_dias pad
INNER JOIN tmp_planes_objetivo tpo ON tpo.id_plan = pad.id_plan_alimentacion
SET
    pad.nombre_dia = CASE pad.dia_semana
        WHEN 1 THEN 'Lunes'
        WHEN 2 THEN 'Martes'
        WHEN 3 THEN 'Miércoles'
        WHEN 4 THEN 'Jueves'
        WHEN 5 THEN 'Viernes'
        WHEN 6 THEN 'Sábado'
        WHEN 7 THEN 'Domingo'
    END,
    pad.orden = pad.dia_semana,
    pad.observaciones = CASE pad.dia_semana
        WHEN 1 THEN 'Inicio de semana - Enfoque en proteína'
        WHEN 2 THEN 'Día de carbohidratos moderados'
        WHEN 3 THEN 'Miércoles de proteína y grasas saludables'
        WHEN 4 THEN 'Jueves con énfasis en verduras'
        WHEN 5 THEN 'Viernes variado'
        WHEN 6 THEN 'Sábado de descanso relativo'
        WHEN 7 THEN 'Domingo - Planificación para la semana'
    END,
    pad.updated_at = @script_ts
WHERE pad.dia_semana BETWEEN 1 AND 7;

UPDATE planes_alimentacion_comidas pac
INNER JOIN planes_alimentacion_dias pad ON pad.id = pac.id_plan_alimentacion_dia
INNER JOIN tmp_planes_objetivo tpo ON tpo.id_plan = pad.id_plan_alimentacion
INNER JOIN (
    SELECT
        'DESAYUNO' AS codigo,
        'Desayuno' AS nombre,
        1 AS orden,
        JSON_OBJECT('alimentos', 'Avena con frutas, huevo, pan integral', 'calorias', '450-500', 'macros', 'P: 20g | C: 60g | G: 10g') AS items,
        'Alternativa: yogur griego con granola' AS reemplazos,
        'Consumir entre 7-8 AM' AS observaciones
    UNION ALL SELECT
        'MEDIA_MAÑANA',
        'Media Mañana',
        2,
        JSON_OBJECT('alimentos', 'Frutas, frutos secos o barra proteica', 'calorias', '150-200', 'macros', 'P: 5-10g | C: 25-30g | G: 5g'),
        'Manzana con almendras o plátano',
        'Consumir entre 10-11 AM'
    UNION ALL SELECT
        'ALMUERZO',
        'Almuerzo',
        3,
        JSON_OBJECT('alimentos', 'Proteína, arroz/papa, vegetales', 'calorias', '600-700', 'macros', 'P: 40g | C: 70g | G: 15g'),
        'Pollo, pechuga de pavo o pescado',
        'Consumir entre 12-1 PM'
    UNION ALL SELECT
        'MERIENDA1',
        'Merienda (15h)',
        4,
        JSON_OBJECT('alimentos', 'Lácteo, frutas o frutos secos', 'calorias', '150-200', 'macros', 'P: 8-12g | C: 20-25g | G: 5g'),
        'Quesillo, yogur o batido de proteína',
        'Consumir entre 3-4 PM'
    UNION ALL SELECT
        'MERIENDA2',
        'Merienda Tarde',
        5,
        JSON_OBJECT('alimentos', 'Bebida deportiva, barrita o fruta', 'calorias', '100-150', 'macros', 'P: 3-5g | C: 20-25g | G: 2g'),
        'Batido o té con galleta',
        'Consumir entre 5-6 PM (pre-entreno si aplica)'
    UNION ALL SELECT
        'CENA',
        'Cena',
        6,
        JSON_OBJECT('alimentos', 'Proteína ligera, vegetales, carbos ligeros', 'calorias', '400-450', 'macros', 'P: 35g | C: 40g | G: 10g'),
        'Pechuga de pollo, salmón, huevo',
        'Consumir entre 7-8 PM (mínimo 2h antes de dormir)'
) comidas ON comidas.codigo = pac.codigo_comida
SET
    pac.nombre_comida = comidas.nombre,
    pac.orden = comidas.orden,
    pac.items = comidas.items,
    pac.reemplazos = comidas.reemplazos,
    pac.observaciones = comidas.observaciones,
    pac.updated_at = @script_ts;

INSERT INTO planes_alimentacion_comidas (
    id_plan_alimentacion_dia,
    codigo_comida,
    nombre_comida,
    orden,
    items,
    reemplazos,
    observaciones,
    created_at,
    updated_at
)
SELECT
    pad.id,
    comidas.codigo,
    comidas.nombre,
    comidas.orden,
    comidas.items,
    comidas.reemplazos,
    comidas.observaciones,
    @script_ts,
    @script_ts
FROM planes_alimentacion_dias pad
INNER JOIN tmp_planes_objetivo tpo ON tpo.id_plan = pad.id_plan_alimentacion
CROSS JOIN (
    SELECT
        'DESAYUNO' AS codigo,
        'Desayuno' AS nombre,
        1 AS orden,
        JSON_OBJECT('alimentos', 'Avena con frutas, huevo, pan integral', 'calorias', '450-500', 'macros', 'P: 20g | C: 60g | G: 10g') AS items,
        'Alternativa: yogur griego con granola' AS reemplazos,
        'Consumir entre 7-8 AM' AS observaciones
    UNION ALL SELECT
        'MEDIA_MAÑANA',
        'Media Mañana',
        2,
        JSON_OBJECT('alimentos', 'Frutas, frutos secos o barra proteica', 'calorias', '150-200', 'macros', 'P: 5-10g | C: 25-30g | G: 5g'),
        'Manzana con almendras o plátano',
        'Consumir entre 10-11 AM'
    UNION ALL SELECT
        'ALMUERZO',
        'Almuerzo',
        3,
        JSON_OBJECT('alimentos', 'Proteína, arroz/papa, vegetales', 'calorias', '600-700', 'macros', 'P: 40g | C: 70g | G: 15g'),
        'Pollo, pechuga de pavo o pescado',
        'Consumir entre 12-1 PM'
    UNION ALL SELECT
        'MERIENDA1',
        'Merienda (15h)',
        4,
        JSON_OBJECT('alimentos', 'Lácteo, frutas o frutos secos', 'calorias', '150-200', 'macros', 'P: 8-12g | C: 20-25g | G: 5g'),
        'Quesillo, yogur o batido de proteína',
        'Consumir entre 3-4 PM'
    UNION ALL SELECT
        'MERIENDA2',
        'Merienda Tarde',
        5,
        JSON_OBJECT('alimentos', 'Bebida deportiva, barrita o fruta', 'calorias', '100-150', 'macros', 'P: 3-5g | C: 20-25g | G: 2g'),
        'Batido o té con galleta',
        'Consumir entre 5-6 PM (pre-entreno si aplica)'
    UNION ALL SELECT
        'CENA',
        'Cena',
        6,
        JSON_OBJECT('alimentos', 'Proteína ligera, vegetales, carbos ligeros', 'calorias', '400-450', 'macros', 'P: 35g | C: 40g | G: 10g'),
        'Pechuga de pollo, salmón, huevo',
        'Consumir entre 7-8 PM (mínimo 2h antes de dormir)'
) comidas
LEFT JOIN planes_alimentacion_comidas pac
    ON pac.id_plan_alimentacion_dia = pad.id
   AND pac.codigo_comida = comidas.codigo
WHERE pad.dia_semana BETWEEN 1 AND 7
  AND pac.id IS NULL;

COMMIT;

-- ============================================================================
-- REPORTE FINAL (consultas separadas para evitar error 1137)
-- ============================================================================

SELECT 'RESUMEN DE PLANES NUTRICIONALES PROCESADOS' AS titulo;

SELECT
    SUM(CASE WHEN accion = 'creado' THEN 1 ELSE 0 END) AS planes_creados,
    SUM(CASE WHEN accion = 'actualizado' THEN 1 ELSE 0 END) AS planes_actualizados,
    COUNT(*) AS total_planes_procesados
FROM tmp_planes_objetivo;

SELECT
    COUNT(*) AS total_dias_configurados
FROM planes_alimentacion_dias pad
INNER JOIN tmp_planes_objetivo tpo ON tpo.id_plan = pad.id_plan_alimentacion;

SELECT
    COUNT(*) AS total_comidas_configuradas
FROM planes_alimentacion_comidas pac
INNER JOIN planes_alimentacion_dias pad ON pad.id = pac.id_plan_alimentacion_dia
INNER JOIN tmp_planes_objetivo tpo ON tpo.id_plan = pad.id_plan_alimentacion;

SELECT
    c.id,
    c.nombres,
    c.paterno,
    pa.id AS id_plan,
    pa.nombre,
    tpo.accion,
    COUNT(DISTINCT pad.id) AS dias_configurados,
    COUNT(DISTINCT pac.id) AS comidas_totales
FROM tmp_planes_objetivo tpo
INNER JOIN planes_alimentacion pa ON pa.id = tpo.id_plan
INNER JOIN clientes c ON c.id = tpo.id_cliente
LEFT JOIN planes_alimentacion_dias pad ON pad.id_plan_alimentacion = pa.id
LEFT JOIN planes_alimentacion_comidas pac ON pac.id_plan_alimentacion_dia = pad.id
GROUP BY c.id, c.nombres, c.paterno, pa.id, pa.nombre, tpo.accion
ORDER BY c.nombres, c.paterno;

SELECT
    CONCAT('DELETE FROM planes_alimentacion WHERE id IN (', GROUP_CONCAT(id_plan), ');') AS comando_rollback_referencial
FROM tmp_planes_objetivo
WHERE accion = 'creado';
