<?php

class SystemUpdater {
    private $db;
    private $updatesDir;

    public function __construct($db, $updatesDir = null) {
        $this->db = $db;
        $this->updatesDir = $updatesDir ?: dirname(__DIR__) . '/actualizaciones';
    }

    public function registryExists() {
        $result = $this->db->query("SHOW TABLES LIKE 'migraciones_sistema'");
        return $result && $result->num_rows > 0;
    }

    public function ensureRegistry() {
        $sql = "CREATE TABLE IF NOT EXISTS migraciones_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(150) NOT NULL UNIQUE,
            version VARCHAR(30) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NULL,
            estado ENUM('instalada','no_aplicable','error') NOT NULL DEFAULT 'instalada',
            instalado_por INT NULL,
            fecha_instalacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            detalle TEXT NULL,
            INDEX idx_migraciones_estado (estado),
            CONSTRAINT fk_migraciones_usuario FOREIGN KEY (instalado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$this->db->query($sql)) {
            throw new Exception('No se pudo crear el registro de actualizaciones: ' . $this->db->error);
        }
    }

    public function getUpdates() {
        $updates = [];
        if (!is_dir($this->updatesDir)) {
            return $updates;
        }

        foreach (glob($this->updatesDir . '/*.php') ?: [] as $file) {
            $update = require $file;
            if (!is_array($update) || empty($update['codigo']) || !isset($update['check'], $update['install'])) {
                continue;
            }
            $update['_file'] = $file;
            $updates[$update['codigo']] = $update;
        }

        uasort($updates, static function ($a, $b) {
            return strcmp($a['codigo'], $b['codigo']);
        });
        return $updates;
    }

    public function isApplied(array $update) {
        try {
            return (bool)call_user_func($update['check'], $this->db);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function pendingCount() {
        $count = 0;
        foreach ($this->getUpdates() as $update) {
            if (!$this->isApplied($update)) {
                $count++;
            }
        }
        return $count;
    }

    public function reconcileExisting() {
        $this->ensureRegistry();
        foreach ($this->getUpdates() as $update) {
            if ($this->isApplied($update) && !$this->getRecord($update['codigo'])) {
                $this->saveRecord($update, 'no_aplicable', null, 'La estructura ya existía al inicializar el centro de actualizaciones.');
            }
        }
    }

    public function install($codigo, $userId) {
        $updates = $this->getUpdates();
        if (!isset($updates[$codigo])) {
            throw new Exception('La actualización solicitada no existe en esta versión del sistema.');
        }

        $this->ensureRegistry();
        $update = $updates[$codigo];
        if ($this->isApplied($update)) {
            $this->saveRecord($update, 'no_aplicable', $userId, 'La estructura ya se encontraba instalada.');
            return 'La actualización ya estaba aplicada.';
        }

        try {
            call_user_func($update['install'], $this->db);
            if (!$this->isApplied($update)) {
                throw new Exception('La verificación posterior no confirmó la instalación.');
            }
            $this->saveRecord($update, 'instalada', $userId, 'Instalación verificada correctamente.');
            return 'Actualización instalada correctamente.';
        } catch (Throwable $e) {
            $this->saveRecord($update, 'error', $userId, $e->getMessage());
            throw $e;
        }
    }

    public function getRecord($codigo) {
        if (!$this->registryExists()) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT ms.*, u.nombre AS instalado_por_nombre
            FROM migraciones_sistema ms
            LEFT JOIN usuarios u ON u.id = ms.instalado_por
            WHERE ms.codigo = ? LIMIT 1");
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $record ?: null;
    }

    private function saveRecord(array $update, $estado, $userId, $detalle) {
        $sql = "INSERT INTO migraciones_sistema
                    (codigo, version, nombre, descripcion, estado, instalado_por, fecha_instalacion, detalle)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    version = VALUES(version), nombre = VALUES(nombre), descripcion = VALUES(descripcion),
                    estado = VALUES(estado), instalado_por = VALUES(instalado_por),
                    fecha_instalacion = NOW(), detalle = VALUES(detalle)";
        $stmt = $this->db->prepare($sql);
        $userId = $userId ?: null;
        $stmt->bind_param('sssssis',
            $update['codigo'], $update['version'], $update['nombre'], $update['descripcion'],
            $estado, $userId, $detalle
        );
        $stmt->execute();
        $stmt->close();
    }
}

