<?php
class Documento {
    private $db;
    private $table = 'documentos';
    private $uploadDir;

    public function __construct($db) {
        $this->db = $db;
        // Usar ruta absoluta desde la raíz del proyecto
        $this->uploadDir = dirname(__DIR__) . '/uploads/';
    }

    // Obtener documento por ID
    public function getById($id) {
        $sql = "SELECT d.*, u.nombre as usuario_nombre, i.nombre as item_nombre 
                FROM {$this->table} d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN items_transparencia i ON d.item_id = i.id
                WHERE d.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener documentos por item
    public function getByItem($item_id, $mes = null, $ano = null) {
        $sql = "SELECT d.*, u.nombre as usuario_nombre 
                FROM {$this->table} d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                WHERE d.item_id = ?";

        $params = [$item_id];
        $types = "i";

        if ($mes) {
            $sql .= " AND MONTH(d.fecha_subida) = ?";
            $params[] = $mes;
            $types .= "i";
        }

        if ($ano) {
            $sql .= " AND YEAR(d.fecha_subida) = ?";
            $params[] = $ano;
            $types .= "i";
        }

        $sql .= " ORDER BY d.fecha_subida DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener documentos por usuario
    public function getByUsuario($usuario_id, $estado = null) {
        $sql = "SELECT d.*, i.nombre as item_nombre, i.periodicidad 
                FROM {$this->table} d
                INNER JOIN items_transparencia i ON d.item_id = i.id
                WHERE d.usuario_id = ?";

        if ($estado) {
            $sql .= " AND d.estado = '" . $this->db->escape($estado) . "'";
        }

        $sql .= " ORDER BY d.fecha_subida DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Crear documento
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (item_id, usuario_id, titulo, descripcion, archivo, mes_carga, ano_carga, estado, fecha_subida)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())";

        $stmt = $this->db->prepare($sql);
        $mes = isset($data['mes_carga']) ? (int)$data['mes_carga'] : (int)date('n');
        $ano = isset($data['ano_carga']) ? (int)$data['ano_carga'] : (int)date('Y');
        $stmt->bind_param("iisssii",
            $data['item_id'],
            $data['usuario_id'],
            $data['titulo'],
            $data['descripcion'],
            $data['archivo'],
            $mes,
            $ano
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    // Obtener documentos por item usando documento_seguimiento (MÁS CONFIABLE)
    public function getByItemFollowUp($item_id, $mes, $ano) {
        // Filtra por mes_carga y ano_carga (columnas guardadas al subir el documento)
        // Si no hay datos en esas columnas (documentos anteriores), fallback a fecha_subida
        $sql = "SELECT d.*, d.fecha_subida as fecha_envio, d.usuario_id, d.titulo, d.archivo,
                       u.nombre as usuario_nombre
                FROM {$this->table} d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                WHERE d.item_id = ?
                AND (
                    (d.mes_carga = ? AND d.ano_carga = ?)
                    OR
                    (d.mes_carga IS NULL AND MONTH(d.fecha_subida) = ? AND YEAR(d.fecha_subida) = ?)
                )
                ORDER BY d.fecha_subida DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("iiiii", $item_id, $mes, $ano, $mes, $ano);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener documentos por item y año (SIN mes) - para periodicidad ANUAL
    public function getByItemFollowUpAnual($item_id, $ano) {
        $sql = "SELECT d.*, d.fecha_subida as fecha_envio, d.usuario_id, d.titulo, d.archivo,
                       u.nombre as usuario_nombre
                FROM {$this->table} d
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                WHERE d.item_id = ? AND YEAR(d.fecha_subida) = ?
                ORDER BY d.fecha_subida DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("ii", $item_id, $ano);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener documentos APROBADOS para publicador - mes específico
    public function getByItemFollowUpAprobados($item_id, $mes, $ano) {
        $sql = "SELECT ds.*, d.usuario_id, d.titulo, d.archivo
                FROM documento_seguimiento ds
                LEFT JOIN {$this->table} d ON ds.documento_id = d.id
                WHERE ds.item_id = ? AND ds.mes = ? AND ds.ano = ? AND d.estado = 'aprobado'
                ORDER BY ds.fecha_envio DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $item_id, $mes, $ano);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener documentos APROBADOS para publicador - periodicidad ANUAL
    public function getByItemFollowUpAprobadosAnual($item_id, $ano) {
        $sql = "SELECT ds.*, d.usuario_id, d.titulo, d.archivo
                FROM documento_seguimiento ds
                LEFT JOIN {$this->table} d ON ds.documento_id = d.id
                WHERE ds.item_id = ? AND ds.ano = ? AND d.estado = 'aprobado'
                ORDER BY ds.fecha_envio DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $item_id, $ano);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener documentos CARGADOS para publicador - mes específico (sin necesidad de aprobación admin)
    public function getByItemFollowUpCargados($item_id, $mes, $ano) {
        $sql = "SELECT ds.*, d.id as documento_id, d.usuario_id, d.titulo, d.archivo, d.estado
                FROM documento_seguimiento ds
                LEFT JOIN {$this->table} d ON ds.documento_id = d.id
                WHERE ds.item_id = ? AND ds.mes = ? AND ds.ano = ? AND d.estado IN ('pendiente', 'aprobado', 'Publicado')
                ORDER BY ds.fecha_envio DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $item_id, $mes, $ano);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener documentos CARGADOS para publicador - sin filtro de mes (todos los documentos cargados)
    public function getAllCargados($mes = null, $ano = null) {
        $sql = "SELECT ds.*, d.id as documento_id, d.usuario_id, d.titulo, d.archivo, d.estado, u.nombre as usuario_nombre, i.nombre as item_nombre
                FROM documento_seguimiento ds
                LEFT JOIN {$this->table} d ON ds.documento_id = d.id
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                LEFT JOIN items_transparencia i ON ds.item_id = i.id
                WHERE d.estado IN ('pendiente', 'aprobado')";
        
        if ($mes && $ano) {
            $sql .= " AND ds.mes = ? AND ds.ano = ?";
        }
        
        $sql .= " ORDER BY ds.fecha_envio DESC";
        
        if ($mes && $ano) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $mes, $ano);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    // Actualizar estado del documento
    public function updateEstado($id, $estado) {
        $sql = "UPDATE {$this->table} SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute();
    }

    // Eliminar documento
    public function delete($id) {
        $doc = $this->getById($id);
        if ($doc) {
            $rutaArchivo = $this->getAbsoluteFilePath($doc['archivo']);
            if ($rutaArchivo && is_file($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Subir archivo
    public function uploadFile($file, $itemId = null, $mes = null, $ano = null) {
        // Tipos permitidos: PDF, documentos Word, Excel, imágenes y archivos comprimidos
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'xlsm', 'csv', 'jpg', 'jpeg', 'png', 'zip', 'rar', '7z'];
        $maxSize = 200 * 1024 * 1024; // 200MB

        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'El archivo no se recibió correctamente'];
        }

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes)) {
            return ['error' => 'Tipo de archivo no permitido'];
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'El archivo es demasiado grande (máximo 100MB)'];
        }

        $relativeDir = '';
        $destinationDir = $this->uploadDir;
        if ((int)$itemId > 0 && (int)$mes >= 1 && (int)$mes <= 12 && (int)$ano > 0) {
            $relativeDir = 'documentos/' . sprintf('%04d', (int)$ano)
                . '/' . sprintf('%02d', (int)$mes)
                . '/item_' . (int)$itemId . '/';
            $destinationDir .= $relativeDir;
        }

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
            return ['error' => 'No se pudo crear la carpeta de destino del documento'];
        }

        $originalName = basename(str_replace('\\', '/', (string)$file['name']));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', $baseName);
        $baseName = trim((string)$baseName, " .-_\t\n\r\0\x0B");
        if ($baseName === '') {
            $baseName = 'documento';
        }
        if (function_exists('mb_substr')) {
            $baseName = mb_substr($baseName, 0, 120, 'UTF-8');
        } else {
            $baseName = substr($baseName, 0, 120);
        }

        $filename = $baseName . '.' . $fileExt;
        $version = 2;
        while (file_exists($destinationDir . $filename)) {
            $filename = $baseName . '_v' . $version . '.' . $fileExt;
            $version++;
        }

        $relativePath = $relativeDir . $filename;
        $filepath = $destinationDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filepath' => $filepath,
                'filename' => $relativePath,
                'download_name' => $filename,
            ];
        }

        return ['error' => 'Error al subir el archivo'];
    }

    /**
     * Resuelve rutas antiguas (archivo.ext) y nuevas
     * (documentos/AAAA/MM/item_ID/archivo.ext) dentro de uploads.
     */
    public function getAbsoluteFilePath($relativePath) {
        $relativePath = str_replace('\\', '/', trim((string)$relativePath));
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || preg_match('#(^|/)\.\.(/|$)#', $relativePath)) {
            return null;
        }

        return $this->uploadDir . $relativePath;
    }
}
?>
