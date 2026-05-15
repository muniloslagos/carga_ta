<?php
/**
 * Clase Revisor
 * Gestiona el proceso de revisión de documentos previo a la publicación
 * 
 * Este perfil puede:
 * - Ver documentos cargados
 * - Aprobar documentos (permite al publicador cargar verificador)
 * - Observar documentos (bloquea al publicador hasta corrección)
 * - Agregar observaciones
 */

class Revisor {
    private $db;
    private $table = 'revisiones_documentos';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Obtener revisión por documento
     */
    public function getByDocumento($documento_id) {
        $sql = "SELECT rd.*, u.nombre as revisor_nombre, u.email as revisor_email
                FROM {$this->table} rd
                LEFT JOIN usuarios u ON rd.revisor_id = u.id
                WHERE rd.documento_id = ?
                ORDER BY rd.fecha_modificacion DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $documento_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Verificar si un documento está aprobado
     */
    public function estaAprobado($documento_id) {
        $revision = $this->getByDocumento($documento_id);
        return $revision && $revision['estado'] === 'aprobado';
    }

    /**
     * Verificar si un documento está observado
     */
    public function estaObservado($documento_id) {
        $revision = $this->getByDocumento($documento_id);
        return $revision && $revision['estado'] === 'observado';
    }

    /**
     * Aprobar un documento
     */
    public function aprobar($documento_id, $revisor_id, $observaciones = null) {
        // Verificar si ya existe una revisión
        $existing = $this->getByDocumento($documento_id);
        
        if ($existing) {
            // Actualizar revisión existente
            $sql = "UPDATE {$this->table} 
                    SET estado = 'aprobado', 
                        observaciones = ?,
                        revisor_id = ?,
                        fecha_modificacion = CURRENT_TIMESTAMP
                    WHERE documento_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sii", $observaciones, $revisor_id, $documento_id);
        } else {
            // Crear nueva revisión
            $sql = "INSERT INTO {$this->table} 
                    (documento_id, revisor_id, estado, observaciones)
                    VALUES (?, ?, 'aprobado', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iis", $documento_id, $revisor_id, $observaciones);
        }
        
        return $stmt->execute();
    }

    /**
     * Observar un documento
     */
    public function observar($documento_id, $revisor_id, $observaciones) {
        if (empty($observaciones)) {
            return ['error' => 'Las observaciones son obligatorias'];
        }

        // Verificar si ya existe una revisión
        $existing = $this->getByDocumento($documento_id);
        
        if ($existing) {
            // Actualizar revisión existente
            $sql = "UPDATE {$this->table} 
                    SET estado = 'observado', 
                        observaciones = ?,
                        revisor_id = ?,
                        fecha_modificacion = CURRENT_TIMESTAMP
                    WHERE documento_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sii", $observaciones, $revisor_id, $documento_id);
        } else {
            // Crear nueva revisión
            $sql = "INSERT INTO {$this->table} 
                    (documento_id, revisor_id, estado, observaciones)
                    VALUES (?, ?, 'observado', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iis", $documento_id, $revisor_id, $observaciones);
        }
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['error' => 'Error al guardar la observación'];
    }

    /**
     * Obtener todos los documentos pendientes de revisión para un revisor
     * (documentos que no han sido revisados)
     */
    public function getDocumentosPendientes($ano = null, $mes = null) {
        $sql = "SELECT d.*, 
                       ds.mes,
                       ds.ano,
                       i.nombre as item_titulo,
                       i.numeracion as item_numeracion,
                       dir.nombre as direccion_nombre,
                       u.nombre as cargador_nombre,
                       rd.estado as estado_revision,
                       rd.observaciones as observaciones_revision,
                       rd.fecha_revision,
                       (SELECT COUNT(*) FROM verificadores_publicador WHERE documento_id = d.id) as tiene_verificador
                FROM documentos d
                LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
                INNER JOIN items_transparencia i ON d.item_id = i.id
                LEFT JOIN direcciones dir ON i.direccion_id = dir.id
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN revisiones_documentos rd ON d.id = rd.documento_id
                WHERE d.estado IN ('pendiente', 'aprobado')
                AND rd.documento_id IS NULL
                AND ds.documento_id IS NOT NULL";
        
        $params = [];
        $types = "";
        
        if ($ano !== null) {
            $sql .= " AND ds.ano = ?";
            $params[] = $ano;
            $types .= "i";
        }
        
        if ($mes !== null) {
            $sql .= " AND ds.mes = ?";
            $params[] = $mes;
            $types .= "i";
        }
        
        $sql .= " ORDER BY d.fecha_subida DESC";
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Obtener TODOS los documentos (con y sin revisión)
     */
    public function getTodosDocumentos($ano = null, $mes = null) {
        $sql = "SELECT d.*, 
                       ds.mes,
                       ds.ano,
                       i.nombre as item_titulo,
                       i.numeracion as item_numeracion,
                       dir.nombre as direccion_nombre,
                       u.nombre as cargador_nombre,
                       rd.estado as estado_revision,
                       rd.observaciones as observaciones_revision,
                       rd.fecha_revision,
                       (SELECT COUNT(*) FROM verificadores_publicador WHERE documento_id = d.id) as tiene_verificador
                FROM documentos d
                LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
                INNER JOIN items_transparencia i ON d.item_id = i.id
                LEFT JOIN direcciones dir ON i.direccion_id = dir.id
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN revisiones_documentos rd ON d.id = rd.documento_id
                WHERE d.estado IN ('pendiente', 'aprobado')
                AND ds.documento_id IS NOT NULL";
        
        $params = [];
        $types = "";
        
        if ($ano !== null) {
            $sql .= " AND ds.ano = ?";
            $params[] = $ano;
            $types .= "i";
        }
        
        if ($mes !== null) {
            $sql .= " AND ds.mes = ?";
            $params[] = $mes;
            $types .= "i";
        }
        
        $sql .= " ORDER BY d.fecha_subida DESC";
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Obtener documentos revisados por un revisor específico
     */
    public function getDocumentosRevisados($revisor_id, $ano = null, $mes = null) {
        $sql = "SELECT d.*, 
                       ds.mes,
                       ds.ano,
                       i.nombre as item_titulo,
                       i.numeracion as item_numeracion,
                       dir.nombre as direccion_nombre,
                       u.nombre as cargador_nombre,
                       rd.estado as estado_revision,
                       rd.observaciones as observaciones_revision,
                       rd.fecha_revision
                FROM documentos d
                LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
                INNER JOIN items_transparencia i ON d.item_id = i.id
                LEFT JOIN direcciones dir ON i.direccion_id = dir.id
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                INNER JOIN revisiones_documentos rd ON d.id = rd.documento_id
                WHERE rd.revisor_id = ?
                AND ds.documento_id IS NOT NULL";
        
        $params = [$revisor_id];
        $types = "i";
        
        if ($ano !== null) {
            $sql .= " AND ds.ano = ?";
            $params[] = $ano;
            $types .= "i";
        }
        
        if ($mes !== null) {
            $sql .= " AND ds.mes = ?";
            $params[] = $mes;
            $types .= "i";
        }
        
        $sql .= " ORDER BY rd.fecha_modificacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Verificar si la funcionalidad de revisión está activada
     */
    public static function estaActivado($conn) {
        $sql = "SELECT valor FROM configuracion WHERE clave = 'activar_revision_previa'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['valor'] == '1';
        }
        return false;
    }

    /**
     * Validar si un documento puede ser publicado (para el publicador)
     * Retorna: ['puede_publicar' => true/false, 'razon' => 'mensaje']
     */
    public static function puedePublicar($documento_id, $conn) {
        // Verificar si la revisión está activada
        if (!self::estaActivado($conn)) {
            return ['puede_publicar' => true, 'razon' => ''];
        }

        // Verificar estado de revisión
        $sql = "SELECT estado FROM revisiones_documentos WHERE documento_id = ? ORDER BY fecha_modificacion DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $documento_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // No ha sido revisado - permitir publicación (revisión es opcional)
            return ['puede_publicar' => true, 'razon' => ''];
        }
        
        $revision = $result->fetch_assoc();
        
        if ($revision['estado'] === 'observado') {
            return [
                'puede_publicar' => false, 
                'razon' => 'El documento tiene observaciones del revisor. Debe ser corregido y re-aprobado antes de publicar.'
            ];
        }
        
        // Estado aprobado o cualquier otro
        return ['puede_publicar' => true, 'razon' => ''];
    }

    /**
     * Obtener estadísticas de revisión
     */
    public function getEstadisticas($revisor_id = null, $ano = null) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN rd.estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN rd.estado = 'observado' THEN 1 ELSE 0 END) as observados
                FROM revisiones_documentos rd
                INNER JOIN documentos d ON rd.documento_id = d.id";
        
        if ($ano !== null) {
            $sql .= " LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id";
        }
        
        $sql .= " WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($revisor_id !== null) {
            $sql .= " AND rd.revisor_id = ?";
            $params[] = $revisor_id;
            $types .= "i";
        }
        
        if ($ano !== null) {
            $sql .= " AND ds.ano = ?";
            $params[] = $ano;
            $types .= "i";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
