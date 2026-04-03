-- Script para actualizar documentos placeholder existentes que no tienen mes_carga/ano_carga
-- Estos documentos fueron creados por crear_documento_placeholder.php antes de la corrección

UPDATE documentos d
INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
SET d.mes_carga = ds.mes,
    d.ano_carga = ds.ano
WHERE d.titulo LIKE 'Sin Movimiento%'
  AND (d.mes_carga IS NULL OR d.ano_carga IS NULL)
  AND ds.mes IS NOT NULL
  AND ds.ano IS NOT NULL;

-- Verificar cuántos se actualizaron
SELECT COUNT(*) as documentos_actualizados 
FROM documentos 
WHERE titulo LIKE 'Sin Movimiento%' 
  AND mes_carga IS NOT NULL 
  AND ano_carga IS NOT NULL;
