<?php

class Historial {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * Obtener historial completo de un item
     * Incluye: documentos cargados, verificadores agregados
     */
    public function getHistorialItem($item_id, $mes = null, $ano = null) {
        $movimientos = [];

        // 1. Documentos cargados (con usuario y fecha)
        $sql = "SELECT 
                d.id as documento_id,
                'documento_cargado' as tipo_movimiento,
                u.nombre as usuario,
                d.fecha_subida as fecha,
                d.titulo,
                ds.mes,
                ds.ano,
                d.archivo
                FROM documentos d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
                WHERE d.item_id = ?";

        $params = [$item_id];
        $types = "i";

        if ($mes !== null && $ano !== null) {
            $sql .= " AND ds.mes = ? AND ds.ano = ?";
            $params[] = $mes;
            $params[] = $ano;
            $types .= "ii";
        }

        $sql .= " ORDER BY d.fecha_subida DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $movimientos[] = [
                'tipo' => 'documento_cargado',
                'usuario' => $row['usuario'] ?? 'Usuario no identificado',
                'fecha' => $row['fecha'],
                'descripcion' => 'Documento cargado: ' . $row['titulo'],
                'documento_id' => $row['documento_id'],
                'detalle' => 'Archivo: ' . $row['archivo'],
                'mes' => $row['mes'],
                'ano' => $row['ano']
            ];
        }

        // 2. Verificadores agregados (con publicador y fecha)
        $sql = "SELECT 
                vp.id as verificador_id,
                'verificador_agregado' as tipo_movimiento,
                u.nombre as usuario,
                vp.fecha_carga_portal as fecha,
                vp.archivo_verificador,
                d.titulo as documento_titulo,
                ds.mes,
                ds.ano
                FROM verificadores_publicador vp
                LEFT JOIN usuarios u ON vp.publicador_id = u.id
                LEFT JOIN documentos d ON vp.documento_id = d.id
                LEFT JOIN documento_seguimiento ds ON vp.documento_id = ds.documento_id
                WHERE vp.item_id = ?";

        $params = [$item_id];
        $types = "i";

        if ($mes !== null && $ano !== null) {
            $sql .= " AND ds.mes = ? AND ds.ano = ?";
            $params[] = $mes;
            $params[] = $ano;
            $types .= "ii";
        }

        $sql .= " ORDER BY vp.fecha_carga_portal DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $movimientos[] = [
                'tipo' => 'verificador_agregado',
                'usuario' => $row['usuario'] ?? 'Usuario no identificado',
                'fecha' => $row['fecha'],
                'descripcion' => 'Verificador agregado para: ' . $row['documento_titulo'],
                'verificador_id' => $row['verificador_id'],
                'detalle' => 'Archivo: ' . $row['archivo_verificador'],
                'mes' => $row['mes'],
                'ano' => $row['ano']
            ];
        }

        // Ordenar por fecha descendente
        usort($movimientos, function ($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        return $movimientos;
    }

    /**
     * Obtener historial de un item para un período específico
     */
    public function getHistorialItemPorPeriodo($item_id, $mes, $ano) {
        return $this->getHistorialItem($item_id, $mes, $ano);
    }

    /**
     * Obtener historial general de un item (sin filtro de período)
     */
    public function getHistorialItemCompleto($item_id) {
        return $this->getHistorialItem($item_id);
    }
}
?>
