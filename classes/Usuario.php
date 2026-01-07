<?php
class Usuario {
    private $db;
    private $table = 'usuarios';

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtener usuario por ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener usuario por correo
    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Autenticar usuario
    public function authenticate($email, $password) {
        $user = $this->getByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    // Obtener todos los usuarios
    public function getAll($direccion_id = null, $perfil = null) {
        $sql = "SELECT u.*, d.nombre as direccion_nombre FROM {$this->table} u
                LEFT JOIN direcciones d ON u.direccion_id = d.id
                WHERE u.activo = 1";

        if ($direccion_id) {
            $sql .= " AND u.direccion_id = " . intval($direccion_id);
        }

        if ($perfil) {
            $sql .= " AND u.perfil = '" . $this->db->escape($perfil) . "'";
        }

        $sql .= " ORDER BY u.nombre ASC";
        return $this->db->query($sql);
    }

    // Crear nuevo usuario
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (nombre, email, password, perfil, direccion_id, activo, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, 1, NOW())";

        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Manejar direccion_id nulo
        $direccion_id = !empty($data['direccion_id']) ? $data['direccion_id'] : null;
        
        $stmt->bind_param(
            "ssssi",
            $data['nombre'],
            $data['email'],
            $hashedPassword,
            $data['perfil'],
            $direccion_id
        );

        return $stmt->execute();
    }

    // Actualizar usuario
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                nombre = ?,
                email = ?,
                perfil = ?,
                direccion_id = ?";

        $params = [
            $data['nombre'],
            $data['email'],
            $data['perfil'],
            $data['direccion_id']
        ];
        $types = "sssi";

        if (isset($data['password']) && !empty($data['password'])) {
            $sql .= ", password = ?";
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $params[] = $hashedPassword;
            $types .= "s";
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    // Desactivar usuario
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET activo = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Cambiar contraseña
    public function changePassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    // Obtener usuarios sin dirección asignada
    public function getUsuariosSinDireccion() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE activo = 1 AND (direccion_id IS NULL OR direccion_id = 0)
                ORDER BY nombre ASC";
        return $this->db->query($sql);
    }
}
?>
