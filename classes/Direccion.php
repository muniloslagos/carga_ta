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
        $sql = "SELECT * FROM {$this->table} WHERE activa = 1 ORDER BY nombre ASC";
        return $this->db->query($sql);
    }

    // Crear dirección
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (nombre, descripcion, activa, fecha_creacion)
                VALUES (?, ?, 1, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $data['nombre'], $data['descripcion']);
        return $stmt->execute();
    }

    // Actualizar dirección
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                nombre = ?,
                descripcion = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $data['nombre'], $data['descripcion'], $id);
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
