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

$observaciones = [];

// 1. Obtener observaciones de "Sin Movimiento"
$tableExists = $conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
if ($tableExists->num_rows > 0) {
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

    while ($row = $result->fetch_assoc()) {
        $observaciones[] = [
            'id' => $row['id'],
            'tipo' => 'sin_movimiento',
            'usuario' => $row['usuario_nombre'] ?? 'Usuario desconocido',
            'observacion' => $row['observacion'],
            'mes' => $row['mes'],
            'ano' => $row['ano'],
            'fecha' => $row['fecha_creacion']
        ];
    }
}

// 2. Obtener observaciones de documentos (rechazos del publicador)
$tableExists2 = $conn->query("SHOW TABLES LIKE 'observaciones_documentos'");
if ($tableExists2->num_rows > 0) {
    $sql = "SELECT od.*, 
            u_observador.nombre as observador_nombre,
            u_cargador.nombre as cargador_nombre,
            d.titulo as documento_titulo
            FROM observaciones_documentos od 
            LEFT JOIN usuarios u_observador ON od.observado_por = u_observador.id 
            LEFT JOIN usuarios u_cargador ON od.cargador_id = u_cargador.id
            LEFT JOIN documentos d ON od.documento_id = d.id
            WHERE od.item_id = ?";
    $params = [$item_id];
    $types = 'i';

    if ($mes !== null && $ano !== null) {
        $sql .= " AND od.mes = ? AND od.ano = ?";
        $params[] = $mes;
        $params[] = $ano;
        $types .= 'ii';
    }

    $sql .= " ORDER BY od.fecha_observacion DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $estado_texto = $row['resuelta'] ? 'Resuelta' : 'Pendiente';
        $observaciones[] = [
            'id' => $row['id'],
            'tipo' => 'documento_observado',
            'usuario' => $row['observador_nombre'] ?? 'Publicador',
            'cargador' => $row['cargador_nombre'],
            'documento' => $row['documento_titulo'],
            'observacion' => $row['observacion'],
            'estado' => $estado_texto,
            'resuelta' => $row['resuelta'],
            'mes' => $row['mes'],
            'ano' => $row['ano'],
            'fecha' => $row['fecha_observacion'],
            'fecha_resolucion' => $row['fecha_resolucion']
        ];
    }
}

// Ordenar por fecha descendente
usort($observaciones, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

echo json_encode(['success' => true, 'observaciones' => $observaciones]);
