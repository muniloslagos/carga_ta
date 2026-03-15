<?php
class ItemConPlazo {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Obtener item con información de plazo y documentos
     * @param $item_id ID del item
     * @param $ano Año
     * @param $mes Mes
     * @return Array con información consolidada
     */
    public function getItemConPlazo($item_id, $ano, $mes) {
        // Intentar con item_plazos si existe
        $sql = "SELECT 
                i.id,
                i.numeracion,
                i.nombre,
                i.descripcion,
                i.periodicidad,
                NULL as plazo_id,
                NULL as plazo_interno,
                NULL as fecha_carga_portal,
                COUNT(d.id) as total_documentos,
                MAX(CASE WHEN d.estado = 'pendiente' THEN 1 ELSE 0 END) as tiene_pendientes,
                MAX(d.fecha_subida) as fecha_ultimo_envio
                FROM items_transparencia i
                LEFT JOIN documentos d ON i.id = d.item_id
                    AND YEAR(d.fecha_subida) = ? AND MONTH(d.fecha_subida) = ?
                WHERE i.id = ?
                GROUP BY i.id";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("iii", $ano, $mes, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Obtener items del usuario para un mes específico
     * @param $usuario_id ID del usuario
     * @param $ano Año
     * @param $mes Mes
     * @return Resultado de query
     */
    public function getItemsUsuarioPorMes($usuario_id, $ano, $mes) {
        $sql = "SELECT 
                i.id,
                i.numeracion,
                i.nombre,
                i.descripcion,
                i.periodicidad,
                ip.id as plazo_id,
                ip.plazo_interno,
                ip.fecha_carga_portal,
                d.id as ultimo_documento_id,
                d.estado as ultimo_estado_documento,
                d.fecha_subida as fecha_ultimo_envio,
                d.titulo as ultimo_titulo_documento,
                COUNT(d.id) as total_documentos
                FROM items_transparencia i
                INNER JOIN item_usuarios iu ON i.id = iu.item_id
                LEFT JOIN item_plazos ip ON i.id = ip.item_id 
                    AND ip.ano = ? AND ip.mes = ?
                LEFT JOIN (
                    SELECT id, item_id, estado, fecha_subida, titulo
                    FROM documentos
                    WHERE YEAR(fecha_subida) = ? AND MONTH(fecha_subida) = ?
                    ORDER BY fecha_subida DESC
                ) d ON i.id = d.item_id
                WHERE iu.usuario_id = ? AND i.activo = 1
                GROUP BY i.id
                ORDER BY i.numeracion ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("iiiii", $ano, $mes, $ano, $mes, $usuario_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Obtener documentos de un item para un mes específico
     * @param $item_id ID del item
     * @param $usuario_id ID del usuario
     * @param $ano Año
     * @param $mes Mes
     * @return Resultado de query
     */
    public function getDocumentosPorMes($item_id, $usuario_id, $ano, $mes) {
        // Filtra por mes_carga/ano_carga (guardados al subir)
        // Con fallback a fecha_subida para documentos anteriores a la migración
        if ($usuario_id === null) {
            $sql = "SELECT 
                    d.*,
                    d.fecha_subida as fecha_envio,
                    u.nombre as usuario_nombre
                    FROM documentos d
                    LEFT JOIN usuarios u ON d.usuario_id = u.id
                    WHERE d.item_id = ?
                    AND (
                        (d.mes_carga = ? AND d.ano_carga = ?)
                        OR
                        (d.mes_carga IS NULL AND MONTH(d.fecha_subida) = ? AND YEAR(d.fecha_subida) = ?)
                    )
                    ORDER BY d.fecha_subida DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) return null;
            $stmt->bind_param("iiiii", $item_id, $mes, $ano, $mes, $ano);
        } else {
            $sql = "SELECT 
                    d.*,
                    d.fecha_subida as fecha_envio,
                    u.nombre as usuario_nombre
                    FROM documentos d
                    LEFT JOIN usuarios u ON d.usuario_id = u.id
                    WHERE d.item_id = ?
                    AND d.usuario_id = ?
                    AND (
                        (d.mes_carga = ? AND d.ano_carga = ?)
                        OR
                        (d.mes_carga IS NULL AND MONTH(d.fecha_subida) = ? AND YEAR(d.fecha_subida) = ?)
                    )
                    ORDER BY d.fecha_subida DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) return null;
            $stmt->bind_param("iiiiii", $item_id, $usuario_id, $mes, $ano, $mes, $ano);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
