-- ============================================================================
-- SCRIPT: VERIFICAR PLANES NUTRICIONALES CREADOS
-- ============================================================================
-- Descripción: Verifica la integridad y completitud de los planes nutricionales
-- ============================================================================

-- 1. VERIFICACIÓN GENERAL
SELECT 
    '📊 ESTADO GENERAL DE PLANES' AS seccion,
    COUNT(*) AS total_planes,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS planes_activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) AS planes_inactivos
FROM planes_alimentacion;

-- 2. CLIENTES CON PLANES
SELECT
    c.id,
    CONCAT(c.nombres, ' ', c.paterno) AS cliente,
    COUNT(pa.id) AS cantidad_planes,
    MAX(pa.created_at) AS ultimo_plan
FROM clientes c
LEFT JOIN planes_alimentacion pa ON pa.id_cliente = c.id
WHERE c.estado = 1
GROUP BY c.id, c.nombres, c.paterno
HAVING COUNT(pa.id) > 0
ORDER BY c.nombres;

-- 3. VALIDAR ESTRUCTURA DE PLANES
SELECT
    pa.id,
    pa.nombre,
    COUNT(DISTINCT pad.id) AS dias_configurados,
    COUNT(DISTINCT pac.id) AS comidas_totales,
    CASE
        WHEN COUNT(DISTINCT pad.id) = 7 AND COUNT(DISTINCT pac.id) = 42 THEN '✓ Completo'
        ELSE '⚠ Incompleto'
    END AS estado_estructura
FROM planes_alimentacion pa
LEFT JOIN planes_alimentacion_dias pad ON pad.id_plan_alimentacion = pa.id
LEFT JOIN planes_alimentacion_comidas pac ON pac.id_plan_alimentacion_dia = pad.id
GROUP BY pa.id, pa.nombre;

-- 4. DETALLES POR DÍA
SELECT
    pa.nombre AS plan,
    pad.nombre_dia,
    COUNT(pac.id) AS comidas_del_dia
FROM planes_alimentacion pa
INNER JOIN planes_alimentacion_dias pad ON pad.id_plan_alimentacion = pa.id
LEFT JOIN planes_alimentacion_comidas pac ON pac.id_plan_alimentacion_dia = pad.id
GROUP BY pa.id, pa.nombre, pad.nombre_dia
ORDER BY pa.nombre, pad.dia_semana;

-- 5. PLANES CREADOS HOY
SELECT
    DATE(pa.created_at) AS fecha_creacion,
    COUNT(*) AS planes_creados
FROM planes_alimentacion pa
WHERE DATE(pa.created_at) = CURDATE()
GROUP BY DATE(pa.created_at);
