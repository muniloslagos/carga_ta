<?php
require_once '../includes/check_auth.php';
require_login();

$perfil = $current_profile ?? ($current_user['perfil'] ?? '');
if ($perfil === 'auditor') {
    header('Location: ' . SITE_URL . 'usuario/dashboard_auditor.php');
    exit;
}

require_once '../includes/header.php';

$conn = $db->getConnection();
$user_id = (int)($current_user['id'] ?? 0);

$nombreItemEspecial = 'Elecciones - Juntas de vecinos y organizaciones comunitarias - Ley 21.146';

$wherePerfil = '';
$params = [$nombreItemEspecial];
$types = 's';

if ($perfil === 'cargador_informacion') {
    $wherePerfil = ' AND EXISTS (SELECT 1 FROM item_usuarios iu WHERE iu.item_id = i.id AND iu.usuario_id = ?)';
    $params[] = $user_id;
    $types .= 'i';
} elseif ($perfil === 'publicador') {
    $checkPubTable = $conn->query("SHOW TABLES LIKE 'item_publicadores'");
    if ($checkPubTable && $checkPubTable->num_rows > 0) {
        $wherePerfil = ' AND EXISTS (SELECT 1 FROM item_publicadores ip WHERE ip.item_id = i.id AND ip.usuario_id = ?)';
        $params[] = $user_id;
        $types .= 'i';
    }
}

$sqlItems = "SELECT i.id, i.numeracion, i.nombre, d.nombre as direccion_nombre
             FROM items_transparencia i
             LEFT JOIN direcciones d ON d.id = i.direccion_id
             WHERE i.activo = 1
               AND i.periodicidad = 'ocurrencia'
               AND i.nombre = ?
               $wherePerfil
             ORDER BY i.id DESC";

$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param($types, ...$params);
$stmtItems->execute();
$resItems = $stmtItems->get_result();

$items = [];
while ($row = $resItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

$mesActual = (int)date('n');
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
?>

<div class="page-header mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1"><i class="bi bi-person-check"></i> Pestaña Especial: Elecciones</h1>
            <small class="text-muted"><?php echo htmlspecialchars($nombreItemEspecial); ?></small>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo SITE_URL; ?>usuario/dashboard.php">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    Este módulo no usa plazos periódicos. Puede cargar archivos cuando corresponda al proceso de elecciones.
</div>

<?php if (empty($items)): ?>
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-folder-x" style="font-size: 2rem;"></i>
            <p class="mt-2 mb-0">No tiene items especiales de Elecciones asignados.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                    <small class="text-muted d-block">Dirección: <?php echo htmlspecialchars($item['direccion_nombre'] ?? 'Sin dirección'); ?></small>
                </div>
                <span class="badge bg-secondary">Numeración <?php echo htmlspecialchars($item['numeracion']); ?></span>
            </div>
            <div class="card-body">
                <?php if ($perfil !== 'publicador'): ?>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCargarElecciones"
                            onclick="abrirModalElecciones(<?php echo (int)$item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['nombre'])); ?>', 'excel')">
                        <i class="bi bi-file-earmark-excel"></i> Cargar Excel
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargarElecciones"
                            onclick="abrirModalElecciones(<?php echo (int)$item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['nombre'])); ?>', 'documento')">
                        <i class="bi bi-file-earmark-text"></i> Cargar Documento
                    </button>
                </div>
                <?php endif; ?>

                <h6 class="mb-2"><i class="bi bi-clock-history"></i> Últimos documentos cargados</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Archivo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmtDocs = $conn->prepare("SELECT id, titulo, archivo, estado, fecha_subida
                                                        FROM documentos
                                                        WHERE item_id = ?
                                                        ORDER BY fecha_subida DESC
                                                        LIMIT 15");
                            $stmtDocs->bind_param('i', $item['id']);
                            $stmtDocs->execute();
                            $resDocs = $stmtDocs->get_result();

                            if ($resDocs->num_rows === 0):
                            ?>
                                <tr><td colspan="5" class="text-center text-muted">Sin documentos cargados aún</td></tr>
                            <?php
                            else:
                                while ($doc = $resDocs->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($doc['titulo']); ?></td>
                                    <td><small><?php echo htmlspecialchars($doc['archivo']); ?></small></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></td>
                                    <td><span class="badge bg-<?php echo ($doc['estado'] === 'Publicado' ? 'success' : 'warning text-dark'); ?>"><?php echo htmlspecialchars($doc['estado']); ?></span></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-secondary" href="descargar_documento.php?doc_id=<?php echo (int)$doc['id']; ?>">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            endif;
                            $stmtDocs->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="modal fade" id="modalCargarElecciones" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="tituloModalElecciones">Cargar archivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="enviar_documento.php">
                <div class="modal-body">
                    <input type="hidden" id="elecItemId" name="item_id">
                    <input type="hidden" id="elecMesCarga" name="mes_carga" value="<?php echo $mesActual; ?>">

                    <div class="mb-2 text-muted small">
                        Mes de registro actual: <?php echo $meses[$mesActual]; ?> <?php echo date('Y'); ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input class="form-control" type="text" id="elecTitulo" name="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comentario (opcional)</label>
                        <textarea class="form-control" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Archivo</label>
                        <input class="form-control" type="file" id="elecArchivo" name="archivo" required>
                        <small class="text-muted">Para Excel: .xls/.xlsx/.xlsm/.csv. Para documento: PDF, Word, imágenes, ZIP.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalElecciones(itemId, itemNombre, tipoArchivo) {
    document.getElementById('elecItemId').value = itemId;

    const esExcel = tipoArchivo === 'excel';
    const tituloBase = esExcel ? 'Elecciones - Excel' : 'Elecciones - Documento';
    const mes = '<?php echo $meses[$mesActual]; ?>';
    const ano = '<?php echo date('Y'); ?>';

    document.getElementById('tituloModalElecciones').textContent = (esExcel ? 'Cargar Excel' : 'Cargar Documento') + ' - ' + itemNombre;
    document.getElementById('elecTitulo').value = tituloBase + ' - ' + mes + ' ' + ano;

    const inputFile = document.getElementById('elecArchivo');
    if (esExcel) {
        inputFile.setAttribute('accept', '.xls,.xlsx,.xlsm,.csv');
    } else {
        inputFile.setAttribute('accept', '.pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar,.7z');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
