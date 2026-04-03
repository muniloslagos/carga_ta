<?php
class Director {
    private $db;
    private $table = 'directores';

    public function __construct($db) {
        $this->db = $db;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getAll($soloActivos = true) {
        $sql = "SELECT * FROM {$this->table}";
        if ($soloActivos) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY apellidos, nombres ASC";
        return $this->db->query($sql);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (nombres, apellidos, correo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $data['nombres'], $data['apellidos'], $data['correo']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET nombres = ?, apellidos = ?, correo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $data['nombres'], $data['apellidos'], $data['correo'], $id);
        return $stmt->execute();
    }

    public function deactivate($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getDireccionesAsignadas($director_id) {
        $stmt = $this->db->prepare("SELECT * FROM direcciones WHERE director_id = ? AND activa = 1 ORDER BY nombre");
        $stmt->bind_param("i", $director_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}
