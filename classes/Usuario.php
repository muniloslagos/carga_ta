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
        
        $stmt->bind_param(
            "ssssi",
            $data['nombre'],
            $data['email'],
            $hashedPassword,
            $data['perfil'],
            $data['direccion_id']
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

    // =========================================================================
    // MÉTODOS PARA MÚLTIPLES PERFILES
    // =========================================================================

    /**
     * Obtener todos los perfiles asignados a un usuario
     * @param int $usuario_id ID del usuario
     * @return array Lista de perfiles
     */
    public function getPerfiles($usuario_id) {
        $sql = "SELECT perfil, es_principal, fecha_asignacion 
                FROM usuario_perfiles 
                WHERE usuario_id = ? 
                ORDER BY es_principal DESC, perfil ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $perfiles = [];
        while ($row = $result->fetch_assoc()) {
            $perfiles[] = $row['perfil'];
        }
        return $perfiles;
    }

    /**
     * Verificar si un usuario tiene un perfil específico
     * @param int $usuario_id ID del usuario
     * @param string $perfil Perfil a verificar
     * @return bool
     */
    public function tienePerfil($usuario_id, $perfil) {
        $sql = "SELECT COUNT(*) as total FROM usuario_perfiles 
                WHERE usuario_id = ? AND perfil = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $perfil);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
    }

    /**
     * Agregar perfil a un usuario
     * @param int $usuario_id ID del usuario
     * @param string $perfil Perfil a agregar
     * @param int $asignado_por ID de quien asigna
     * @param bool $es_principal Si es el perfil principal
     * @return bool
     */
    public function agregarPerfil($usuario_id, $perfil, $asignado_por = null, $es_principal = false) {
        // Si se marca como principal, quitar marca de otros perfiles
        if ($es_principal) {
            $sql = "UPDATE usuario_perfiles SET es_principal = 0 WHERE usuario_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
        }

        $sql = "INSERT INTO usuario_perfiles (usuario_id, perfil, es_principal, asignado_por) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE es_principal = VALUES(es_principal)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isii", $usuario_id, $perfil, $es_principal, $asignado_por);
        return $stmt->execute();
    }

    /**
     * Eliminar perfil de un usuario
     * @param int $usuario_id ID del usuario
     * @param string $perfil Perfil a eliminar
     * @return bool
     */
    public function eliminarPerfil($usuario_id, $perfil) {
        $sql = "DELETE FROM usuario_perfiles WHERE usuario_id = ? AND perfil = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $perfil);
        return $stmt->execute();
    }

    /**
     * Obtener perfil principal de un usuario
     * @param int $usuario_id ID del usuario
     * @return string|null
     */
    public function getPerfilPrincipal($usuario_id) {
        $sql = "SELECT perfil FROM usuario_perfiles 
                WHERE usuario_id = ? AND es_principal = 1 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['perfil'] : null;
    }

    /**
     * Establecer perfil principal
     * @param int $usuario_id ID del usuario
     * @param string $perfil Perfil a marcar como principal
     * @return bool
     */
    public function setPerfilPrincipal($usuario_id, $perfil) {
        // Quitar marca principal de todos
        $sql = "UPDATE usuario_perfiles SET es_principal = 0 WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        // Marcar el nuevo como principal
        $sql = "UPDATE usuario_perfiles SET es_principal = 1 
                WHERE usuario_id = ? AND perfil = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $perfil);
        return $stmt->execute();
    }
}
?>
