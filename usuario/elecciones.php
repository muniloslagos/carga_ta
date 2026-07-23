<?php
require_once '../includes/check_auth.php';
require_login();

$perfil = $current_profile ?? ($current_user['perfil'] ?? '');
if ($perfil === 'auditor') {
    header('Location: ' . SITE_URL . 'usuario/dashboard_auditor.php');
    exit;
}

function ensure_elections_year_directory($year)
{
    $dir = dirname(__DIR__) . '/uploads/elecciones/' . $year;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $adjuntosDir = $dir . '/archivos';
    if (!is_dir($adjuntosDir)) {
        mkdir($adjuntosDir, 0777, true);
    }

    return $dir;
}

function get_elections_csv_path($year)
{
    ensure_elections_year_directory($year);
    return dirname(__DIR__) . '/uploads/elecciones/' . $year . '/CSV_' . $year . '_PP0228.csv';
}

function ensure_elections_csv($year)
{
    $path = get_elections_csv_path($year);
    if (!file_exists($path)) {
        $header = [
            'Tipo de organización comunal',
            'Nombre',
            'Fecha elección',
            'Hora elección',
            'Lugar elección',
            'Comunicación fecha de la elección',
            'Resultado elección',
            'Rol reclamación',
            'Reclamación',
            'Fallo de la reclamación'
        ];
        $handle = fopen($path, 'w');
        if ($handle !== false) {
            fputcsv($handle, $header, ';');
            fclose($handle);
        }
    }

    return $path;
}

function read_elections_rows($path)
{
    if (!file_exists($path)) {
        return [];
    }

    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        return $rows;
    }

    $header = fgetcsv($handle, 0, ';');
    if ($header === false) {
        fclose($handle);
        return $rows;
    }

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (!is_array($row)) {
            continue;
        }
        foreach ($row as $k => $value) {
            $row[$k] = normalize_csv_text($value);
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

function normalize_csv_text($value)
{
    $value = (string)$value;
    if ($value === '') {
        return '';
    }

    if (!function_exists('mb_detect_encoding') || !function_exists('mb_convert_encoding')) {
        return $value;
    }

    $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding === false) {
        $converted = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        return $converted !== false ? $converted : $value;
    }

    if ($encoding !== 'UTF-8') {
        $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);
        return $converted !== false ? $converted : $value;
    }

    return $value;
}

function write_elections_rows($path, $rows)
{
    $header = [
        'Tipo de organización comunal',
        'Nombre',
        'Fecha elección',
        'Hora elección',
        'Lugar elección',
        'Comunicación fecha de la elección',
        'Resultado elección',
        'Rol reclamación',
        'Reclamación',
        'Fallo de la reclamación'
    ];

    $handle = fopen($path, 'w');
    if ($handle === false) {
        return false;
    }

    fputcsv($handle, $header, ';');
    foreach ($rows as $row) {
        $values = [];
        for ($i = 0; $i < 10; $i++) {
            $values[] = isset($row[$i]) ? $row[$i] : '';
        }
        fputcsv($handle, $values, ';');
    }

    fclose($handle);
    return true;
}

function save_uploaded_attachment($year, $file, $defaultValue = '')
{
    if (!is_array($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return $defaultValue;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $defaultValue;
    }

    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', '7z', 'txt'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        return $defaultValue;
    }

    $baseDir = dirname(__DIR__) . '/uploads/elecciones/' . $year . '/archivos';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $filename = uniqid('eleccion_', true) . ($extension !== '' ? '.' . $extension : '');
    $targetPath = $baseDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $defaultValue;
    }

    return SITE_URL . 'uploads/elecciones/' . $year . '/archivos/' . $filename;
}

function format_date_for_csv($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return date('d-m-Y', strtotime($value));
    }

    return $value;
}

function normalize_date_for_form($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
        return date('Y-m-d', strtotime($value));
    }

    return $value;
}

function normalize_time_for_form($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];
        if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59) {
            return sprintf('%02d:%02d', $hours, $minutes);
        }
    }

    if (preg_match('/^(\d{1,2})\s*horas?/i', $value, $matches)) {
        return sprintf('%02d:00', (int)$matches[1]);
    }

    return $value;
}

function ensure_elections_numbering_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS elecciones_numeracion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ano INT NOT NULL,
                row_index INT NOT NULL,
                numero_eleccion INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_ano_row (ano, row_index),
                UNIQUE KEY uniq_ano_numero (ano, numero_eleccion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return (bool)$conn->query($sql);
}

function rebuild_elections_numbering_for_year($conn, $year, $rows)
{
    if (!ensure_elections_numbering_table($conn)) {
        return false;
    }

    $maxRetries = 2;
    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        $conn->begin_transaction();

        try {
            $stmtDelete = $conn->prepare('DELETE FROM elecciones_numeracion WHERE ano = ?');
            if ($stmtDelete === false) {
                throw new Exception('No se pudo preparar borrado de numeración.');
            }

            $stmtDelete->bind_param('i', $year);
            if (!$stmtDelete->execute()) {
                $stmtDelete->close();
                throw new Exception('No se pudo borrar numeración anterior.');
            }
            $stmtDelete->close();

            if (!empty($rows)) {
                $stmtInsert = $conn->prepare('INSERT INTO elecciones_numeracion (ano, row_index, numero_eleccion) VALUES (?, ?, ?)');
                if ($stmtInsert === false) {
                    throw new Exception('No se pudo preparar inserción de numeración.');
                }

                foreach ($rows as $index => $row) {
                    $rowIndex = (int)$index;
                    $numero = $rowIndex + 1;
                    $stmtInsert->bind_param('iii', $year, $rowIndex, $numero);
                    if (!$stmtInsert->execute()) {
                        $stmtInsert->close();
                        throw new Exception('No se pudo insertar numeración correlativa.');
                    }
                }
                $stmtInsert->close();
            }

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            if ($attempt === $maxRetries) {
                return false;
            }
        }
    }

    return false;
}

function get_elections_numbering_for_year($conn, $year)
{
    if (!ensure_elections_numbering_table($conn)) {
        return [];
    }

    $numbers = [];
    $stmt = $conn->prepare('SELECT row_index, numero_eleccion FROM elecciones_numeracion WHERE ano = ? ORDER BY row_index ASC');
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $numbers[(int)$row['row_index']] = (int)$row['numero_eleccion'];
    }

    $stmt->close();
    return $numbers;
}

$nombreItemEspecial = 'Elecciones - Juntas de vecinos y organizaciones comunitarias - Ley 21.146';
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
if ($selectedYear < 2000) {
    $selectedYear = date('Y');
}

$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
    if ($year < 2000) {
        $year = date('Y');
    }

    if ($_POST['action'] === 'create_year') {
        $newYear = isset($_POST['new_year']) ? (int)$_POST['new_year'] : 0;
        if ($newYear < 2000) {
            $_SESSION['error'] = 'Debe ingresar un año válido.';
            header('Location: elecciones.php?year=' . $selectedYear);
            exit;
        }

        ensure_elections_csv($newYear);
        $_SESSION['success'] = 'Año ' . $newYear . ' creado correctamente.';
        header('Location: elecciones.php?year=' . $newYear);
        exit;
    }

    if ($_POST['action'] === 'save') {
        $path = ensure_elections_csv($year);
        $rows = read_elections_rows($path);

        $tipo = trim((string)($_POST['tipo_organizacion'] ?? ''));
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $fecha = trim((string)($_POST['fecha_eleccion'] ?? ''));
        $hora = trim((string)($_POST['hora_eleccion'] ?? ''));
        $lugar = trim((string)($_POST['lugar_eleccion'] ?? ''));

        if ($nombre === '' || $fecha === '' || $hora === '' || $lugar === '' || $tipo === '') {
            $_SESSION['error'] = 'Complete los campos obligatorios: tipo, nombre, fecha, hora y lugar.';
            header('Location: elecciones.php?year=' . $year);
            exit;
        }

        $rowIndex = isset($_POST['row_index']) ? (int)$_POST['row_index'] : -1;
        $newRow = [
            $tipo,
            $nombre,
            format_date_for_csv($fecha),
            $hora,
            $lugar,
            ''
        ];

        $fieldNames = [
            'file_comunicacion' => 5,
            'file_resultado' => 6,
            'file_rol_reclamacion' => 7,
            'file_reclamacion' => 8,
            'file_fallo' => 9,
        ];

        foreach ($fieldNames as $fieldName => $index) {
            $existingValue = '';
            if ($rowIndex >= 0 && isset($rows[$rowIndex][$index])) {
                $existingValue = $rows[$rowIndex][$index];
            }

            if (isset($_FILES[$fieldName]) && is_array($_FILES[$fieldName])) {
                $uploadedValue = save_uploaded_attachment($year, $_FILES[$fieldName], $existingValue);
                $newRow[$index] = $uploadedValue;
            } else {
                $newRow[$index] = $existingValue;
            }
        }

        if ($rowIndex >= 0 && $rowIndex < count($rows)) {
            $rows[$rowIndex] = $newRow;
            $_SESSION['success'] = 'Elección actualizada correctamente.';
        } else {
            $rows[] = $newRow;
            $_SESSION['success'] = 'Elección agregada correctamente.';
        }

        if (!write_elections_rows($path, $rows)) {
            $_SESSION['error'] = 'No se pudo guardar el archivo CSV.';
        } elseif (!rebuild_elections_numbering_for_year($conn, $year, $rows)) {
            $_SESSION['error'] = 'No se pudo actualizar la numeración correlativa.';
        }

        $saveMode = trim((string)($_POST['save_mode'] ?? 'stay'));
        if ($saveMode === 'back') {
            header('Location: elecciones.php?year=' . $year);
        } else {
            header('Location: elecciones.php?year=' . $year . '&show_form=1');
        }
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $_SESSION['error'] = 'No se permite eliminar elecciones una vez creadas.';
        header('Location: elecciones.php?year=' . $year);
        exit;
    }

    if ($_POST['action'] === 'export') {
        $path = ensure_elections_csv($year);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="elecciones_' . $year . '.csv"');
        readfile($path);
        exit;
    }
}

require_once '../includes/header.php';

$csvPath = ensure_elections_csv($selectedYear);
$rows = read_elections_rows($csvPath);
rebuild_elections_numbering_for_year($conn, $selectedYear, $rows);
$numberingByRow = get_elections_numbering_for_year($conn, $selectedYear);
$nextNumeroEleccion = count($rows) + 1;
$availableYears = [];
$baseDir = dirname(__DIR__) . '/uploads/elecciones';
if (is_dir($baseDir)) {
    $yearDirectories = glob($baseDir . '/*', GLOB_ONLYDIR);
    foreach ($yearDirectories as $dir) {
        $yearName = basename($dir);
        if (ctype_digit($yearName)) {
            $availableYears[] = (int)$yearName;
        }
    }
}

if (!in_array($selectedYear, $availableYears, true)) {
    $availableYears[] = $selectedYear;
}

$availableYears = array_values(array_unique($availableYears));
sort($availableYears);

$editRow = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editIndex = (int)$_GET['edit'];
    if ($editIndex >= 0 && $editIndex < count($rows)) {
        $editRow = $rows[$editIndex];
    }
}

$showForm = isset($_GET['show_form']) && $_GET['show_form'] === '1';
if ($editRow !== null) {
    $showForm = true;
}
?>

<style>
.elecciones-table {
    font-size: 0.78rem;
}

.elecciones-table th,
.elecciones-table td {
    padding: 0.35rem 0.45rem;
    vertical-align: top;
}

.elecciones-col-nombre {
    max-width: 210px;
    width: 210px;
}

.elecciones-col-lugar {
    max-width: 180px;
    width: 180px;
}

.elecciones-col-link {
    min-width: 90px;
    max-width: 90px;
    width: 90px;
}

.elecciones-col-numero {
    width: 60px;
    min-width: 60px;
    max-width: 60px;
    text-align: center;
}

.elecciones-link-icon {
    color: #dc3545;
    font-size: 1.1rem;
    line-height: 1;
}

.elecciones-texto-ayuda {
    color: #9aa0a6;
}
</style>

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

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarAno">
                    <i class="bi bi-calendar-plus"></i> Agregar año
                </button>

                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small">Filtro año</label>
                    <select class="form-select form-select-sm" name="year" onchange="this.form.submit()" style="width: 95px;">
                        <?php foreach ($availableYears as $yearOption): ?>
                            <option value="<?php echo (int)$yearOption; ?>" <?php echo $yearOption === $selectedYear ? 'selected' : ''; ?>><?php echo (int)$yearOption; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <button type="button" class="btn btn-outline-danger" onclick="window.print()">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                </button>

                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="export">
                    <input type="hidden" name="year" value="<?php echo (int)$selectedYear; ?>">
                    <button type="submit" class="btn btn-success">Exportar CSV</button>
                </form>
            </div>

            <div>
                <?php if ($showForm): ?>
                    <a class="btn btn-outline-secondary" href="elecciones.php?year=<?php echo (int)$selectedYear; ?>">
                        <i class="bi bi-x-circle"></i> Cerrar formulario
                    </a>
                <?php else: ?>
                    <a class="btn btn-primary" href="elecciones.php?year=<?php echo (int)$selectedYear; ?>&show_form=1">
                        <i class="bi bi-plus-circle"></i> Agregar elección
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($showForm): ?>
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <strong><?php echo $editRow === null ? 'Formulario de nueva elección' : 'Editar elección'; ?></strong>
            <div class="small elecciones-texto-ayuda">Complete los datos y adjunte archivos si corresponde.</div>
        </div>
        <?php if ($editRow !== null): ?>
            <a class="btn btn-outline-secondary btn-sm" href="elecciones.php?year=<?php echo (int)$selectedYear; ?>&show_form=1">Cancelar edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="year" value="<?php echo (int)$selectedYear; ?>">
            <input type="hidden" name="row_index" value="<?php echo $editRow === null ? '-1' : (int)$_GET['edit']; ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">N° Elección</label>
                    <input class="form-control" type="text" value="<?php echo (int)($editRow !== null ? ($numberingByRow[(int)$_GET['edit']] ?? ((int)$_GET['edit'] + 1)) : $nextNumeroEleccion); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de organización comunal</label>
                    <select class="form-select" name="tipo_organizacion" required>
                        <option value="">Seleccione...</option>
                        <option value="Junta de vecinos" <?php echo ($editRow !== null && ($editRow[0] ?? '') === 'Junta de vecinos') ? 'selected' : ''; ?>>Junta de vecinos</option>
                        <option value="Organización comunitaria funcional" <?php echo ($editRow !== null && ($editRow[0] ?? '') === 'Organización comunitaria funcional') ? 'selected' : ''; ?>>Organización comunitaria funcional</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input class="form-control" type="text" name="nombre" value="<?php echo htmlspecialchars($editRow !== null ? ($editRow[1] ?? '') : ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha elección</label>
                    <input class="form-control" type="date" name="fecha_eleccion" value="<?php echo htmlspecialchars($editRow !== null ? normalize_date_for_form($editRow[2] ?? '') : ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hora elección</label>
                    <input class="form-control" type="time" name="hora_eleccion" value="<?php echo htmlspecialchars($editRow !== null ? normalize_time_for_form($editRow[3] ?? '') : ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lugar elección</label>
                    <input class="form-control" type="text" name="lugar_eleccion" value="<?php echo htmlspecialchars($editRow !== null ? ($editRow[4] ?? '') : ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Comunicación fecha de la elección</label>
                    <?php if ($editRow !== null && !empty($editRow[5] ?? '')): ?>
                        <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-light">
                            <span class="text-success"><i class="bi bi-file-earmark-pdf-fill"></i> Cargado</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Reemplazar documento pdf existente" onclick="this.closest('.col-md-6').querySelector('input[type=file]').click()">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <input class="form-control mt-2" type="file" name="file_comunicacion" style="display:none;">
                    <?php else: ?>
                        <input class="form-control" type="file" name="file_comunicacion">
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Resultado elección</label>
                    <?php if ($editRow !== null && !empty($editRow[6] ?? '')): ?>
                        <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-light">
                            <span class="text-success"><i class="bi bi-file-earmark-pdf-fill"></i> Cargado</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Reemplazar documento pdf existente" onclick="this.closest('.col-md-6').querySelector('input[type=file]').click()">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <input class="form-control mt-2" type="file" name="file_resultado" style="display:none;">
                    <?php else: ?>
                        <input class="form-control" type="file" name="file_resultado">
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rol reclamación</label>
                    <?php if ($editRow !== null && !empty($editRow[7] ?? '')): ?>
                        <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-light">
                            <span class="text-success"><i class="bi bi-file-earmark-pdf-fill"></i> Cargado</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Reemplazar documento pdf existente" onclick="this.closest('.col-md-6').querySelector('input[type=file]').click()">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <input class="form-control mt-2" type="file" name="file_rol_reclamacion" style="display:none;">
                    <?php else: ?>
                        <input class="form-control" type="file" name="file_rol_reclamacion">
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reclamación</label>
                    <?php if ($editRow !== null && !empty($editRow[8] ?? '')): ?>
                        <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-light">
                            <span class="text-success"><i class="bi bi-file-earmark-pdf-fill"></i> Cargado</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Reemplazar documento pdf existente" onclick="this.closest('.col-md-6').querySelector('input[type=file]').click()">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <input class="form-control mt-2" type="file" name="file_reclamacion" style="display:none;">
                    <?php else: ?>
                        <input class="form-control" type="file" name="file_reclamacion">
                    <?php endif; ?>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Fallo de la reclamación</label>
                    <?php if ($editRow !== null && !empty($editRow[9] ?? '')): ?>
                        <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-light">
                            <span class="text-success"><i class="bi bi-file-earmark-pdf-fill"></i> Cargado</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Reemplazar documento pdf existente" onclick="this.closest('.col-md-12').querySelector('input[type=file]').click()">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <input class="form-control mt-2" type="file" name="file_fallo" style="display:none;">
                    <?php else: ?>
                        <input class="form-control" type="file" name="file_fallo">
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" name="save_mode" value="stay" class="btn btn-primary">
                    <i class="bi bi-floppy"></i> Guardar
                </button>
                <button type="submit" name="save_mode" value="back" class="btn btn-success">
                    <i class="bi bi-check2-circle"></i> Guardar y volver
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong>Listado de elecciones - Año <?php echo (int)$selectedYear; ?></strong>
        <span class="badge bg-secondary"><?php echo count($rows); ?> registros</span>
    </div>
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-inboxes"></i> No hay elecciones registradas para este año.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle elecciones-table">
                    <thead class="table-light">
                        <tr>
                            <th class="elecciones-col-numero">N°</th>
                            <th>Tipo</th>
                            <th class="elecciones-col-nombre">Nombre</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th class="elecciones-col-lugar">Lugar</th>
                            <th class="elecciones-col-link">Comunicación fecha de la elección</th>
                            <th class="elecciones-col-link">Resultado elección</th>
                            <th class="elecciones-col-link">Rol reclamación</th>
                            <th class="elecciones-col-link">Reclamación</th>
                            <th class="elecciones-col-link">Fallo de la reclamación</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td class="elecciones-col-numero"><?php echo (int)($numberingByRow[$index] ?? ($index + 1)); ?></td>
                                <td><?php echo htmlspecialchars($row[0] ?? ''); ?></td>
                                <td class="elecciones-col-nombre text-truncate" title="<?php echo htmlspecialchars($row[1] ?? ''); ?>"><?php echo htmlspecialchars($row[1] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row[2] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row[3] ?? ''); ?></td>
                                <td class="elecciones-col-lugar text-truncate" title="<?php echo htmlspecialchars($row[4] ?? ''); ?>"><?php echo htmlspecialchars($row[4] ?? ''); ?></td>
                                <td>
                                    <?php if (!empty(trim((string)($row[5] ?? '')))): ?>
                                        <a href="<?php echo htmlspecialchars($row[5]); ?>" target="_blank" rel="noopener" class="elecciones-link-icon" title="Comunicación fecha de la elección">
                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty(trim((string)($row[6] ?? '')))): ?>
                                        <a href="<?php echo htmlspecialchars($row[6]); ?>" target="_blank" rel="noopener" class="elecciones-link-icon" title="Resultado elección">
                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty(trim((string)($row[7] ?? '')))): ?>
                                        <a href="<?php echo htmlspecialchars($row[7]); ?>" target="_blank" rel="noopener" class="elecciones-link-icon" title="Rol reclamación">
                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty(trim((string)($row[8] ?? '')))): ?>
                                        <a href="<?php echo htmlspecialchars($row[8]); ?>" target="_blank" rel="noopener" class="elecciones-link-icon" title="Reclamación">
                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty(trim((string)($row[9] ?? '')))): ?>
                                        <a href="<?php echo htmlspecialchars($row[9]); ?>" target="_blank" rel="noopener" class="elecciones-link-icon" title="Fallo de la reclamación">
                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-outline-primary" href="elecciones.php?year=<?php echo (int)$selectedYear; ?>&edit=<?php echo (int)$index; ?>"><i class="bi bi-pencil"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalAgregarAno" tabindex="-1" aria-labelledby="modalAgregarAnoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarAnoLabel">Agregar año</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_year">
                    <div class="mb-2">
                        <label class="form-label">Año a crear</label>
                        <input class="form-control" type="number" name="new_year" min="2000" step="1" placeholder="2027" required>
                        <small class="text-muted">Se creará carpeta y CSV si no existen.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear año</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
