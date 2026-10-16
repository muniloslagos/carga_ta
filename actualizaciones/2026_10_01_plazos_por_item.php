<?php

return [
    'codigo' => '2026_10_01_plazos_por_item',
    'version' => '1.1.0',
    'nombre' => 'Plazos configurables por ítem',
    'descripcion' => 'Agrega plazos base independientes para cargador y publicador. No modifica las reglas ni documentos existentes.',
    'requiere_bd' => true,
    'check' => static function ($db) {
        $result = $db->query("SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'items_transparencia'
              AND COLUMN_NAME IN ('plazo_dias_cargador','plazo_dias_publicador')");
        $row = $result ? $result->fetch_assoc() : null;
        return $row && (int)$row['total'] === 2;
    },
    'install' => static function ($db) {
        $columns = [
            'plazo_dias_cargador' => "TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Día hábil base para el cargador; NULL hereda 6'",
            'plazo_dias_publicador' => "TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Día hábil base para el publicador; NULL hereda 10'",
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

