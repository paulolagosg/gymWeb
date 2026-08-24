-- ============================================================================
-- SCRIPT: LIMPIAR/ELIMINAR PLANES NUTRICIONALES
-- ============================================================================
-- Descripción: Elimina planes nutricionales (útil para rollback o limpieza)
-- ADVERTENCIA: Este script elimina datos en cascada
-- ============================================================================

START TRANSACTION;

-- 1. Mostrar planes que serán eliminados
SELECT 
    pa.id,
    pa.nombre,
    c.nombres,
    c.paterno,
    pa.created_at
FROM planes_alimentacion pa
INNER JOIN clientes c ON c.id = pa.id_cliente
WHERE pa.estado = 'activo'
  AND DATE(pa.created_at) = CURDATE()
ORDER BY pa.created_at DESC;

-- 2. Guardar registro de eliminación (opcional - comentar si no se desea)
-- INSERT INTO logs_eliminaciones (tabla, cantidad, descripcion, usuario, fecha)
-- SELECT 
--     'planes_alimentacion',
--     COUNT(*),
--     CONCAT('Eliminados ', COUNT(*), ' planes nutricionales del ', CURDATE()),
--     CURRENT_USER(),
--     NOW()
-- FROM planes_alimentacion
-- WHERE DATE(created_at) = CURDATE();

-- 3. Eliminar planes (las referencias en cascada se eliminarán automáticamente)
-- Descomentar las líneas siguientes para ejecutar la eliminación

-- DELETE FROM planes_alimentacion
-- WHERE estado = 'activo'
--   AND DATE(created_at) = CURDATE();

-- SELECT CONCAT('Se eliminaron ', ROW_COUNT(), ' planes nutricionales.') AS resultado;

-- COMMIT;

-- Para rollback manual:
-- ROLLBACK;

-- Nota: Descomentar el DELETE y COMMIT cuando esté listo para eliminar
