<?php
/**
 * Gestión de Categorías
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/categorias.php';

requireRole('admin');

$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $nombre = sanitize($_POST['nombre'] ?? '');
            $prefijo = sanitize($_POST['prefijo'] ?? '');
            $descripcion = sanitize($_POST['descripcion'] ?? '');
            $color = sanitize($_POST['color'] ?? '#007bff');
            $tipoNumeracion = sanitize($_POST['tipo_numeracion'] ?? 'automatica');
            
            if (empty($nombre)) {
                $error = 'El nombre es obligatorio';
            } else {
                try {
                    crearCategoria($nombre, $prefijo, $descripcion, $color, $tipoNumeracion);
                    $mensaje = 'Categoría creada exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al crear categoría: ' . $e->getMessage();
                }
            }
            break;
            
        case 'editar':
            $id = (int)$_POST['id'];
            $nombre = sanitize($_POST['nombre'] ?? '');
            $prefijo = sanitize($_POST['prefijo'] ?? '');
            $descripcion = sanitize($_POST['descripcion'] ?? '');
            $color = sanitize($_POST['color'] ?? '#007bff');
            $tipoNumeracion = sanitize($_POST['tipo_numeracion'] ?? 'automatica');
            $activa = isset($_POST['activa']) ? 1 : 0;
            
            actualizarCategoria($id, $nombre, $prefijo, $descripcion, $color, $tipoNumeracion, $activa);
            $mensaje = 'Categoría actualizada exitosamente';
            break;
            
        case 'eliminar':
            $id = (int)$_POST['id'];
            $result = eliminarCategoria($id);
            if ($result['success']) {
                $mensaje = 'Categoría eliminada exitosamente';
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'resetear':
            $id = (int)$_POST['id'];
            resetearNumeracionCategoria($id);
            $mensaje = 'Numeración reseteada exitosamente';
            break;
            
        case 'resetear_todas':
            resetearTodasNumeraciones();
            $mensaje = 'Todas las numeraciones han sido reseteadas';
            break;
    }
}

$categorias = getCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - Sistema de Numeración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-tags me-2"></i>Gestión de Categorías</h2>
                    <p class="text-muted mb-0">Administre las categorías de atención</p>
                </div>
                <div>
                    <button class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#modalResetearTodas">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Resetear Todas
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                        <i class="bi bi-plus-lg me-1"></i>Nueva Categoría
                    </button>
                </div>
            </div>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Prefijo</th>
                                <th>Nombre</th>
                                <th>Numeración</th>
                                <th class="text-center">Nº Actual</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td>
                                    <span class="color-preview" style="background-color: <?= $cat['color'] ?>"></span>
                                </td>
                                <td><?= !empty($cat['prefijo']) ? '<span class="badge bg-secondary">' . $cat['prefijo'] . '</span>' : '<span class="text-muted">-</span>' ?></td>
                                <td><?= htmlspecialchars($cat['nombre']) ?></td>
                                <td>
                                    <?php if (($cat['tipo_numeracion'] ?? 'automatica') === 'manual'): ?>
                                        <span class="badge bg-info"><i class="bi bi-hand-index me-1"></i>Manual</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="bi bi-gear me-1"></i>Automática</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6"><?= $cat['numero_actual'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($cat['activa']): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editarCategoria(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" 
                                            onclick="resetearCategoria(<?= $cat['id'] ?>, '<?= $cat['nombre'] ?>')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="eliminarCategoria(<?= $cat['id'] ?>, '<?= $cat['nombre'] ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nueva/Editar Categoría -->
    <div class="modal fade" id="modalCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formCategoria">
                    <input type="hidden" name="accion" id="accionCategoria" value="crear">
                    <input type="hidden" name="id" id="categoriaId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCategoriaTitle">Nueva Categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="prefijo" class="form-label">Prefijo</label>
                            <input type="text" class="form-control" id="prefijo" name="prefijo" 
                                   maxlength="10" placeholder="Ej: PC, AS, SA (opcional)">
                            <small class="text-muted">Máximo 10 caracteres, se convertirá a mayúsculas</small>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color" id="color" 
                                   name="color" value="#007bff">
                        </div>
                        <div class="mb-3">
                            <label for="tipo_numeracion" class="form-label">Tipo de Numeración</label>
                            <select class="form-select" id="tipo_numeracion" name="tipo_numeracion">
                                <option value="automatica">Automática (sistema genera número)</option>
                                <option value="manual">Manual (ticket físico/impresora externa)</option>
                            </select>
                            <small class="text-muted">Manual: el funcionario ingresa el número del ticket físico</small>
                        </div>
                        <div class="mb-3" id="divActiva" style="display: none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activa" name="activa" checked>
                                <label class="form-check-label" for="activa">Categoría Activa</label>
                            </div>
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
    
    <!-- Modal Confirmar Resetear Todas -->
    <div class="modal fade" id="modalResetearTodas" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="resetear_todas">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar Reset General</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">¿Está seguro de resetear TODAS las numeraciones?</p>
                        <p class="text-danger mb-0 mt-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Esta acción cancelará todos los turnos en espera y reiniciará los contadores.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sí, Resetear Todo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="eliminarId">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de eliminar la categoría "<span id="eliminarNombre"></span>"?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Resetear -->
    <div class="modal fade" id="modalResetear" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="resetear">
                    <input type="hidden" name="id" id="resetearId">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Confirmar Reset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de resetear la numeración de "<span id="resetearNombre"></span>"?</p>
                        <p class="text-muted mb-0">El contador volverá a 0.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Resetear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalCategoria = new bootstrap.Modal(document.getElementById('modalCategoria'));
        const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));
        const modalResetear = new bootstrap.Modal(document.getElementById('modalResetear'));
        
        // Limpiar modal al cerrar
        document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formCategoria').reset();
            document.getElementById('accionCategoria').value = 'crear';
            document.getElementById('categoriaId').value = '';
            document.getElementById('modalCategoriaTitle').textContent = 'Nueva Categoría';
            document.getElementById('divActiva').style.display = 'none';
        });
        
        function editarCategoria(cat) {
            document.getElementById('accionCategoria').value = 'editar';
            document.getElementById('categoriaId').value = cat.id;
            document.getElementById('nombre').value = cat.nombre;
            document.getElementById('prefijo').value = cat.prefijo;
            document.getElementById('descripcion').value = cat.descripcion || '';
            document.getElementById('color').value = cat.color;
            document.getElementById('tipo_numeracion').value = cat.tipo_numeracion || 'automatica';
            document.getElementById('activa').checked = cat.activa == 1;
            document.getElementById('divActiva').style.display = 'block';
            document.getElementById('modalCategoriaTitle').textContent = 'Editar Categoría';
            modalCategoria.show();
        }
        
        function eliminarCategoria(id, nombre) {
            document.getElementById('eliminarId').value = id;
            document.getElementById('eliminarNombre').textContent = nombre;
            modalEliminar.show();
        }
        
        function resetearCategoria(id, nombre) {
            document.getElementById('resetearId').value = id;
            document.getElementById('resetearNombre').textContent = nombre;
            modalResetear.show();
        }
    </script>
</body>
</html>
