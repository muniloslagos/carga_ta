<?php
require_once '../includes/check_auth.php';
require_login();
require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : null;

if (!$item_id) {
    echo json_encode(['success' => false, 'error' => 'item_id no proporcionado']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verificar que la tabla existe
$tableExists = $conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
if ($tableExists->num_rows === 0) {
    echo json_encode(['success' => true, 'observaciones' => []]);
    exit;
}

$sql = "SELECT o.*, u.nombre as usuario_nombre 
        FROM observaciones_sin_movimiento o 
        LEFT JOIN usuarios u ON o.usuario_id = u.id 
        WHERE o.item_id = ?";
$params = [$item_id];
$types = 'i';

if ($mes !== null && $ano !== null) {
    $sql .= " AND o.mes = ? AND o.ano = ?";
    $params[] = $mes;
    $params[] = $ano;
    $types .= 'ii';
}

$sql .= " ORDER BY o.fecha_creacion DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$observaciones = [];
while ($row = $result->fetch_assoc()) {
    $observaciones[] = [
        'id' => $row['id'],
        'usuario' => $row['usuario_nombre'] ?? 'Usuario desconocido',
        'observacion' => $row['observacion'],
        'mes' => $row['mes'],
        'ano' => $row['ano'],
        'fecha' => $row['fecha_creacion']
    ];
}

echo json_encode(['success' => true, 'observaciones' => $observaciones]);
