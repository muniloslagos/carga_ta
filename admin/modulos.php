<?php
/**
 * Gestión de Módulos
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/modulos.php';
require_once dirname(__DIR__) . '/includes/categorias.php';
require_once dirname(__DIR__) . '/includes/usuarios.php';

requireRole('admin');

$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'editar':
            $id = (int)$_POST['id'];
            $nombreFuncionario = sanitize($_POST['nombre_funcionario'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;
            $usuarioId = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
            
            actualizarModulo($id, $nombreFuncionario, $activo, $usuarioId);
            
            // Actualizar categorías del módulo
            $categoriasIds = $_POST['categorias'] ?? [];
            asignarCategoriasModulo($id, $categoriasIds);
            
            $mensaje = 'Módulo actualizado exitosamente';
            break;
    }
}

$modulos = getModulos();
$categorias = getCategorias(true);
$usuarios = getUsuarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulos - Sistema de Numeración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="bi bi-pc-display me-2"></i>Gestión de Módulos</h2>
                <p class="text-muted mb-0">Configure los módulos de atención (máximo <?= MAX_MODULOS ?> módulos)</p>
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
        
        <div class="row">
            <?php foreach ($modulos as $mod): 
                $categoriasModulo = getCategoriasModulo($mod['id']);
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?= $mod['activo'] ? '' : 'border-secondary opacity-75' ?>">
                    <div class="card-header d-flex justify-content-between align-items-center 
                                <?= $mod['activo'] ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                        <h5 class="mb-0">
                            <i class="bi bi-pc-display me-2"></i>Módulo <?= $mod['numero'] ?>
                        </h5>
                        <span class="badge <?= $mod['activo'] ? 'bg-light text-primary' : 'bg-dark' ?>">
                            <?= $mod['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Funcionario Asignado</label>
                            <p class="mb-0 fw-bold">
                                <?= htmlspecialchars($mod['nombre_funcionario'] ?? 'Sin asignar') ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Estado Actual</label>
                            <p class="mb-0">
                                <?php 
                                $estados = [
                                    'disponible' => ['bg-success', 'Disponible'],
                                    'ocupado' => ['bg-warning', 'Ocupado'],
                                    'pausado' => ['bg-info', 'Pausado'],
                                    'inactivo' => ['bg-secondary', 'Inactivo']
                                ];
                                $estado = $estados[$mod['estado']] ?? ['bg-secondary', 'Desconocido'];
                                ?>
                                <span class="badge <?= $estado[0] ?>"><?= $estado[1] ?></span>
                            </p>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted small">Categorías que Atiende</label>
                            <div>
                                <?php if (empty($categoriasModulo)): ?>
                                    <span class="text-muted">Ninguna asignada</span>
                                <?php else: ?>
                                    <?php foreach ($categoriasModulo as $cat): ?>
                                        <span class="badge me-1" style="background-color: <?= $cat['color'] ?>">
                                            <?= $cat['prefijo'] ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-outline-primary w-100" 
                                onclick="editarModulo(<?= $mod['id'] ?>, <?= htmlspecialchars(json_encode($mod)) ?>, <?= htmlspecialchars(json_encode(array_column($categoriasModulo, 'id'))) ?>)">
                            <i class="bi bi-pencil me-1"></i>Configurar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modal Editar Módulo -->
    <div class="modal fade" id="modalModulo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="moduloId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalModuloTitle">Configurar Módulo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre_funcionario" class="form-label">Nombre del Funcionario</label>
                            <input type="text" class="form-control" id="nombre_funcionario" 
                                   name="nombre_funcionario" placeholder="Ej: Carla Orellana">
                            <small class="text-muted">Este nombre se mostrará en pantalla al llamar turnos</small>
                        </div>
                        <div class="mb-3">
                            <label for="usuario_id" class="form-label">Usuario del Sistema (opcional)</label>
                            <select class="form-select" id="usuario_id" name="usuario_id">
                                <option value="">Sin usuario asignado</option>
                                <?php foreach ($usuarios as $usr): ?>
                                    <option value="<?= $usr['id'] ?>"><?= htmlspecialchars($usr['nombre_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categorías que Atiende</label>
                            <div class="border rounded p-2">
                                <?php foreach ($categorias as $cat): ?>
                                <div class="form-check">
                                    <input class="form-check-input categoria-check" type="checkbox" 
                                           name="categorias[]" value="<?= $cat['id'] ?>" 
                                           id="cat_<?= $cat['id'] ?>">
                                    <label class="form-check-label" for="cat_<?= $cat['id'] ?>">
                                        <span class="badge me-1" style="background-color: <?= $cat['color'] ?>">
                                            <?= $cat['prefijo'] ?>
                                        </span>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo">
                                <label class="form-check-label" for="activo">Módulo Activo</label>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalModulo = new bootstrap.Modal(document.getElementById('modalModulo'));
        
        function editarModulo(id, modulo, categoriasIds) {
            document.getElementById('moduloId').value = id;
            document.getElementById('nombre_funcionario').value = modulo.nombre_funcionario || '';
            document.getElementById('usuario_id').value = modulo.usuario_id || '';
            document.getElementById('activo').checked = modulo.activo == 1;
            document.getElementById('modalModuloTitle').textContent = 'Configurar Módulo ' + modulo.numero;
            
            // Limpiar checkboxes
            document.querySelectorAll('.categoria-check').forEach(cb => cb.checked = false);
            
            // Marcar categorías asignadas
            categoriasIds.forEach(catId => {
                const cb = document.getElementById('cat_' + catId);
                if (cb) cb.checked = true;
            });
            
            modalModulo.show();
        }
    </script>
</body>
</html>
