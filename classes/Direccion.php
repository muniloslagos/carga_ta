<?php
class Direccion {
    private $db;
    private $table = 'direcciones';

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtener dirección por ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener todas las direcciones
    public function getAll() {
        $sql = "SELECT d.*, 
                       CONCAT(dir.nombres, ' ', dir.apellidos) as director_nombre
                FROM {$this->table} d
                LEFT JOIN directores dir ON d.director_id = dir.id
                WHERE d.activa = 1 
                ORDER BY d.nombre ASC";
        return $this->db->query($sql);
    }

    // Crear dirección
    public function create($data) {
        $director_id = !empty($data['director_id']) ? intval($data['director_id']) : null;
        $sql = "INSERT INTO {$this->table} 
                (nombre, descripcion, director_id, activa, fecha_creacion)
                VALUES (?, ?, ?, 1, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $data['nombre'], $data['descripcion'], $director_id);
        return $stmt->execute();
    }

    // Actualizar dirección
    public function update($id, $data) {
        $director_id = !empty($data['director_id']) ? intval($data['director_id']) : null;
        $sql = "UPDATE {$this->table} SET 
                nombre = ?,
                descripcion = ?,
                director_id = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssii", $data['nombre'], $data['descripcion'], $director_id, $id);
        return $stmt->execute();
    }

    // Desactivar dirección
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET activa = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Obtener usuarios de una dirección
    public function getUsuarios($direccion_id) {
        $sql = "SELECT * FROM usuarios WHERE direccion_id = ? AND activo = 1 ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $direccion_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
