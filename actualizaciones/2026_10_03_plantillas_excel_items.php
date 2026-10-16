<?php

return [
    'codigo' => '2026_10_03_plantillas_excel_items',
    'version' => '1.3.0',
    'nombre' => 'Plantillas Excel por ítem',
    'descripcion' => 'Permite adjuntar una plantilla Excel a un ítem y descargarla desde el título en el perfil cargador.',
    'requiere_bd' => true,
    'check' => static function ($db) {
        $result = $db->query("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'items_transparencia'
              AND COLUMN_NAME IN ('plantilla_archivo','plantilla_nombre_original','plantilla_subida_por','plantilla_fecha_actualizacion')");
        $row = $result ? $result->fetch_assoc() : null;
        return $row && (int)$row['total'] === 4;
    },
    'install' => static function ($db) {
        $columns = [
            'plantilla_archivo' => 'VARCHAR(500) NULL DEFAULT NULL',
            'plantilla_nombre_original' => 'VARCHAR(255) NULL DEFAULT NULL',
            'plantilla_subida_por' => 'INT NULL DEFAULT NULL',
            'plantilla_fecha_actualizacion' => 'TIMESTAMP NULL DEFAULT NULL',
        ];
        foreach ($columns as $name => $definition) {
            $safe = $db->real_escape_string($name);
            $exists = $db->query("SHOW COLUMNS FROM items_transparencia LIKE '$safe'");
            if (!$exists || $exists->num_rows === 0) {
                if (!$db->query("ALTER TABLE items_transparencia ADD COLUMN `$name` $definition")) {
                    throw new Exception($db->error);
                }
            }
        }
    },
];
