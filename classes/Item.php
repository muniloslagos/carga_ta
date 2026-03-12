<?php
class Item {
    private $db;
    private $table = 'items_transparencia';

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtener item por ID
    public function getById($id) {
        $sql = "SELECT it.*, d.nombre as direccion_nombre FROM {$this->table} it
                LEFT JOIN direcciones d ON it.direccion_id = d.id
                WHERE it.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener todos los items
    public function getAll($direccion_id = null, $periodicidad = null) {
        $sql = "SELECT it.*, d.nombre as direccion_nombre FROM {$this->table} it
                LEFT JOIN direcciones d ON it.direccion_id = d.id
                WHERE it.activo = 1";

        if ($direccion_id) {
            $sql .= " AND it.direccion_id = " . intval($direccion_id);
        }

        if ($periodicidad) {
            $sql .= " AND it.periodicidad = '" . $this->db->escape($periodicidad) . "'";
        }

        $sql .= " ORDER BY it.numeracion ASC";
        return $this->db->query($sql);
    }

    // Crear item
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (numeracion, nombre, direccion_id, periodicidad, activo, fecha_creacion)
                VALUES (?, ?, ?, ?, 1, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssis", 
            $data['numeracion'],
            $data['nombre'],
            $data['direccion_id'],
            $data['periodicidad']
        );

        return $stmt->execute();
    }

    // Actualizar item
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                numeracion = ?,
                nombre = ?,
                direccion_id = ?,
                periodicidad = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssisi",
            $data['numeracion'],
            $data['nombre'],
            $data['direccion_id'],
            $data['periodicidad'],
            $id
        );

        return $stmt->execute();
    }

    // Desactivar item
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET activo = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Asignar usuario a item
    public function assignUser($item_id, $usuario_id) {
        $sql = "INSERT INTO item_usuarios (item_id, usuario_id) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE usuario_id = usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $item_id, $usuario_id);
        return $stmt->execute();
    }

    // Obtener usuarios asignados a un item
    public function getAsignedUsers($item_id) {
        $sql = "SELECT u.* FROM usuarios u
                INNER JOIN item_usuarios iu ON u.id = iu.usuario_id
                WHERE iu.item_id = ? AND u.activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener items asignados a un usuario
    public function getItemsByUser($usuario_id) {
        $sql = "SELECT it.*, d.nombre as direccion_nombre FROM {$this->table} it
                LEFT JOIN direcciones d ON it.direccion_id = d.id
                INNER JOIN item_usuarios iu ON it.id = iu.item_id
                WHERE iu.usuario_id = ? AND it.activo = 1
                ORDER BY it.numeracion ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
