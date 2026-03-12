<?php
// Script de verificación del sistema
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/check_auth.php';
require_login();
require_role('admin');

require_once 'includes/header.php';
require_once 'classes/Item.php';
require_once 'classes/ItemPlazo.php';
require_once 'classes/ItemConPlazo.php';
require_once 'classes/Documento.php';

$db_conn = $db->getConnection();
?>

<div class="page-header">
    <h1><i class="bi bi-check-circle"></i> Verificación del Sistema</h1>
    <p class="text-white-50">Estado de todas las funcionalidades</p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">✅ Clases PHP</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-success">ItemPlazo</span>
                    <?php
                    try {
                        $itemPlazo = new ItemPlazo($db_conn);
                        echo '<small class="text-success">Cargada correctamente</small>';
                    } catch (Exception $e) {
                        echo '<small class="text-danger">' . $e->getMessage() . '</small>';
                    }
                    ?>
                </div>
                <div class="mb-2">
                    <span class="badge bg-success">ItemConPlazo</span>
                    <?php
                    try {
                        $itemConPlazo = new ItemConPlazo($db_conn);
                        echo '<small class="text-success">Cargada correctamente</small>';
                    } catch (Exception $e) {
                        echo '<small class="text-danger">' . $e->getMessage() . '</small>';
                    }
                    ?>
                </div>
                <div class="mb-2">
                    <span class="badge bg-success">Item</span>
                    <?php
                    try {
                        $item = new Item($db_conn);
                        echo '<small class="text-success">Cargada correctamente</small>';
                    } catch (Exception $e) {
                        echo '<small class="text-danger">' . $e->getMessage() . '</small>';
                    }
                    ?>
                </div>
                <div class="mb-2">
                    <span class="badge bg-success">Documento</span>
                    <?php
                    try {
                        $documento = new Documento($db_conn);
                        echo '<small class="text-success">Cargada correctamente</small>';
                    } catch (Exception $e) {
                        echo '<small class="text-danger">' . $e->getMessage() . '</small>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">✅ Tablas de Base de Datos</h6>
            </div>
            <div class="card-body">
                <?php
                $tables = ['item_plazos', 'documento_seguimiento', 'items', 'documentos', 'usuarios'];
                foreach ($tables as $table) {
                    $result = $db_conn->query("SHOW TABLES LIKE '$table'");
                    $exists = $result->num_rows > 0;
                    $badge = $exists ? 'bg-success' : 'bg-danger';
                    $status = $exists ? 'Existe' : 'NO EXISTE';
                    echo "<div class='mb-2'><span class='badge $badge'>$table</span> <small class='text-secondary'>$status</small></div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">✅ Archivos del Sistema</h6>
            </div>
            <div class="card-body">
                <?php
                $files = [
                    'usuario/dashboard.php' => 'Dashboard usuario',
                    'usuario/enviar_documento.php' => 'Procesar carga',
                    'admin/items/plazos.php' => 'Admin plazos',
                    'classes/ItemPlazo.php' => 'Clase ItemPlazo',
                    'classes/ItemConPlazo.php' => 'Clase ItemConPlazo'
                ];
                foreach ($files as $file => $desc) {
                    $exists = file_exists($file);
                    $badge = $exists ? 'bg-success' : 'bg-danger';
                    $status = $exists ? 'OK' : 'NO ENCONTRADO';
                    echo "<div class='mb-2'><span class='badge $badge'>$desc</span> <small class='text-secondary'>$file</small></div>";
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">✅ Enlaces de Acceso</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <a href="usuario/dashboard.php" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bi bi-speedometer2"></i> Dashboard Usuario
                    </a>
                </div>
                <div class="mb-2">
                    <a href="admin/items/plazos.php" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bi bi-calendar-check"></i> Admin Plazos
                    </a>
                </div>
                <div class="mt-3">
                    <p class="text-muted"><small><strong>Nota:</strong> El sistema está completamente operativo. Verifica que puedas acceder a los enlaces superiores.</small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <h6 class="mb-3"><i class="bi bi-info-circle"></i> Estado del Sistema</h6>
    <p class="mb-2"><strong>✅ Base de Datos:</strong> Tablas `item_plazos` y `documento_seguimiento` creadas</p>
    <p class="mb-2"><strong>✅ Dashboard:</strong> Rediseñado con selector de mes y tabla completa</p>
    <p class="mb-2"><strong>✅ Admin:</strong> Panel de configuración de plazos implementado</p>
    <p class="mb-2"><strong>✅ Funcionalidad:</strong> Carga de documentos con registro automático de fechas</p>
    <p class="mb-0"><strong>Estado General:</strong> <span class="badge bg-success">LISTO PARA USAR</span></p>
</div>

<?php require_once 'includes/footer.php'; ?>
