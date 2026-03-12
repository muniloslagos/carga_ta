<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$c = $db->getConnection();

echo "=== DIAGNÓSTICO: DOCUMENTOS PARA PUBLICADOR ===\n\n";

// 1. Verificar documento de Marianela
echo "1. DOCUMENTO CARGADO POR MARIANELA:\n";
$result = $c->query("
    SELECT d.id, d.titulo, d.archivo, d.estado, d.fecha_subida, 
           u.nombre as usuario, i.nombre as item
    FROM documentos d
    JOIN usuarios u ON d.usuario_id = u.id
    JOIN items_transparencia i ON d.item_id = i.id
    WHERE u.id = 6 AND i.nombre LIKE '%Libro diario%noviembre%'
    ORDER BY d.fecha_subida DESC
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Título: {$row['titulo']}\n";
        echo "    Item: {$row['item']}\n";
        echo "    Estado: {$row['estado']}\n";
        echo "    Cargado: {$row['fecha_subida']}\n";
        echo "    Archivo: {$row['archivo']}\n\n";
    }
} else {
    echo "  ✗ No se encontró documento\n\n";
}

// 2. Verificar qué item es "Libro diario municipal mes noviembre"
echo "2. BUSCAR ITEM 'LIBRO DIARIO':\n";
$result = $c->query("
    SELECT id, nombre, numeracion, periodicidad
    FROM items_transparencia
    WHERE nombre LIKE '%libro%diario%' OR nombre LIKE '%noviembre%'
    ORDER BY nombre
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID {$row['id']}: {$row['nombre']}\n";
        echo "    Numeración: {$row['numeracion']}, Periodicidad: {$row['periodicidad']}\n";
    }
} else {
    echo "  ✗ No se encontró item\n";
}
echo "\n";

// 3. Verificar si Juan está asignado a algún item
echo "3. ITEMS ASIGNADOS A JUAN (ID 8):\n";
$result = $c->query("
    SELECT i.id, i.nombre, i.periodicidad
    FROM item_usuarios iu
    JOIN items_transparencia i ON iu.item_id = i.id
    WHERE iu.usuario_id = 8
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['nombre']} (ID {$row['id']}, {$row['periodicidad']})\n";
    }
} else {
    echo "  ✗ Juan no tiene items asignados\n";
}
echo "\n";

// 4. Verificar documentos en general para el item de Libro diario
echo "4. TODOS LOS DOCUMENTOS DEL ITEM 'LIBRO DIARIO':\n";
$result = $c->query("
    SELECT d.id, d.titulo, d.usuario_id, u.nombre, d.estado, d.fecha_subida
    FROM documentos d
    JOIN usuarios u ON d.usuario_id = u.id
    JOIN items_transparencia i ON d.item_id = i.id
    WHERE i.nombre LIKE '%libro%diario%'
    ORDER BY d.fecha_subida DESC
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - Doc ID {$row['id']}: {$row['titulo']}\n";
        echo "    Usuario: {$row['nombre']} (ID {$row['usuario_id']})\n";
        echo "    Estado: {$row['estado']}, Fecha: {$row['fecha_subida']}\n";
    }
} else {
    echo "  ✗ No hay documentos\n";
}
echo "\n";

// 5. Verificar tabla documento_seguimiento
echo "5. DOCUMENTO_SEGUIMIENTO (para mes/año):\n";
$result = $c->query("
    SELECT ds.id, ds.documento_id, ds.mes, ds.ano, ds.estado
    FROM documento_seguimiento ds
    JOIN documentos d ON ds.documento_id = d.id
    JOIN items_transparencia i ON d.item_id = i.id
    WHERE i.nombre LIKE '%libro%diario%'
    ORDER BY ds.ano DESC, ds.mes DESC
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - Doc {$row['documento_id']}: Mes {$row['mes']}/{$row['ano']}, Estado: {$row['estado']}\n";
    }
} else {
    echo "  ✗ No hay registros en documento_seguimiento\n";
}

?>
