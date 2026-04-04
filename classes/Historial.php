<?php

class Historial {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * Obtener historial completo de un item
     * Incluye: documentos cargados, verificadores agregados, observaciones sin movimiento
     */
    public function getHistorialItem($item_id, $mes = null, $ano = null) {
        $movimientos = [];

        // 1. Documentos cargados (con usuario y fecha)
        // Usar mes_carga/ano_carga de documentos (más confiable que documento_seguimiento)
        $sql = "SELECT 
                d.id as documento_id,
                'documento_cargado' as tipo_movimiento,
                u.nombre as usuario,
                d.fecha_subida as fecha,
                d.titulo,
                d.mes_carga as mes,
                d.ano_carga as ano,
                d.archivo
                FROM documentos d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                WHERE d.item_id = ?";

        $params = [$item_id];
        $types = "i";

        if ($mes !== null && $ano !== null) {
            $sql .= " AND d.mes_carga = ? AND d.ano_carga = ?";
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
                d.mes_carga as mes,
                d.ano_carga as ano
                FROM verificadores_publicador vp
                LEFT JOIN usuarios u ON vp.publicador_id = u.id
                LEFT JOIN documentos d ON vp.documento_id = d.id
                WHERE vp.item_id = ?";

        $params = [$item_id];
        $types = "i";

        if ($mes !== null && $ano !== null) {
            $sql .= " AND d.mes_carga = ? AND d.ano_carga = ?";
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

        // 3. Observaciones "Sin Movimiento"
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $sql = "SELECT 
                    o.id as observacion_id,
                    'sin_movimiento' as tipo_movimiento,
                    u.nombre as usuario,
                    o.fecha_creacion as fecha,
                    o.observacion,
                    o.mes,
                    o.ano
                    FROM observaciones_sin_movimiento o
                    LEFT JOIN usuarios u ON o.usuario_id = u.id
                    WHERE o.item_id = ?";

            $params = [$item_id];
            $types = "i";

            if ($mes !== null && $ano !== null) {
                $sql .= " AND o.mes = ? AND o.ano = ?";
                $params[] = $mes;
                $params[] = $ano;
                $types .= "ii";
            }

            $sql .= " ORDER BY o.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $movimientos[] = [
                    'tipo' => 'sin_movimiento',
                    'usuario' => $row['usuario'] ?? 'Usuario no identificado',
                    'fecha' => $row['fecha'],
                    'descripcion' => 'Sin Movimiento: ' . $row['observacion'],
                    'observacion_id' => $row['observacion_id'],
                    'detalle' => 'Observación registrada para el período',
                    'mes' => $row['mes'],
                    'ano' => $row['ano']
                ];
            }
        }

        // 4. Observaciones de Documentos (rechazos/observaciones del publicador)
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'observaciones_documentos'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $sql = "SELECT 
                    od.id as observacion_id,
                    'documento_observado' as tipo_movimiento,
                    u_observador.nombre as usuario_observador,
                    u_cargador.nombre as usuario_cargador,
                    od.fecha_observacion as fecha,
                    od.observacion,
                    od.resuelta,
                    od.fecha_resolucion,
                    od.mes,
                    od.ano,
                    d.titulo as documento_titulo
                    FROM observaciones_documentos od
                    LEFT JOIN usuarios u_observador ON od.observado_por = u_observador.id
                    LEFT JOIN usuarios u_cargador ON od.cargador_id = u_cargador.id
                    LEFT JOIN documentos d ON od.documento_id = d.id
                    WHERE od.item_id = ?";

            $params = [$item_id];
            $types = "i";

            if ($mes !== null && $ano !== null) {
                $sql .= " AND od.mes = ? AND od.ano = ?";
                $params[] = $mes;
                $params[] = $ano;
                $types .= "ii";
            }

            $sql .= " ORDER BY od.fecha_observacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $estado = $row['resuelta'] ? 'Resuelta' : 'Pendiente';
                $movimientos[] = [
                    'tipo' => 'documento_observado',
                    'usuario' => $row['usuario_observador'] ?? 'Publicador no identificado',
                    'fecha' => $row['fecha'],
                    'descripcion' => 'Documento Observado: ' . $row['documento_titulo'],
                    'observacion_id' => $row['observacion_id'],
                    'detalle' => 'Observación: ' . $row['observacion'] . ' | Estado: ' . $estado . 
                                 ($row['resuelta'] ? ' (Resuelta el ' . date('d/m/Y', strtotime($row['fecha_resolucion'])) . ')' : ' - Cargador: ' . $row['usuario_cargador']),
                    'mes' => $row['mes'],
                    'ano' => $row['ano']
                ];
            }
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
