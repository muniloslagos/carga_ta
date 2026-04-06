<?php
require_once dirname(dirname(__DIR__)) . '/includes/check_auth.php';

// Verificar permisos - Solo administrativo
if ($current_profile !== 'administrativo') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';

$conn = $db->getConnection();
$mensaje = '';
$error = '';
$tipo_mensaje = 'success';

// Verificar si existe la tabla anos_configurados
$checkTable = $conn->query("SHOW TABLES LIKE 'anos_configurados'");
$tabla_existe = $checkTable && $checkTable->num_rows > 0;

if (!$tabla_existe) {
    $error = 'La tabla de años configurados no existe. Ejecuta la migración: sql/migration_anos_configurados.sql';
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tabla_existe) {
    
    // Activar/Desactivar año
    if (isset($_POST['toggle_ano'])) {
        $ano_id = (int)$_POST['ano_id'];
        $nuevo_estado = (int)$_POST['nuevo_estado'];
        
        $stmt = $conn->prepare("UPDATE anos_configurados SET activo = ? WHERE id = ?");
        $stmt->bind_param('ii', $nuevo_estado, $ano_id);
        
        if ($stmt->execute()) {
            $mensaje = $nuevo_estado ? 'Año activado exitosamente' : 'Año desactivado exitosamente';
        } else {
            $error = 'Error al actualizar el año';
        }
        $stmt->close();
    }
    
    // Agregar nuevo año
    if (isset($_POST['agregar_ano'])) {
        $nuevo_ano = (int)$_POST['ano'];
        
        if ($nuevo_ano < 2020 || $nuevo_ano > 2050) {
            $error = 'El año debe estar entre 2020 y 2050';
        } else {
            $stmt = $conn->prepare("INSERT INTO anos_configurados (ano, activo, creado_por) VALUES (?, 1, ?)");
            $stmt->bind_param('ii', $nuevo_ano, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $mensaje = "Año $nuevo_ano agregado exitosamente";
            } else {
                if ($conn->errno === 1062) {
                    $error = "El año $nuevo_ano ya existe en el sistema";
                } else {
                    $error = 'Error al agregar el año';
                }
            }
            $stmt->close();
        }
    }
    
    // Eliminar año
    if (isset($_POST['eliminar_ano'])) {
        $ano_id = (int)$_POST['ano_id'];
        
        $stmt = $conn->prepare("DELETE FROM anos_configurados WHERE id = ?");
        $stmt->bind_param('i', $ano_id);
        
        if ($stmt->execute()) {
            $mensaje = 'Año eliminado exitosamente';
        } else {
            $error = 'Error al eliminar el año';
        }
        $stmt->close();
    }
}

// Obtener años configurados
$anos = [];
if ($tabla_existe) {
    $result = $conn->query("SELECT * FROM anos_configurados ORDER BY ano DESC");
    while ($row = $result->fetch_assoc()) {
        $anos[] = $row;
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h2><i class="bi bi-gear"></i> Configuración del Sistema</h2>
                <p class="text-muted">Administra la configuración general del sistema</p>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tabs de configuración -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="anos-tab" data-bs-toggle="tab" data-bs-target="#anos" type="button" role="tab">
                        <i class="bi bi-calendar-range"></i> Años
                    </button>
                </li>
                <!-- Aquí se agregarán más tabs de configuración en el futuro -->
            </ul>

            <div class="tab-content" id="configTabContent">
                <!-- Tab: Años -->
                <div class="tab-pane fade show active" id="anos" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-calendar-year"></i> Gestión de Años Disponibles</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#agregarAnoModal">
                                <i class="bi bi-plus-circle"></i> Agregar Año
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Los años activos estarán disponibles para selección en los dashboards y reportes.
                                Solo los años activos serán visibles para los usuarios.
                            </p>

                            <?php if (!$tabla_existe): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    La tabla de años configurados no existe. Por favor, ejecuta la migración SQL primero.
                                </div>
                            <?php elseif (empty($anos)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    No hay años configurados. Agrega al menos un año para comenzar.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Año</th>
                                                <th>Estado</th>
                                                <th>Fecha Creación</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($anos as $ano): ?>
                                                <tr>
                                                    <td><strong><?php echo $ano['ano']; ?></strong></td>
                                                    <td>
                                                        <?php if ($ano['activo']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle"></i> Activo
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="bi bi-x-circle"></i> Inactivo
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($ano['fecha_creacion'])); ?></td>
                                                    <td class="text-center">
                                                        <!-- Toggle activar/desactivar -->
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="ano_id" value="<?php echo $ano['id']; ?>">
                                                            <input type="hidden" name="nuevo_estado" value="<?php echo $ano['activo'] ? 0 : 1; ?>">
                                                            <button type="submit" name="toggle_ano" 
                                                                    class="btn btn-sm btn-<?php echo $ano['activo'] ? 'warning' : 'success'; ?>"
                                                                    onclick="return confirm('¿Estás seguro de <?php echo $ano['activo'] ? 'desactivar' : 'activar'; ?> este año?')">
                                                                <i class="bi bi-<?php echo $ano['activo'] ? 'pause-circle' : 'play-circle'; ?>"></i>
                                                                <?php echo $ano['activo'] ? 'Desactivar' : 'Activar'; ?>
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Eliminar -->
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="ano_id" value="<?php echo $ano['id']; ?>">
                                                            <button type="submit" name="eliminar_ano" 
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('¿Estás seguro de eliminar este año? Esta acción no se puede deshacer.')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Agregar Año -->
<div class="modal fade" id="agregarAnoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Agregar Nuevo Año</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ano" class="form-label">Año <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ano" name="ano" 
                               min="2020" max="2050" required 
                               placeholder="Ej: 2027">
                        <small class="text-muted">El año debe estar entre 2020 y 2050</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        El año se agregará como <strong>activo</strong> por defecto.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agregar_ano" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Agregar Año
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
