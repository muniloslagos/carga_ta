<?php
/**
 * AJAX endpoint para gestionar notas de items
 * Acciones: list, create, delete
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/check_auth.php';
require_login();
require_role('administrativo');

header('Content-Type: application/json');

$conn = $db->getConnection();
$action = $_REQUEST['action'] ?? '';
$uploadDir = dirname(dirname(__DIR__)) . '/uploads/notas/';

// Crear directorio si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

switch ($action) {
    case 'list':
        $item_id = (int)($_GET['item_id'] ?? 0);
        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Item no válido']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT n.*, u.nombre as usuario_nombre 
            FROM item_notas n 
            JOIN usuarios u ON n.usuario_id = u.id 
            WHERE n.item_id = ? 
            ORDER BY n.fecha_registro DESC
        ");
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notas = [];
        while ($row = $result->fetch_assoc()) {
            $notas[] = $row;
        }

        echo json_encode(['success' => true, 'notas' => $notas]);
        break;

    case 'create':
        $item_id = (int)($_POST['item_id'] ?? 0);
        $nota = trim($_POST['nota'] ?? '');
        $usuario_id = (int)$_SESSION['user_id'];

        if (!$item_id || empty($nota)) {
            echo json_encode(['success' => false, 'message' => 'Item y nota son requeridos']);
            exit;
        }

        $archivo = null;
        $archivo_original = null;

        // Procesar archivo adjunto si existe
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['archivo'];
            $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowedTypes)]);
                exit;
            }

            if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
                echo json_encode(['success' => false, 'message' => 'El archivo no debe superar los 10MB']);
                exit;
            }

            $archivo = 'nota_' . $item_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $archivo_original = $file['name'];

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $archivo)) {
                echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
                exit;
            }
        }

        $stmt = $conn->prepare("INSERT INTO item_notas (item_id, usuario_id, nota, archivo, archivo_original) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iisss', $item_id, $usuario_id, $nota, $archivo, $archivo_original);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota agregada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la nota']);
        }
        break;

    case 'delete':
        $nota_id = (int)($_POST['nota_id'] ?? 0);
        if (!$nota_id) {
            echo json_encode(['success' => false, 'message' => 'Nota no válida']);
            exit;
        }

        // Obtener archivo antes de borrar
        $stmt = $conn->prepare("SELECT archivo FROM item_notas WHERE id = ?");
        $stmt->bind_param('i', $nota_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && !empty($row['archivo'])) {
            $filePath = $uploadDir . $row['archivo'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM item_notas WHERE id = ?");
        $stmt->bind_param('i', $nota_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la nota']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
