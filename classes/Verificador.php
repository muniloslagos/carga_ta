<?php
class Verificador {
    private $db;
    private $table = 'verificadores_publicador';
    private $uploadDir;

    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = dirname(__DIR__) . '/uploads/';
    }

    // Obtener verificador por ID
    public function getById($id) {
        $sql = "SELECT vp.*, u.nombre as publicador_nombre, d.titulo as documento_titulo
                FROM {$this->table} vp
                LEFT JOIN usuarios u ON vp.publicador_id = u.id
                LEFT JOIN documentos d ON vp.documento_id = d.id
                WHERE vp.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener verificador por documento
    public function getByDocumento($documento_id) {
        $sql = "SELECT vp.*, u.nombre as publicador_nombre
                FROM {$this->table} vp
                LEFT JOIN usuarios u ON vp.publicador_id = u.id
                WHERE vp.documento_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $documento_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener verificadores por item y mes/año
    public function getByItemMesAno($item_id, $mes, $ano) {
        $sql = "SELECT vp.*, d.usuario_id, d.titulo as documento_titulo, u.nombre as publicador_nombre
                FROM {$this->table} vp
                LEFT JOIN documentos d ON vp.documento_id = d.id
                LEFT JOIN documento_seguimiento ds ON vp.documento_id = ds.documento_id
                LEFT JOIN usuarios u ON vp.publicador_id = u.id
                WHERE vp.item_id = ? AND ds.mes = ? AND ds.ano = ?
                ORDER BY vp.fecha_carga_portal DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $item_id, $mes, $ano);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Crear verificador y cambiar estado del documento a "Publicado"
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (documento_id, item_id, usuario_id, publicador_id, archivo_verificador, fecha_carga_portal, comentarios)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiiisss",
            $data['documento_id'],
            $data['item_id'],
            $data['usuario_id'],
            $data['publicador_id'],
            $data['archivo_verificador'],
            $data['fecha_carga_portal'],
            $data['comentarios']
        );

        if ($stmt->execute()) {
            $verificador_id = $this->db->insert_id;
            
            // Cambiar estado del documento a "Publicado"
            $updateSql = "UPDATE documentos SET estado = 'Publicado' WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param("i", $data['documento_id']);
            $updateStmt->execute();
            
            return $verificador_id;
        }
        return false;
    }

    // Actualizar verificador
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                archivo_verificador = ?, 
                fecha_carga_portal = ?, 
                comentarios = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssi",
            $data['archivo_verificador'],
            $data['fecha_carga_portal'],
            $data['comentarios'],
            $id
        );

        return $stmt->execute();
    }

    // Eliminar verificador
    public function delete($id) {
        $verificador = $this->getById($id);
        if ($verificador && file_exists($this->uploadDir . $verificador['archivo_verificador'])) {
            unlink($this->uploadDir . $verificador['archivo_verificador']);
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Subir archivo verificador (imagen)
    public function uploadFile($file) {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes)) {
            return ['error' => 'Tipo de archivo no permitido. Solo se aceptan: JPG, JPEG, PNG, GIF, PDF'];
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'El archivo es demasiado grande (máximo 5MB)'];
        }

        $filename = uniqid('verif_') . '.' . $fileExt;
        $filepath = $this->uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
        }

        return ['error' => 'Error al subir el archivo'];
    }

    // Actualizar fecha de carga en documento_seguimiento
    public function actualizarFechaCargaPortal($documento_id, $mes, $ano) {
        $fecha_carga = date('Y-m-d H:i:s');
        
        $sql = "UPDATE documento_seguimiento 
                SET fecha_carga_portal = ? 
                WHERE documento_id = ? AND mes = ? AND ano = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siii", $fecha_carga, $documento_id, $mes, $ano);
        
        return $stmt->execute();
    }
}
?>
