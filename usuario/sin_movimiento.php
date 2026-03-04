<?php
require_once '../includes/check_auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$user_id    = $current_user['id'];
$item_id    = (int)($_POST['item_id']    ?? 0);
$mes_carga  = (int)($_POST['mes_carga']  ?? 0);
$ano_carga  = (int)($_POST['ano_carga']  ?? (int)date('Y'));

if (!$item_id || !$mes_carga) {
    $_SESSION['error'] = 'Datos inválidos';
    header('Location: dashboard.php');
    exit;
}

$conn = $db->getConnection();

// Verificar que no existe ya un documento para este item/mes/año
$checkSql = "SELECT d.id FROM documentos d
             JOIN documento_seguimiento ds ON d.id = ds.documento_id
             WHERE d.item_id = ? AND ds.mes = ? AND ds.ano = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param('iii', $item_id, $mes_carga, $ano_carga);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = 'Ya existe un documento registrado para este período.';
    header('Location: dashboard.php?mes=' . $mes_carga . '&ano=' . $ano_carga);
    exit;
}

// Obtener nombre del item
$stmt = $conn->prepare("SELECT nombre FROM items_transparencia WHERE id = ?");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
if (!$item) {
    $_SESSION['error'] = 'Item no encontrado.';
    header('Location: dashboard.php');
    exit;
}

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$titulo = $item['nombre'] . ' - Sin Movimiento - ' . $meses[$mes_carga] . ' ' . $ano_carga;
$ahora  = date('Y-m-d H:i:s');

$conn->begin_transaction();
try {
    // Insertar documento con archivo sentinel 'sin_movimiento'
    $sqlDoc = "INSERT INTO documentos (item_id, usuario_id, titulo, archivo, estado, descripcion, fecha_subida)
               VALUES (?, ?, ?, 'sin_movimiento', 'pendiente', 'Sin movimiento declarado por el cargador de información', ?)";
    $stmt = $conn->prepare($sqlDoc);
    $stmt->bind_param('iiss', $item_id, $user_id, $titulo, $ahora);
    $stmt->execute();
    $doc_id = $conn->insert_id;

    // Insertar seguimiento
    $sqlSeg = "INSERT INTO documento_seguimiento (documento_id, item_id, usuario_id, mes, ano, fecha_envio)
               VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlSeg);
    $stmt->bind_param('iiiiss', $doc_id, $item_id, $user_id, $mes_carga, $ano_carga, $ahora);
    $stmt->execute();

    $conn->commit();
    $_SESSION['success'] = 'Se registró "Sin Movimiento" para ' . htmlspecialchars($item['nombre']) . ' — ' . $meses[$mes_carga] . ' ' . $ano_carga . '.';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Error al registrar Sin Movimiento: ' . $e->getMessage();
}

header('Location: dashboard.php?mes=' . $mes_carga . '&ano=' . $ano_carga);
exit;
