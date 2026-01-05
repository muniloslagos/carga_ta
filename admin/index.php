<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../includes/check_auth.php';
require_role('administrativo');

// LUEGO: Incluir header con HTML
require_once '../includes/header.php';

require_once '../classes/Direccion.php';
require_once '../classes/Usuario.php';

$direccionClass = new Direccion($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());

?>

<div class="page-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 2.5rem 2rem; margin: -1rem -1rem 2rem -1rem; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h1 style="font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem;">
        <i class="bi bi-sliders2" style="color: #3498db; margin-right: 0.5rem;"></i> 
        Panel de Administración
    </h1>
    <p style="margin: 0; color: #bdc3c7; font-size: 1.05rem;">
        <i class="bi bi-person-check" style="color: #3498db;"></i> 
        Bienvenido, <strong><?php echo htmlspecialchars($current_user['nombre'] ?? 'Administrador'); ?></strong>
    </p>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
            <div class="card-body text-center">
                <i class="bi bi-people" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                <h5 class="card-title mt-3 mb-2">Usuarios</h5>
                <?php
                $result = $usuarioClass->getAll();
                $count = $result->num_rows;
                ?>
                <p class="h2 mb-3" style="font-weight: 700;"><?php echo $count; ?></p>
                <a href="usuarios/index.php" class="btn btn-sm btn-light" style="font-weight: 600;">Gestionar</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
            <div class="card-body text-center">
                <i class="bi bi-building" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                <h5 class="card-title mt-3 mb-2">Direcciones</h5>
                <?php
                $result = $direccionClass->getAll();
                $count = $result->num_rows;
                ?>
                <p class="h2 mb-3" style="font-weight: 700;"><?php echo $count; ?></p>
                <a href="direcciones/index.php" class="btn btn-sm btn-light" style="font-weight: 600;">Gestionar</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0" style="background: linear-gradient(135deg, #f39c12 0%, #d68910 100%); color: white; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
            <div class="card-body text-center">
                <i class="bi bi-file-text" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                <h5 class="card-title mt-3 mb-2">Items</h5>
                <?php
                $conn = $db->getConnection();
                $result = $conn->query("SELECT COUNT(*) as count FROM items_transparencia WHERE activo = 1");
                $row = $result->fetch_assoc();
                ?>
                <p class="h2 mb-3" style="font-weight: 700;"><?php echo $row['count']; ?></p>
                <a href="items/index.php" class="btn btn-sm btn-light" style="font-weight: 600;">Gestionar</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
            <div class="card-body text-center">
                <i class="bi bi-file-pdf" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                <h5 class="card-title mt-3 mb-2">Documentos</h5>
                <?php
                $result = $conn->query("SELECT COUNT(*) as count FROM documentos");
                $row = $result->fetch_assoc();
                ?>
                <p class="h2 mb-3" style="font-weight: 700;"><?php echo $row['count']; ?></p>
                <a href="documentos/index.php" class="btn btn-sm btn-light" style="font-weight: 600;">Ver</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Actividad Reciente
            </div>
            <div class="card-body">
                <?php
                $result = $conn->query("SELECT l.*, u.nombre FROM logs l
                                      LEFT JOIN usuarios u ON l.usuario_id = u.id
                                      ORDER BY l.fecha DESC LIMIT 10");
                ?>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>IP</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nombre'] ?? 'Sistema'); ?></td>
                                <td><?php echo htmlspecialchars($row['accion']); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($row['ip_address']); ?></small></td>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></small></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Información del Sistema
            </div>
            <div class="card-body">
                <p><strong>Versión:</strong> 1.0.0</p>
                <p><strong>Base de Datos:</strong> <?php echo DB_NAME; ?></p>
                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                <hr>
                <div class="d-grid gap-2">
                    <a href="usuarios/index.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people"></i> Gestionar Usuarios
                    </a>
                    <a href="direcciones/index.php" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-building"></i> Gestionar Direcciones
                    </a>
                    <a href="items/index.php" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-file-text"></i> Gestionar Items
                    </a>
                    <a href="items/plazos.php" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-calendar-check"></i> Gestionar Plazos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
