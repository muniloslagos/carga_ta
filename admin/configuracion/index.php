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

// Verificar si existe la tabla configuracion_alcalde
$checkTableAlcalde = $conn->query("SHOW TABLES LIKE 'configuracion_alcalde'");
$tabla_alcalde_existe = $checkTableAlcalde && $checkTableAlcalde->num_rows > 0;

// Verificar si existe la tabla configuracion_smtp
$checkTableSmtp = $conn->query("SHOW TABLES LIKE 'configuracion_smtp'");
$tabla_smtp_existe = $checkTableSmtp && $checkTableSmtp->num_rows > 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Configuración General
    if (isset($_POST['guardar_general'])) {
        $max_file_size = (int)$_POST['max_file_size'];
        
        if ($max_file_size < 1 || $max_file_size > 500) {
            $error = 'El tamaño máximo debe estar entre 1 y 500 MB';
        } else {
            // Guardar en tabla de configuración (usaremos configuracion_alcalde por ahora o crear nueva tabla)
            // Por simplicidad, guardaremos como configuración en archivo o tabla general
            $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor) VALUES ('max_file_size_mb', ?) 
                                    ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->bind_param('ss', $max_file_size, $max_file_size);
            
            if ($stmt->execute()) {
                $mensaje = 'Configuración general guardada exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $error = 'Error al guardar la configuración general';
            }
            $stmt->close();
        }
    }
    
    // Configuración SMTP
    if (isset($_POST['guardar_smtp']) && $tabla_smtp_existe) {
        try {
            $smtp_host = trim($_POST['smtp_host'] ?? '');
            $smtp_port = (int)($_POST['smtp_port'] ?? 587);
            $smtp_usuario = trim($_POST['smtp_usuario'] ?? '');
            $smtp_password = trim($_POST['smtp_password'] ?? '');
            $smtp_encriptacion = $_POST['smtp_encriptacion'] ?? 'tls';
            $smtp_de_correo = trim($_POST['smtp_de_correo'] ?? '');
            $smtp_de_nombre = trim($_POST['smtp_de_nombre'] ?? 'Sistema de Transparencia');
            $smtp_activo = isset($_POST['smtp_activo']) ? 1 : 0;
            
            // Validaciones
            if (empty($smtp_host)) {
                throw new Exception('El servidor SMTP es obligatorio');
            }
            if (empty($smtp_usuario)) {
                throw new Exception('El usuario SMTP es obligatorio');
            }
            if (empty($smtp_de_correo)) {
                throw new Exception('El correo del remitente es obligatorio');
            }
            if (!filter_var($smtp_de_correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El correo del remitente no es válido');
            }
            
            // Verificar si existe configuración
            $config_existe = $conn->query("SELECT id FROM configuracion_smtp LIMIT 1")->fetch_assoc();
            
            if ($config_existe) {
                // Actualizar
                $stmt = $conn->prepare("UPDATE configuracion_smtp SET 
                    smtp_host = ?, 
                    smtp_port = ?, 
                    smtp_usuario = ?, 
                    smtp_password = ?, 
                    smtp_encriptacion = ?, 
                    smtp_de_correo = ?, 
                    smtp_de_nombre = ?, 
                    smtp_activo = ?,
                    modificado_por = ?
                    WHERE id = ?");
                $stmt->bind_param('sissssssii', 
                    $smtp_host, 
                    $smtp_port, 
                    $smtp_usuario, 
                    $smtp_password, 
                    $smtp_encriptacion, 
                    $smtp_de_correo, 
                    $smtp_de_nombre, 
                    $smtp_activo,
                    $_SESSION['user_id'],
                    $config_existe['id']
                );
            } else {
                // Insertar
                $stmt = $conn->prepare("INSERT INTO configuracion_smtp 
                    (smtp_host, smtp_port, smtp_usuario, smtp_password, smtp_encriptacion, smtp_de_correo, smtp_de_nombre, smtp_activo, modificado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sisssssii', 
                    $smtp_host, 
                    $smtp_port, 
                    $smtp_usuario, 
                    $smtp_password, 
                    $smtp_encriptacion, 
                    $smtp_de_correo, 
                    $smtp_de_nombre, 
                    $smtp_activo,
                    $_SESSION['user_id']
                );
            }
            
            if ($stmt->execute()) {
                $mensaje = 'Configuración SMTP guardada exitosamente';
                $tipo_mensaje = 'success';
            } else {
                throw new Exception('Error al guardar la configuración');
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Configuración del Alcalde
    if (isset($_POST['guardar_alcalde']) && $tabla_alcalde_existe) {
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $correo = trim($_POST['correo']);
        $subrogante_1 = !empty($_POST['subrogante_1']) ? (int)$_POST['subrogante_1'] : null;
        $subrogante_2 = !empty($_POST['subrogante_2']) ? (int)$_POST['subrogante_2'] : null;
        $subrogante_3 = !empty($_POST['subrogante_3']) ? (int)$_POST['subrogante_3'] : null;
        
        if (empty($nombre) || empty($apellidos) || empty($correo)) {
            $error = 'El nombre, apellidos y correo del alcalde son obligatorios';
        } else if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo electrónico no es válido';
        } else {
            // Verificar si ya existe configuración
            $check = $conn->query("SELECT id FROM configuracion_alcalde WHERE activo = 1 LIMIT 1");
            
            if ($check && $check->num_rows > 0) {
                // Actualizar
                $alcalde_id = $check->fetch_assoc()['id'];
                $stmt = $conn->prepare("UPDATE configuracion_alcalde SET nombre = ?, apellidos = ?, correo = ?, subrogante_1_id = ?, subrogante_2_id = ?, subrogante_3_id = ?, modificado_por = ? WHERE id = ?");
                $stmt->bind_param('sssiiiii', $nombre, $apellidos, $correo, $subrogante_1, $subrogante_2, $subrogante_3, $_SESSION['user_id'], $alcalde_id);
            } else {
                // Insertar nuevo
                $stmt = $conn->prepare("INSERT INTO configuracion_alcalde (nombre, apellidos, correo, subrogante_1_id, subrogante_2_id, subrogante_3_id, modificado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssiiii', $nombre, $apellidos, $correo, $subrogante_1, $subrogante_2, $subrogante_3, $_SESSION['user_id']);
            }
            
            if ($stmt->execute()) {
                $mensaje = 'Configuración del alcalde guardada exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $error = 'Error al guardar la configuración del alcalde';
            }
            $stmt->close();
        }
    }
    
    // Activar/Desactivar año
    if (isset($_POST['toggle_ano']) && $tabla_existe) {
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

// Obtener configuración del alcalde
$alcalde = null;
if ($tabla_alcalde_existe) {
    $result = $conn->query("SELECT * FROM configuracion_alcalde WHERE activo = 1 LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $alcalde = $result->fetch_assoc();
    }
}

// Obtener lista de directores para subrogantes
$directores = [];
$result_dir = $conn->query("SELECT id, nombres, apellidos, correo FROM directores WHERE activo = 1 ORDER BY apellidos, nombres");
if ($result_dir) {
    while ($row = $result_dir->fetch_assoc()) {
        $directores[] = $row;
    }
}

// Obtener configuración SMTP
$config_smtp = null;
if ($tabla_smtp_existe) {
    $result = $conn->query("SELECT * FROM configuracion_smtp ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $config_smtp = $result->fetch_assoc();
    }
}

if (!$config_smtp) {
    $config_smtp = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_usuario' => '',
        'smtp_password' => '',
        'smtp_encriptacion' => 'tls',
        'smtp_de_correo' => '',
        'smtp_de_nombre' => 'Sistema de Transparencia Activa',
        'smtp_activo' => 0,
        'smtp_verificado' => 0
    ];
}

// Obtener configuración general
$max_file_size = 200; // Valor por defecto en MB
$result_config = $conn->query("SELECT valor FROM configuracion WHERE clave = 'max_file_size_mb'");
if ($result_config && $result_config->num_rows > 0) {
    $max_file_size = (int)$result_config->fetch_assoc()['valor'];
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
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="bi bi-gear"></i> General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="anos-tab" data-bs-toggle="tab" data-bs-target="#anos" type="button" role="tab">
                        <i class="bi bi-calendar-range"></i> Años
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="alcalde-tab" data-bs-toggle="tab" data-bs-target="#alcalde" type="button" role="tab">
                        <i class="bi bi-person-badge-fill"></i> Alcalde
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="smtp-tab" data-bs-toggle="tab" data-bs-target="#smtp" type="button" role="tab">
                        <i class="bi bi-envelope-at"></i> SMTP
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="configTabContent">
                <!-- Tab: General -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-gear"></i> Configuración General del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <strong><i class="bi bi-file-earmark-arrow-up"></i> Límites de Carga</strong>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="max_file_size" class="form-label">Tamaño Máximo de Archivo <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                                               value="<?= htmlspecialchars($max_file_size) ?>" min="1" max="500" required>
                                                        <span class="input-group-text">MB</span>
                                                    </div>
                                                    <small class="text-muted">Tamaño máximo permitido para cargar archivos (1-500 MB)</small>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle"></i>
                                                    <strong>Nota:</strong> Este límite se aplica a todos los tipos de documentos cargados en el sistema.
                                                    <br><small>Límite actual: <strong><?= $max_file_size ?> MB</strong></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="alert alert-warning">
                                            <h6><i class="bi bi-exclamation-triangle"></i> Configuración del Servidor</h6>
                                            <p>Para que este límite funcione correctamente, asegúrese de configurar también:</p>
                                            <ul class="mb-0">
                                                <li><code>upload_max_filesize</code> en php.ini</li>
                                                <li><code>post_max_size</code> en php.ini</li>
                                                <li><code>max_execution_time</code> en php.ini</li>
                                            </ul>
                                            <hr>
                                            <p class="mb-0"><small>Reinicie el servidor Apache después de modificar php.ini</small></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="guardar_general" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Guardar Configuración General
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Años -->
                <div class="tab-pane fade" id="anos" role="tabpanel">
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

                <!-- Tab: Alcalde -->
                <div class="tab-pane fade" id="alcalde" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-person-badge-fill"></i> Configuración del Alcalde y Subrogantes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!$tabla_alcalde_existe): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Tabla no encontrada:</strong> Ejecuta la migración: <code>sql/migration_configuracion_alcalde.sql</code>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">
                                    Configura los datos del Alcalde y hasta 3 subrogantes (directores) que podrán recibir el informe de cierre del proceso cuando el alcalde no esté disponible.
                                </p>

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-3">
                                                <div class="card-header bg-primary text-white">
                                                    <strong><i class="bi bi-person-fill"></i> Datos del Alcalde</strong>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($alcalde['nombre'] ?? '') ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="apellidos" value="<?= htmlspecialchars($alcalde['apellidos'] ?? '') ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                                        <input type="email" class="form-control" name="correo" value="<?= htmlspecialchars($alcalde['correo'] ?? '') ?>" required>
                                                        <small class="text-muted">Aquí se enviará el informe de cierre del proceso</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-3">
                                                <div class="card-header bg-secondary text-white">
                                                    <strong><i class="bi bi-people-fill"></i> Subrogantes (Opcional)</strong>
                                                </div>
                                                <div class="card-body">
                                                    <p class="small text-muted">Selecciona hasta 3 directores que puedan actuar como subrogantes del alcalde.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Subrogante 1</label>
                                                        <select class="form-select" name="subrogante_1">
                                                            <option value="">-- Sin asignar --</option>
                                                            <?php foreach ($directores as $dir): ?>
                                                                <option value="<?= $dir['id'] ?>" <?= ($alcalde && $alcalde['subrogante_1_id'] == $dir['id']) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($dir['apellidos'] . ', ' . $dir['nombres']) ?> (<?= htmlspecialchars($dir['correo']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Subrogante 2</label>
                                                        <select class="form-select" name="subrogante_2">
                                                            <option value="">-- Sin asignar --</option>
                                                            <?php foreach ($directores as $dir): ?>
                                                                <option value="<?= $dir['id'] ?>" <?= ($alcalde && $alcalde['subrogante_2_id'] == $dir['id']) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($dir['apellidos'] . ', ' . $dir['nombres']) ?> (<?= htmlspecialchars($dir['correo']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Subrogante 3</label>
                                                        <select class="form-select" name="subrogante_3">
                                                            <option value="">-- Sin asignar --</option>
                                                            <?php foreach ($directores as $dir): ?>
                                                                <option value="<?= $dir['id'] ?>" <?= ($alcalde && $alcalde['subrogante_3_id'] == $dir['id']) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($dir['apellidos'] . ', ' . $dir['nombres']) ?> (<?= htmlspecialchars($dir['correo']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="guardar_alcalde" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Guardar Configuración
                                        </button>
                                    </div>
                                </form>

                                <?php if ($alcalde): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Última modificación:</strong> <?= date('d/m/Y H:i', strtotime($alcalde['fecha_modificacion'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: SMTP -->
                <div class="tab-pane fade" id="smtp" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-envelope-at"></i> Configuración de Correo Electrónico (SMTP)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!$tabla_smtp_existe): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Tabla no encontrada:</strong> Ejecuta la migración SMTP primero.
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Configure el servidor SMTP para el envío de notificaciones automáticas</p>
                                
                                <form method="POST">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Información importante:</strong> Configure correctamente el servidor SMTP para que el sistema pueda enviar notificaciones automáticas.
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="smtp_host" class="form-label">Servidor SMTP <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                   value="<?= htmlspecialchars($config_smtp['smtp_host']) ?>" required
                                                   placeholder="smtp.gmail.com">
                                            <small class="text-muted">Ejemplo: smtp.gmail.com, smtp.office365.com</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="smtp_port" class="form-label">Puerto <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                   value="<?= htmlspecialchars($config_smtp['smtp_port']) ?>" required
                                                   min="1" max="65535">
                                            <small class="text-muted">TLS: 587 | SSL: 465</small>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_encriptacion" class="form-label">Tipo de Encriptación</label>
                                        <select class="form-select" id="smtp_encriptacion" name="smtp_encriptacion">
                                            <option value="tls" <?= $config_smtp['smtp_encriptacion'] === 'tls' ? 'selected' : '' ?>>TLS (recomendado)</option>
                                            <option value="ssl" <?= $config_smtp['smtp_encriptacion'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                            <option value="none" <?= $config_smtp['smtp_encriptacion'] === 'none' ? 'selected' : '' ?>>Sin encriptación</option>
                                        </select>
                                    </div>

                                    <hr class="my-4">

                                    <h6 class="mb-3"><i class="bi bi-person-lock"></i> Credenciales</h6>

                                    <div class="mb-3">
                                        <label for="smtp_usuario" class="form-label">Usuario SMTP <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="smtp_usuario" name="smtp_usuario" 
                                               value="<?= htmlspecialchars($config_smtp['smtp_usuario']) ?>" required
                                               placeholder="correo@muniloslagos.cl">
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">Contraseña SMTP <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                               value="<?= htmlspecialchars($config_smtp['smtp_password']) ?>"
                                               placeholder="Contraseña o App Password">
                                        <small class="text-muted">
                                            <i class="bi bi-shield-lock"></i> Para Gmail, use una App Password
                                        </small>
                                    </div>

                                    <hr class="my-4">

                                    <h6 class="mb-3"><i class="bi bi-envelope"></i> Remitente</h6>

                                    <div class="mb-3">
                                        <label for="smtp_de_correo" class="form-label">Correo del Remitente <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="smtp_de_correo" name="smtp_de_correo" 
                                               value="<?= htmlspecialchars($config_smtp['smtp_de_correo']) ?>" required
                                               placeholder="transparencia@muniloslagos.cl">
                                    </div>

                                    <div class="mb-3">
                                        <label for="smtp_de_nombre" class="form-label">Nombre del Remitente</label>
                                        <input type="text" class="form-control" id="smtp_de_nombre" name="smtp_de_nombre" 
                                               value="<?= htmlspecialchars($config_smtp['smtp_de_nombre']) ?>"
                                               placeholder="Sistema de Transparencia Activa">
                                    </div>

                                    <hr class="my-4">

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="smtp_activo" name="smtp_activo" 
                                                   <?= $config_smtp['smtp_activo'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="smtp_activo">
                                                <strong>Activar envío de correos automáticos</strong>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="guardar_smtp" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Guardar Configuración SMTP
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="alert alert-secondary mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    Para más opciones de configuración y pruebas, visite: 
                                    <a href="<?= SITE_URL ?>admin/smtp/" class="alert-link">Configuración SMTP Completa</a>
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
