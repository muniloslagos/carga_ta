<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../../includes/check_auth.php';
require_role('administrativo');

// LUEGO: Incluir header con HTML
require_once '../../includes/header.php';

require_once '../../classes/Documento.php';
require_once '../../classes/Item.php';

$documentoClass = new Documento($db->getConnection());
$itemClass = new Item($db->getConnection());

$error = '';
$success = '';

// Procesar revisión de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $documento_id = intval($_POST['documento_id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    $comentarios = trim($_POST['comentarios'] ?? '');
    $redirect = false;

    if ($action === 'review') {
        if (!empty($estado) && in_array($estado, ['aprobado', 'rechazado'])) {
            $conn = $db->getConnection();
            
            // Actualizar documento
            $sql = "UPDATE documentos SET estado = ?, comentarios_revision = ?, 
                    revisado_por = ?, fecha_revision = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $user_id = $current_user['id'] ?? $_SESSION['user_id'];
            $stmt->bind_param("ssii", $estado, $comentarios, $user_id, $documento_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Documento revisado correctamente';
                $redirect = true;
            } else {
                $error = 'Error al revisar el documento';
            }
        }
    }
    
    // PRG Pattern: Redirigir después del POST exitoso
    if ($redirect) {
        header('Location: ' . SITE_URL . 'admin/documentos/index.php');
        exit;
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Obtener documentos
$conn = $db->getConnection();
$sql = "SELECT d.*, u.nombre as usuario_nombre, u.email as usuario_email, 
        i.nombre as item_nombre, i.numeracion
        FROM documentos d
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN items_transparencia i ON d.item_id = i.id
        ORDER BY d.fecha_subida DESC";
$documentos = $conn->query($sql);

// Filtros
$estado_filtro = $_GET['estado'] ?? '';
$item_filtro = intval($_GET['item'] ?? 0);
?>

<div class="page-header mb-4 pb-3" style="border-bottom: 2px solid #e0e0e0;">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(250,112,154,0.3);">
                    <i class="bi bi-file-earmark-text text-white" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h1 class="mb-1" style="color: #2c3e50; font-weight: 600; font-size: 1.5rem;">Gestión de Documentos</h1>
                    <small class="text-muted" style="font-size: 0.875rem;">Revisa y aprueba los documentos cargados</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $estado_filtro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="aprobado" <?php echo $estado_filtro === 'aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                    <option value="rechazado" <?php echo $estado_filtro === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Título</th>
                    <th>Usuario</th>
                    <th>Fecha Subida</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($doc = $documentos->fetch_assoc()): 
                    if ($estado_filtro && $doc['estado'] !== $estado_filtro) continue;
                ?>
                    <tr>
                        <td>
                            <small><strong><?php echo htmlspecialchars($doc['numeracion']); ?></strong></small>
                            <br><small class="text-muted"><?php echo htmlspecialchars($doc['item_nombre']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($doc['titulo']); ?></td>
                        <td>
                            <small><?php echo htmlspecialchars($doc['usuario_nombre']); ?></small>
                            <br><small class="text-muted"><?php echo htmlspecialchars($doc['usuario_email']); ?></small>
                        </td>
                        <td><small><?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></small></td>
                        <td>
                            <?php 
                            $estado_class = 'pending';
                            if ($doc['estado'] === 'aprobado') $estado_class = 'approved';
                            if ($doc['estado'] === 'rechazado') $estado_class = 'rejected';
                            ?>
                            <span class="state-badge <?php echo $estado_class; ?>"><?php echo ucfirst($doc['estado']); ?></span>
                        </td>
                        <td>
                            <a href="<?php echo htmlspecialchars($doc['archivo']); ?>" class="btn btn-sm btn-info" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                            <?php if ($doc['estado'] === 'pendiente'): ?>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal" 
                                        onclick="setDocId(<?php echo $doc['id']; ?>)">
                                    <i class="bi bi-eye"></i> Revisar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para revisar documento -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revisar Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="review">
                    <input type="hidden" name="documento_id" id="documentoId">

                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Seleccionar</option>
                            <option value="aprobado"><i class="bi bi-check-circle"></i> Aprobado</option>
                            <option value="rechazado"><i class="bi bi-x-circle"></i> Rechazado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="comentarios" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="comentarios" name="comentarios" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setDocId(docId) {
    document.getElementById('documentoId').value = docId;
}
</script>

<?php require_once '../../includes/footer.php'; ?>
