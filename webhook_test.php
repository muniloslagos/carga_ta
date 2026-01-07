<?php
/**
 * Test simple del webhook - Sin validación de firma
 * Solo para verificar que GitHub puede comunicarse
 */

$log_file = __DIR__ . '/webhook_test.log';

function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

write_log("========================================");
write_log("Webhook recibido!");

// Capturar headers
write_log("Headers recibidos:");
foreach (getallheaders() as $name => $value) {
    if (strpos($name, 'X-') === 0 || strpos($name, 'HTTP') === 0) {
        write_log("  $name: $value");
    }
}

// Capturar payload
$payload = file_get_contents('php://input');
write_log("Payload length: " . strlen($payload) . " bytes");

if ($payload) {
    $data = json_decode($payload, true);
    if ($data) {
        write_log("Evento: " . ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown'));
        write_log("Rama: " . str_replace('refs/heads/', '', $data['ref'] ?? 'unknown'));
        write_log("Commit: " . ($data['head_commit']['id'] ?? 'unknown'));
        write_log("Mensaje: " . ($data['head_commit']['message'] ?? 'sin mensaje'));
    }
}

write_log("Test completado - OK");

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Webhook test received',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
