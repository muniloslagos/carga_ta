<?php
require_once dirname(dirname(__DIR__)) . '/includes/check_auth.php';

// Verificar permisos - Solo administrativo
if ($current_profile !== 'administrativo') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
require_once dirname(dirname(__DIR__)) . '/classes/EmailSender.php';

$conn = $db->getConnection();
$mensaje = '';
$error = '';
$tipo_mensaje = 'success';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Guardar plantilla editada
    if (isset($_POST['guardar_plantilla'])) {
        try {
            $tipo = $_POST['tipo_plantilla'];
            $asunto = trim($_POST['asunto']);
            $cuerpo = trim($_POST['cuerpo']);
            $envio_automatico = isset($_POST['envio_automatico']) ? 1 : 0;
            
            if (empty($asunto) || empty($cuerpo)) {
                throw new Exception('El asunto y el cuerpo son obligatorios');
            }
            
            $stmt = $conn->prepare("UPDATE plantillas_correo SET 
                asunto = ?, 
                cuerpo = ?, 
                envio_automatico = ?,
                modificado_por = ?
                WHERE tipo = ?");
            $stmt->bind_param('ssiss', $asunto, $cuerpo, $envio_automatico, $_SESSION['user_id'], $tipo);
            
            if ($stmt->execute()) {
                $mensaje = 'Plantilla guardada exitosamente';
                $tipo_mensaje = 'success';
            } else {
                throw new Exception('Error al guardar la plantilla');
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar correo masivo (inicio de proceso)
    elseif (isset($_POST['enviar_masivo_inicio'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $mes = (int)$_POST['mes_periodo'];
            $ano = (int)$_POST['ano_periodo'];
            $enviar_a_directores = isset($_POST['enviar_a_directores_inicio']) && $_POST['enviar_a_directores_inicio'] === '1';
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarInicioProceso($mes, $ano, $enviar_a_directores);
            
            $mensaje = "Envío completado: {$resultado['exitosos']} correos enviados ({$resultado['cargadores']} cargadores";
            if ($resultado['directores'] > 0) {
                $mensaje .= ", {$resultado['directores']} directores";
            }
            $mensaje .= "), {$resultado['fallidos']} fallidos";
            $tipo_mensaje = $resultado['fallidos'] > 0 ? 'warning' : 'success';
            
        } catch (Exception $e) {
            $error = 'Error en envío masivo: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar correo individual (inicio de proceso)
    elseif (isset($_POST['enviar_individual_inicio'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $usuario_id = (int)$_POST['usuario_id'];
            $mes = (int)$_POST['mes_periodo'];
            $ano = (int)$_POST['ano_periodo'];
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarInicioProcesoIndividual($usuario_id, $mes, $ano);
            
            $mensaje = "Correo enviado exitosamente al usuario";
            $tipo_mensaje = 'success';
            
        } catch (Exception $e) {
            $error = 'Error al enviar: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar correo individual a director (inicio de proceso)
    elseif (isset($_POST['enviar_individual_inicio_director'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $director_id = (int)$_POST['director_id'];
            $mes = (int)$_POST['mes_periodo'];
            $ano = (int)$_POST['ano_periodo'];
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarInicioProcesoDirector($director_id, $mes, $ano);
            
            $mensaje = "Correo enviado exitosamente al director";
            $tipo_mensaje = 'success';
            
        } catch (Exception $e) {
            $error = 'Error al enviar: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar correo masivo (fin de proceso cargadores)
    elseif (isset($_POST['enviar_masivo_fin_cargadores'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $mes = (int)$_POST['mes_periodo'];
            $ano = (int)$_POST['ano_periodo'];
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarFinProcesoCargadores($mes, $ano);
            
            $mensaje = "Envío masivo completado: {$resultado['exitosos']} correos enviados, {$resultado['fallidos']} fallidos";
            $tipo_mensaje = $resultado['fallidos'] > 0 ? 'warning' : 'success';
            
        } catch (Exception $e) {
            $error = 'Error en envío masivo: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar correo individual (fin de proceso cargadores)
    elseif (isset($_POST['enviar_individual_fin_cargadores'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $usuario_id = (int)$_POST['usuario_id'];
            $mes = (int)$_POST['mes_periodo'];
            $ano = (int)$_POST['ano_periodo'];
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarFinProcesoCargadoresIndividual($usuario_id, $mes, $ano);
            
            $mensaje = "Correo enviado exitosamente";
            $tipo_mensaje = 'success';
            
        } catch (Exception $e) {
            $error = 'Error al enviar: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar correo masivo fin de proceso general (a directores)
    elseif (isset($_POST['enviar_masivo_fin_general'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $mes = (int)$_POST['mes_periodo'];
            $ano = (int)$_POST['ano_periodo'];
            $enviar_a_auditores = isset($_POST['enviar_a_auditores']) && $_POST['enviar_a_auditores'] === '1';
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarFinProcesoGeneral($mes, $ano, $enviar_a_auditores);
            
            $mensaje = "Envío completado: {$resultado['exitosos']} correos enviados ({$resultado['directores']} directores";
            if ($resultado['auditores'] > 0) {
                $mensaje .= ", {$resultado['auditores']} auditores";
            }
            $mensaje .= "), {$resultado['fallidos']} fallidos";
            if (!empty($resultado['enlace_resumen'])) {
                $mensaje .= " | Enlace público generado";
            }
            $tipo_mensaje = $resultado['fallidos'] > 0 ? 'warning' : 'success';
            
        } catch (Exception $e) {
            $error = 'Error en envío: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Enviar contraseñas masivo
    // Enviar contraseña individual
    elseif (isset($_POST['enviar_password_individual'])) {
        try {
            require_once dirname(dirname(__DIR__)) . '/classes/CorreoManager.php';
            
            $usuario_id = (int)$_POST['usuario_id'];
            $nueva_password = trim($_POST['nueva_password']);
            
            $correo_manager = new CorreoManager();
            $resultado = $correo_manager->enviarPasswordIndividual($usuario_id, $nueva_password);
            
            $mensaje = "Contraseña actualizada y enviada exitosamente al usuario";
            $tipo_mensaje = 'success';
            
        } catch (Exception $e) {
            $error = 'Error al enviar: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}

// Obtener plantillas
$plantillas = [];
$result = $conn->query("SELECT * FROM plantillas_correo ORDER BY FIELD(tipo, 'inicio_proceso', 'fin_proceso_cargadores', 'fin_proceso_general')");
while ($row = $result->fetch_assoc()) {
    $plantillas[$row['tipo']] = $row;
}

// Obtener usuarios cargadores para selector
$cargadores_query = "SELECT id, nombre, email FROM usuarios WHERE perfil = 'cargador_informacion' AND activo = 1 ORDER BY nombre";
$cargadores = $conn->query($cargadores_query);
$cargadores2 = $conn->query($cargadores_query); // Segunda copia para el segundo form

// Obtener directores para tab fin proceso general
$directores_query = "SELECT id, nombres, apellidos, correo FROM directores WHERE activo = 1 AND correo IS NOT NULL AND correo != '' ORDER BY apellidos, nombres";
$directores_list = $conn->query($directores_query);

// Obtener auditores para tab fin proceso general
$auditores_query = "SELECT id, nombre, email FROM usuarios WHERE perfil = 'auditor' AND activo = 1 AND email IS NOT NULL AND email != '' ORDER BY nombre";
$auditores_list = $conn->query($auditores_query);

// Obtener todos los usuarios para envío de contraseñas
$usuarios_password_query = "SELECT id, nombre, email, perfil FROM usuarios WHERE activo = 1 AND email IS NOT NULL AND email != '' ORDER BY nombre";
$usuarios_password = $conn->query($usuarios_password_query);
$usuarios_password2 = $conn->query($usuarios_password_query); // Segunda copia para form individual

// Obtener enlaces públicos existentes
$enlaces_publicos = $conn->query("SELECT t.*, u.nombre as creado_por_nombre 
    FROM resumen_publico_tokens t 
    JOIN usuarios u ON t.creado_por = u.id 
    ORDER BY t.fecha_creacion DESC LIMIT 10");

// Obtener historial reciente de envíos
$historial_query = "SELECT h.*, p.tipo, u.nombre as enviado_por_nombre 
    FROM historial_envios_correo h
    JOIN plantillas_correo p ON h.plantilla_id = p.id
    JOIN usuarios u ON h.enviado_por = u.id
    ORDER BY h.fecha_envio DESC
    LIMIT 20";
$historial = $conn->query($historial_query);

// Mes y año actuales
$mes_actual = (int)date('n');
$ano_actual = (int)date('Y');
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-envelope"></i> Gestión de Correos Automáticos</h2>
                    <p class="text-muted">Configure y envíe notificaciones automáticas a los usuarios</p>
                </div>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs de navegación -->
    <ul class="nav nav-tabs" id="correosTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="inicio-tab" data-bs-toggle="tab" data-bs-target="#inicio" type="button">
                <i class="bi bi-play-circle"></i> Inicio de Proceso
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fin-cargadores-tab" data-bs-toggle="tab" data-bs-target="#fin-cargadores" type="button">
                <i class="bi bi-clock-history"></i> Fin Proceso - Cargadores
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fin-general-tab" data-bs-toggle="tab" data-bs-target="#fin-general" type="button">
                <i class="bi bi-bar-chart"></i> Fin Proceso - General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="envio-password-tab" data-bs-toggle="tab" data-bs-target="#envio-password" type="button">
                <i class="bi bi-key-fill"></i> Envío de Contraseñas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button">
                <i class="bi bi-list-check"></i> Historial de Envíos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="correosTabContent">
        
        <!-- TAB 1: INICIO DE PROCESO -->
        <div class="tab-pane fade show active" id="inicio" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-play-circle"></i> Notificación de Inicio de Proceso</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Descripción:</strong> Este correo se envía a cada Cargador de Información al inicio del mes con los ítems que debe cargar.
                    </div>

                    <!-- Editor de Plantilla -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="tipo_plantilla" value="inicio_proceso">
                        
                        <div class="row mb-3">
                            <div class="col-md-10">
                                <label class="form-label">Asunto del correo:</label>
                                <input type="text" name="asunto" class="form-control" 
                                       value="<?= htmlspecialchars($plantillas['inicio_proceso']['asunto']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Envío automático:</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="envio_automatico" 
                                           <?= $plantillas['inicio_proceso']['envio_automatico'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Activar</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cuerpo del mensaje (HTML):</label>
                            <textarea name="cuerpo" class="form-control" rows="12" required><?= htmlspecialchars($plantillas['inicio_proceso']['cuerpo']) ?></textarea>
                            <small class="text-muted">
                                <strong>Variables disponibles:</strong> 
                                {nombre_usuario}, {mes_carga}, {ano_carga}, {mes_siguiente}, {items_asignados}, {plazo_dias}, {fecha_limite}
                            </small>
                        </div>

                        <button type="submit" name="guardar_plantilla" class="btn btn-success">
                            <i class="bi bi-save"></i> Guardar Plantilla
                        </button>
                    </form>

                    <hr>

                    <!-- Destinatarios: Directores (Opcional) -->
                    <h5><i class="bi bi-person-badge"></i> Directores (Opcional)</h5>
                    <div class="alert alert-info py-2 mb-3">
                        <small><i class="bi bi-info-circle"></i> <strong>Nota:</strong> Los directores recibirán la notificación de inicio de proceso con los <strong>ítems de SUS direcciones asignadas</strong>.</small>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Director</th>
                                    <th>Correo</th>
                                    <th>Direcciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hay_directores_inicio = false;
                                if ($directores_list && $directores_list->num_rows > 0):
                                    // Reset pointer
                                    $directores_list->data_seek(0);
                                    while ($dir = $directores_list->fetch_assoc()): 
                                        $hay_directores_inicio = true;
                                        // Obtener nombres de direcciones
                                        $dir_query = $conn->query("SELECT nombre FROM direcciones WHERE director_id = {$dir['id']} AND activa = 1 ORDER BY nombre");
                                        $dir_nombres = [];
                                        while ($d = $dir_query->fetch_assoc()) {
                                            $dir_nombres[] = $d['nombre'];
                                        }
                                        $texto_dir = !empty($dir_nombres) ? implode(', ', $dir_nombres) : 'Sin dirección';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($dir['nombres'] . ' ' . $dir['apellidos']) ?></td>
                                        <td><?= htmlspecialchars($dir['correo']) ?></td>
                                        <td><small><?= htmlspecialchars($texto_dir) ?></small></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                endif;
                                if (!$hay_directores_inicio): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No hay directores con correo registrado. 
                                            <a href="<?= SITE_URL ?>admin/directores/">Agregar directores</a>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <!-- Formulario de Envío -->
                    <h5><i class="bi bi-send"></i> Enviar Notificaciones</h5>
                    
                    <div class="row">
                        <!-- Envío Masivo -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Envío Masivo</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Período:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <select name="mes_periodo" class="form-select" required>
                                                        <?php foreach ($meses as $num => $nombre): ?>
                                                            <option value="<?= $num ?>" <?= $num === $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="ano_periodo" class="form-control" 
                                                           value="<?= $ano_actual ?>" min="2020" max="2099" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Checkbox para enviar a directores -->
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="enviar_a_directores_inicio" value="1" id="checkDirectoresInicio" <?= !$hay_directores_inicio ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="checkDirectoresInicio">
                                                    <strong>Enviar también a Directores</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted ms-4">
                                                <?php if ($hay_directores_inicio): ?>
                                                    Los directores recibirán los <strong>ítems de sus direcciones asignadas</strong>.
                                                <?php else: ?>
                                                    <em>No hay directores registrados con correo.</em>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <button type="submit" name="enviar_masivo_inicio" class="btn btn-success w-100">
                                            <i class="bi bi-send-fill"></i> Enviar Notificación de Inicio
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Envío Individual a Usuario -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0">Envío Individual a Usuario</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Usuario:</label>
                                            <select name="usuario_id" class="form-select" required>
                                                <option value="">Seleccione un cargador...</option>
                                                <?php while ($cargador = $cargadores->fetch_assoc()): ?>
                                                    <option value="<?= $cargador['id'] ?>">
                                                        <?= htmlspecialchars($cargador['nombre']) ?>
                                                        (<?= htmlspecialchars($cargador['email']) ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Período:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <select name="mes_periodo" class="form-select" required>
                                                        <?php foreach ($meses as $num => $nombre): ?>
                                                            <option value="<?= $num ?>" <?= $num === $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="ano_periodo" class="form-control" 
                                                           value="<?= $ano_actual ?>" min="2020" max="2099" required>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" name="enviar_individual_inicio" class="btn btn-warning w-100">
                                            <i class="bi bi-send"></i> Enviar a Usuario Específico
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Envío Individual a Director -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Envío Individual a Director</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Director:</label>
                                            <select name="director_id" class="form-select" required>
                                                <option value="">Seleccione un director...</option>
                                                <?php 
                                                // Reset pointer para el segundo uso de directores_list
                                                if ($directores_list && $directores_list->num_rows > 0) {
                                                    $directores_list->data_seek(0);
                                                }
                                                while ($director = $directores_list->fetch_assoc()): ?>
                                                    <option value="<?= $director['id'] ?>">
                                                        <?= htmlspecialchars($director['nombres'] . ' ' . $director['apellidos']) ?>
                                                        (<?= htmlspecialchars($director['correo']) ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Período:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <select name="mes_periodo" class="form-select" required>
                                                        <?php foreach ($meses as $num => $nombre): ?>
                                                            <option value="<?= $num ?>" <?= $num === $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="ano_periodo" class="form-control" 
                                                           value="<?= $ano_actual ?>" min="2020" max="2099" required>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" name="enviar_individual_inicio_director" class="btn btn-info w-100">
                                            <i class="bi bi-send"></i> Enviar a Director Específico
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: FIN PROCESO CARGADORES -->
        <div class="tab-pane fade" id="fin-cargadores" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Notificación de Fin de Proceso - Cargadores</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Descripción:</strong> Este correo se envía cuando vence el plazo de carga con el resumen del estado de cada cargador (documentos cargados, pendientes, fechas).
                    </div>

                    <!-- Editor de Plantilla -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="tipo_plantilla" value="fin_proceso_cargadores">
                        
                        <div class="row mb-3">
                            <div class="col-md-10">
                                <label class="form-label">Asunto del correo:</label>
                                <input type="text" name="asunto" class="form-control" 
                                       value="<?= htmlspecialchars($plantillas['fin_proceso_cargadores']['asunto']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Envío automático:</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="envio_automatico" 
                                           <?= $plantillas['fin_proceso_cargadores']['envio_automatico'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Activar</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cuerpo del mensaje (HTML):</label>
                            <textarea name="cuerpo" class="form-control" rows="12" required><?= htmlspecialchars($plantillas['fin_proceso_cargadores']['cuerpo']) ?></textarea>
                            <small class="text-muted">
                                <strong>Variables disponibles:</strong> 
                                {nombre_usuario}, {mes_carga}, {ano_carga}, {resumen_carga}, {fecha_limite}
                            </small>
                        </div>

                        <button type="submit" name="guardar_plantilla" class="btn btn-success">
                            <i class="bi bi-save"></i> Guardar Plantilla
                        </button>
                    </form>

                    <hr>

                    <!-- Formulario de Envío -->
                    <h5><i class="bi bi-send"></i> Enviar Notificaciones</h5>
                    
                    <div class="row">
                        <!-- Envío Masivo -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Envío Masivo</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Período:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <select name="mes_periodo" class="form-select" required>
                                                        <?php foreach ($meses as $num => $nombre): ?>
                                                            <option value="<?= $num ?>" <?= $num === $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="ano_periodo" class="form-control" 
                                                           value="<?= $ano_actual ?>" min="2020" max="2099" required>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" name="enviar_masivo_fin_cargadores" class="btn btn-success w-100">
                                            <i class="bi bi-send-fill"></i> Enviar a Todos los Cargadores
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Envío Individual -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0">Envío Individual</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Usuario:</label>
                                            <select name="usuario_id" class="form-select" required>
                                                <option value="">Seleccione un cargador...</option>
                                                <?php while ($cargador = $cargadores2->fetch_assoc()): ?>
                                                    <option value="<?= $cargador['id'] ?>">
                                                        <?= htmlspecialchars($cargador['nombre']) ?>
                                                        (<?= htmlspecialchars($cargador['email']) ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Período:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <select name="mes_periodo" class="form-select" required>
                                                        <?php foreach ($meses as $num => $nombre): ?>
                                                            <option value="<?= $num ?>" <?= $num === $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="ano_periodo" class="form-control" 
                                                           value="<?= $ano_actual ?>" min="2020" max="2099" required>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" name="enviar_individual_fin_cargadores" class="btn btn-warning w-100">
                                            <i class="bi bi-send"></i> Enviar a Usuario Específico
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: FIN PROCESO GENERAL -->
        <div class="tab-pane fade" id="fin-general" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Cierre del Proceso - Directores</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Descripción:</strong> Este correo se envía a todos los Directores registrados en el sistema al cierre del proceso (10° día hábil). 
                        Incluye un resumen general de todos los ítems del municipio y un enlace público para consultar el estado completo sin necesidad de iniciar sesión.
                    </div>

                    <!-- Editor de Plantilla -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="tipo_plantilla" value="fin_proceso_general">
                        
                        <div class="row mb-3">
                            <div class="col-md-10">
                                <label class="form-label">Asunto del correo:</label>
                                <input type="text" name="asunto" class="form-control" 
                                       value="<?= htmlspecialchars($plantillas['fin_proceso_general']['asunto'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Envío automático:</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="envio_automatico" 
                                           <?= !empty($plantillas['fin_proceso_general']['envio_automatico']) ? 'checked' : '' ?>>
                                    <label class="form-check-label">Activar</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cuerpo del mensaje (HTML):</label>
                            <textarea name="cuerpo" class="form-control" rows="12" required><?= htmlspecialchars($plantillas['fin_proceso_general']['cuerpo'] ?? '') ?></textarea>
                            <small class="text-muted">
                                <strong>Variables disponibles:</strong> 
                                {mes_carga}, {ano_carga}, {fecha_cierre}, {nombre_director}, {direcciones_director}, {resumen_general}, {enlace_resumen}
                            </small>
                        </div>

                        <button type="submit" name="guardar_plantilla" class="btn btn-success">
                            <i class="bi bi-save"></i> Guardar Plantilla
                        </button>
                    </form>

                    <hr>

                    <!-- Destinatarios: Directores -->
                    <h5><i class="bi bi-person-badge"></i> Destinatarios (Directores)</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Director</th>
                                    <th>Correo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hay_directores = false;
                                if ($directores_list && $directores_list->num_rows > 0):
                                    while ($dir = $directores_list->fetch_assoc()): 
                                        $hay_directores = true;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($dir['nombres'] . ' ' . $dir['apellidos']) ?></td>
                                        <td><?= htmlspecialchars($dir['correo']) ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                endif;
                                if (!$hay_directores): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">
                                            No hay directores con correo registrado. 
                                            <a href="<?= SITE_URL ?>admin/directores/">Agregar directores</a>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Destinatarios: Auditores (Opcional) -->
                    <h5><i class="bi bi-shield-check"></i> Auditores (Opcional)</h5>
                    <div class="alert alert-info py-2 mb-3">
                        <small><i class="bi bi-info-circle"></i> <strong>Nota:</strong> Los auditores recibirán el resumen <strong>COMPLETO de TODAS las direcciones</strong> del municipio para poder realizar auditoría integral.</small>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Auditor</th>
                                    <th>Correo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hay_auditores = false;
                                if ($auditores_list && $auditores_list->num_rows > 0):
                                    while ($aud = $auditores_list->fetch_assoc()): 
                                        $hay_auditores = true;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($aud['nombre']) ?></td>
                                        <td><?= htmlspecialchars($aud['email']) ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                endif;
                                if (!$hay_auditores): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">
                                            No hay auditores con correo registrado. 
                                            <a href="<?= SITE_URL ?>admin/usuarios/">Agregar auditores</a>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <!-- Formulario de Envío -->
                    <h5><i class="bi bi-send"></i> Enviar Cierre del Proceso</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Envío Masivo - Cierre del Proceso</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" onsubmit="return confirm('¿Enviar correo de cierre del proceso? Se enviará a directores y opcionalmente a auditores según selección.');">
                                        <div class="mb-3">
                                            <label class="form-label">Período:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <select name="mes_periodo" class="form-select" required>
                                                        <?php foreach ($meses as $num => $nombre): ?>
                                                            <option value="<?= $num ?>" <?= $num === $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="ano_periodo" class="form-control" 
                                                           value="<?= $ano_actual ?>" min="2020" max="2099" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Checkbox para enviar a auditores -->
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="enviar_a_auditores" value="1" id="checkAuditores" <?= !$hay_auditores ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="checkAuditores">
                                                    <strong>Enviar también a Auditores</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted ms-4">
                                                <?php if ($hay_auditores): ?>
                                                    Los auditores recibirán el <strong>resumen completo de todas las direcciones</strong>.
                                                <?php else: ?>
                                                    <em>No hay auditores registrados con correo.</em>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="alert alert-warning py-2">
                                            <small><i class="bi bi-exclamation-triangle"></i> Se generará un enlace público con el resumen municipal completo.</small>
                                        </div>
                                        <button type="submit" name="enviar_masivo_fin_general" class="btn btn-info text-white w-100" <?= !$hay_directores ? 'disabled' : '' ?>>
                                            <i class="bi bi-send-fill"></i> Enviar Cierre del Proceso
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Enlaces públicos generados -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Enlaces Públicos Generados</h6>
                                </div>
                                <div class="card-body" style="max-height:300px; overflow-y:auto;">
                                    <?php if ($enlaces_publicos && $enlaces_publicos->num_rows > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php while ($enlace = $enlaces_publicos->fetch_assoc()): ?>
                                                <div class="list-group-item px-0">
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?= $meses[$enlace['mes']] ?> <?= $enlace['ano'] ?></strong>
                                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($enlace['fecha_creacion'])) ?></small>
                                                    </div>
                                                    <div class="input-group input-group-sm mt-1">
                                                        <input type="text" class="form-control form-control-sm" 
                                                               value="<?= htmlspecialchars(SITE_URL . 'resumen_publico.php?token=' . $enlace['token']) ?>" 
                                                               readonly id="link-<?= $enlace['id'] ?>">
                                                        <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                                onclick="navigator.clipboard.writeText(document.getElementById('link-<?= $enlace['id'] ?>').value); this.innerHTML='<i class=\'bi bi-check\'></i> Copiado';">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0">No hay enlaces generados aún.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: ENVÍO DE CONTRASEÑAS -->
        <div class="tab-pane fade" id="envio-password" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-key-fill"></i> Envío de Contraseñas</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Descripción:</strong> Envíe las contraseñas actuales a los usuarios del sistema.
                    </div>

                    <!-- Editor de Plantilla -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="tipo_plantilla" value="envio_password">
                        
                        <div class="row mb-3">
                            <div class="col-md-10">
                                <label class="form-label">Asunto del correo:</label>
                                <input type="text" name="asunto" class="form-control" 
                                       value="<?= htmlspecialchars($plantillas['envio_password']['asunto'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Envío automático:</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="envio_automatico" 
                                           <?= isset($plantillas['envio_password']) && $plantillas['envio_password']['envio_automatico'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Activar</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cuerpo del mensaje (HTML):</label>
                            <textarea name="cuerpo" class="form-control" rows="12" required><?= htmlspecialchars($plantillas['envio_password']['cuerpo'] ?? '') ?></textarea>
                            <small class="text-muted">
                                <strong>Variables disponibles:</strong> 
                                {nombre_usuario}, {email_usuario}, {password}, {url_sistema}
                            </small>
                        </div>

                        <button type="submit" name="guardar_plantilla" class="btn btn-success">
                            <i class="bi bi-save"></i> Guardar Plantilla
                        </button>
                    </form>

                    <hr>

                    <!-- Formulario de Envío -->
                    <h5><i class="bi bi-send"></i> Enviar Contraseñas</h5>
                    
                    <div class="row">
                        <!-- Envío Individual -->
                        <div class="col-md-8 mx-auto">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0">Envío Individual</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Usuario:</label>
                                            <select name="usuario_id" class="form-select" required>
                                                <option value="">Seleccione un usuario...</option>
                                                <?php while ($usuario = $usuarios_password->fetch_assoc()): ?>
                                                    <option value="<?= $usuario['id'] ?>">
                                                        <?= htmlspecialchars($usuario['nombre']) ?> 
                                                        (<?= htmlspecialchars($usuario['email']) ?>) 
                                                        - <?= htmlspecialchars(ucfirst($usuario['perfil'])) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Nueva Contraseña:</label>
                                            <input type="password" name="nueva_password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                                            <small class="text-muted">La contraseña será actualizada en el sistema y enviada por correo al usuario.</small>
                                        </div>
                                        
                                        <button type="submit" name="enviar_password_individual" class="btn btn-warning w-100">
                                            <i class="bi bi-send"></i> Actualizar Contraseña y Enviar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Usuarios -->
                    <div class="mt-4">
                        <h6>Usuarios Registrados:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Perfil</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($usuarios_password2 && $usuarios_password2->num_rows > 0) {
                                        while ($usuario = $usuarios_password2->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($usuario['nombre']) . '</td>';
                                            echo '<td>' . htmlspecialchars($usuario['email']) . '</td>';
                                            echo '<td><span class="badge bg-info">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $usuario['perfil']))) . '</span></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center text-muted">No hay usuarios registrados</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: HISTORIAL -->
        <div class="tab-pane fade" id="historial" role="tabpanel">
            <div class="card mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Historia de Envíos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Envío</th>
                                    <th>Período</th>
                                    <th>Enviados</th>
                                    <th>Fallidos</th>
                                    <th>Enviado por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($historial->num_rows > 0): ?>
                                    <?php while ($h = $historial->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($h['fecha_envio'])) ?></td>
                                            <td>
                                                <?php
                                                $tipos = [
                                                    'inicio_proceso' => '<span class="badge bg-primary">Inicio</span>',
                                                    'fin_proceso_cargadores' => '<span class="badge bg-warning">Fin Cargadores</span>',
                                                    'fin_proceso_general' => '<span class="badge bg-info">Cierre General</span>'
                                                ];
                                                echo $tipos[$h['tipo']] ?? $h['tipo'];
                                                ?>
                                            </td>
                                            <td>
                                                <?= $h['tipo_envio'] === 'masivo' ? 
                                                    '<span class="badge bg-success">Masivo</span>' : 
                                                    '<span class="badge bg-secondary">Individual</span>' ?>
                                            </td>
                                            <td><?= $h['mes_periodo'] ? $meses[$h['mes_periodo']] . ' ' . $h['ano_periodo'] : '-' ?></td>
                                            <td><?= $h['correos_enviados'] ?></td>
                                            <td><?= $h['correos_fallidos'] > 0 ? '<span class="text-danger">' . $h['correos_fallidos'] . '</span>' : '0' ?></td>
                                            <td><?= htmlspecialchars($h['enviado_por_nombre']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay envíos registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /tab-content -->
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
