<?php

return [
    'codigo' => '2026_10_02_version_final_revisor',
    'version' => '1.2.0',
    'nombre' => 'Versiones finales validadas por revisor',
    'descripcion' => 'Permite conservar el archivo original y entregar al publicador una versión final validada cuando el revisor la carga.',
    'requiere_bd' => true,
    'check' => static function ($db) {
        $result = $db->query("SHOW TABLES LIKE 'documentos_versiones_revisor'");
        return $result && $result->num_rows > 0;
    },
    'install' => static function ($db) {
        $sql = "CREATE TABLE IF NOT EXISTS documentos_versiones_revisor (
            id INT AUTO_INCREMENT PRIMARY KEY,
            documento_id INT NOT NULL,
            revisor_id INT NOT NULL,
            archivo VARCHAR(500) NOT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            comentarios TEXT NULL,
            numero_version INT NOT NULL DEFAULT 1,
            es_vigente TINYINT(1) NOT NULL DEFAULT 1,
            fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            hash_archivo CHAR(64) NULL,
            INDEX idx_version_documento_vigente (documento_id, es_vigente),
            INDEX idx_version_revisor (revisor_id),
            CONSTRAINT fk_version_revisor_documento FOREIGN KEY (documento_id)
                REFERENCES documentos(id) ON DELETE CASCADE,
            CONSTRAINT fk_version_revisor_usuario FOREIGN KEY (revisor_id)
                REFERENCES usuarios(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (!$db->query($sql)) {
            throw new Exception($db->error);
        }
    },
];

