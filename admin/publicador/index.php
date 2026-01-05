<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once dirname(dirname(__DIR__)) . '/includes/check_auth.php';
// Permitir acceso a administrativo, director y publicador
if ($current_profile !== 'administrativo' && $current_profile !== 'director_revisor' && $current_profile !== 'publicador') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// LUEGO: Incluir header con HTML
require_once dirname(dirname(__DIR__)) . '/includes/header.php';

require_once dirname(dirname(__DIR__)) . '/classes/Documento.php';
require_once dirname(dirname(__DIR__)) . '/classes/Item.php';
require_once dirname(dirname(__DIR__)) . '/classes/Usuario.php';
require_once dirname(dirname(__DIR__)) . '/classes/Verificador.php';

$documentoClass = new Documento($db->getConnection());
$itemClass = new Item($db->getConnection());
$usuarioClass = new Usuario($db->getConnection());
$verificadorClass = new Verificador($db->getConnection());

// Obtener mes y año actual
$mesActual = (int)date('m');
$anoActual = (int)date('Y');

// Permitir seleccionar mes
$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : $mesActual;
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : $anoActual;

// Validar mes y año
if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = $mesActual;
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = $anoActual;

// Obtener todos los items
$itemsResult = $itemClass->getAll();
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
         'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mesBusqueda = $mesSeleccionado;
?>

<div class="page-header" style="background: linear-gradient(135deg, #8e44ad 0%, #3a0f5c 100%); color: white; padding: 2.5rem 2rem; margin: -1rem -1rem 2rem -1rem; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h1 style="font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem;">
        <i class="bi bi-check-circle" style="color: #f39c12; margin-right: 0.5rem;"></i> 
        Centro de Publicación y Transparencia Activa
    </h1>
    <p style="margin: 0; color: #ecf0f1; font-size: 1.05rem;">
        Revise los documentos cargados por los usuarios y publíquelos en Transparencia Activa
    </p>
</div>

<!-- NOTA INFORMATIVA -->
<div class="alert alert-info border-2" style="margin-bottom: 2rem;">
    <i class="bi bi-info-circle-fill" style="font-size: 1.3rem; margin-right: 0.5rem;"></i>
    <strong>Proceso de Publicación:</strong>
    <ul style="margin-top: 0.5rem; margin-bottom: 0;">
        <li><strong>Estado "Cargado":</strong> Documento subido por el usuario, lista para publicar en Transparencia Activa</li>
        <li><strong>Agregar Verificador:</strong> Al cargar la imagen de verificación, el documento pasa a estado "Publicado"</li>
        <li><strong>Estado "Publicado":</strong> Documento publicado y disponible en el Portal de Transparencia Activa</li>
    </ul>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calendar3"></i> Seleccionar Período
            </div>
            <div class="card-body">
                <form method="GET" class="d-flex gap-2">
                    <select name="mes" class="form-select" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($m === $mesSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo $meses[$m]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    
                    <select name="ano" class="form-select" onchange="this.form.submit()">
                        <?php for ($a = 2024; $a <= 2026; $a++): ?>
                            <option value="<?php echo $a; ?>" <?php echo ($a === $anoSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo $a; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
    
    <?php
    // Contar documentos sin verificación para el mes seleccionado
    $docsSinVerif = 0;
    $docsConVerif = 0;
    $itemsConDoc = [];
    
    foreach ($items as $item) {
        // Para mensuales, buscar por mes; para anuales, por año
        if ($item['periodicidad'] === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpCargados($item['id'], 1, $anoSeleccionado);
        } else {
            $docsResult = $documentoClass->getByItemFollowUpCargados($item['id'], $mesSeleccionado, $anoSeleccionado);
        }
        
        if ($docsResult && $docsResult->num_rows > 0) {
            while ($doc = $docsResult->fetch_assoc()) {
                $verificador = $verificadorClass->getByDocumento($doc['documento_id']);
                if (!$verificador) {
                    $docsSinVerif++;
                } else {
                    $docsConVerif++;
                }
                $itemsConDoc[$item['id']] = $doc;
            }
        }
    }
    
    if ($docsSinVerif > 0 || $docsConVerif > 0) {
        echo '<div class="col-md-6">';
        if ($docsSinVerif > 0) {
            echo '<div class="alert alert-warning border-2" style="border-color: #ff9800 !important; background-color: #fff3cd; color: #856404; margin-bottom: 0.5rem;">';
            echo '<i class="bi bi-clock-history" style="font-size: 1.3rem; margin-right: 0.5rem;"></i>';
            echo '<strong>¡Documentos Pendientes!</strong><br>';
            echo '<span style="font-size: 0.95rem;"><strong>' . $docsSinVerif . ' documento(s)</strong> sin verificador</span>';
            echo '</div>';
        }
        if ($docsConVerif > 0) {
            echo '<div class="alert alert-success border-2" style="border-color: #28a745 !important; background-color: #d4edda; color: #155724;">';
            echo '<i class="bi bi-check-circle-fill" style="font-size: 1.3rem; margin-right: 0.5rem;"></i>';
            echo '<strong>¡Documentos Publicados!</strong><br>';
            echo '<span style="font-size: 0.95rem;"><strong>' . $docsConVerif . ' documento(s)</strong> ya publicados</span>';
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
</div>

<!-- TABLA POR PERIODICIDAD -->
<?php
// Agrupar items por periodicidad
$itemsPorPeriodicidad = [
    'mensual' => [],
    'trimestral' => [],
    'semestral' => [],
    'anual' => [],
    'ocurrencia' => []
];

foreach ($items as $item) {
    $periodicidad = $item['periodicidad'] ?? 'ocurrencia';
    $itemsPorPeriodicidad[$periodicidad][] = $item;
}

$periodosNombres = [
    'mensual' => 'Items Mensuales',
    'trimestral' => 'Items Trimestrales',
    'semestral' => 'Items Semestrales',
    'anual' => 'Items Anuales',
    'ocurrencia' => 'Items por Ocurrencia'
];

foreach ($itemsPorPeriodicidad as $periodicidad => $itemsGrupo) {
    if (count($itemsGrupo) === 0) continue;
    
    echo '<div class="card mt-4">';
    echo '<div class="card-header bg-secondary text-white">';
    echo '<i class="bi bi-list"></i> ' . $periodosNombres[$periodicidad] . ' - ' . $meses[$mesSeleccionado] . ' ' . $anoSeleccionado;
    echo '</div>';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover mb-0">';
    echo '<thead class="table-light">';
    echo '<tr>';
    echo '<th width="10%">Num</th>';
    echo '<th width="25%">Item</th>';
    echo '<th width="10%">Estado</th>';
    echo '<th width="20%">Cargado Por</th>';
    echo '<th width="12%">Fecha</th>';
    echo '<th width="23%">Acciones</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($itemsGrupo as $item) {
        // Verificar si tiene documento
        if ($item['periodicidad'] === 'anual') {
            $docsResult = $documentoClass->getByItemFollowUpCargados($item['id'], 1, $anoSeleccionado);
        } else {
            $docsResult = $documentoClass->getByItemFollowUpCargados($item['id'], $mesSeleccionado, $anoSeleccionado);
        }
        
        $documento = null;
        if ($docsResult && $docsResult->num_rows > 0) {
            $documento = $docsResult->fetch_assoc();
        }
        
        $estadoBadge = '';
        $usuario = '';
        $fecha = '';
        $botones = '';
        
        if ($documento) {
            $verificador = $verificadorClass->getByDocumento($documento['documento_id']);
            $usuario = $usuarioClass->getById($documento['usuario_id']);
            $fecha = date('d/m/Y H:i', strtotime($documento['fecha_envio']));
            
            if ($verificador) {
                $estadoBadge = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Publicado</span>';
                $botones = '<a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                           data-bs-target="#modalVerDocumento" 
                           onclick="verDocumento(' . $documento['documento_id'] . ', \'' . htmlspecialchars($item['nombre']) . '\', \'' . htmlspecialchars($documento['titulo']) . '\');">
                            <i class="bi bi-eye"></i> Ver Doc
                        </a>
                        <a href="#" class="btn btn-sm btn-success" data-bs-toggle="modal" 
                           data-bs-target="#modalVerVerificador"
                           onclick="verVerificador(' . $verificador['id'] . ', \'' . htmlspecialchars($verificador['archivo_verificador']) . '\');">
                            <i class="bi bi-file-check"></i> Ver Verif
                        </a>';
            } else {
                $estadoBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Cargado</span>';
                $botones = '<a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                           data-bs-target="#modalVerDocumento" 
                           onclick="verDocumento(' . $documento['documento_id'] . ', \'' . htmlspecialchars($item['nombre']) . '\', \'' . htmlspecialchars($documento['titulo']) . '\');">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                        <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                           data-bs-target="#modalCargarVerificador"
                           onclick="seleccionarDocumento(' . $documento['documento_id'] . ', ' . $item['id'] . ', ' . $documento['usuario_id'] . ', \'' . htmlspecialchars($item['nombre']) . '\');">
                            <i class="bi bi-plus-circle"></i> Agregar Verificador
                        </a>';
            }
        } else {
            $estadoBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Sin Cargar</span>';
            $usuario = '-';
            $fecha = '-';
            $botones = '<span class="text-muted"><small>Pendiente de cargar</small></span>';
        }
        
        echo '<tr>';
        echo '<td><small>' . htmlspecialchars($item['numeracion']) . '</small></td>';
        echo '<td><strong>' . htmlspecialchars($item['nombre']) . '</strong></td>';
        echo '<td>' . $estadoBadge . '</td>';
        echo '<td><small>' . ($documento ? htmlspecialchars($usuario['nombre'] ?? 'Desconocido') : $usuario) . '</small></td>';
        echo '<td><small>' . $fecha . '</small></td>';
        echo '<td>' . $botones . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
}
?>

<!-- MODAL: Ver Documento -->
<div class="modal fade" id="modalVerDocumento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark"></i> Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVerDocumentoBody">
                Cargando...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Cargar Verificador -->
<div class="modal fade" id="modalCargarVerificador" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-cloud-upload"></i> Cargar Verificador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="cargar_verificador.php">
                <div class="modal-body">
                    <input type="hidden" name="documento_id" id="docIdInput">
                    <input type="hidden" name="item_id" id="itemIdInput">
                    <input type="hidden" name="usuario_id" id="usuarioIdInput">
                    
                    <small class="text-muted d-block mb-3">
                        Está cargando verificador para: <strong id="itemNombreVerif">-</strong>
                    </small>
                    
                    <div class="mb-3">
                        <label class="form-label">Imagen del Verificador <span class="text-danger">*</span></label>
                        
                        <!-- Area de Pega -->
                        <div id="pasteArea" tabindex="0" class="border-2 border-dashed rounded p-4 text-center mb-3" 
                             style="background-color: #f8f9fa; cursor: pointer; min-height: 120px; display: flex; align-items: center; justify-content: center; outline: none;">
                            <div>
                                <i class="bi bi-clipboard-pulse" style="font-size: 2rem; color: #6c757d;"></i>
                                <p class="mt-2 mb-0"><strong>Pega la imagen aquí</strong></p>
                                <small class="text-muted">O usa el botón "Seleccionar" abajo</small>
                            </div>
                        </div>
                        
                        <!-- Preview de imagen pegada -->
                        <div id="previewContainer" class="mb-3 d-none">
                            <p><strong>Preview:</strong></p>
                            <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 5px;">
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="limpiarPreview()">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                        
                        <!-- Input de archivo tradicional -->
                        <input type="file" class="form-control" id="archivoVerificador" name="archivo_verificador"
                               accept=".jpg,.jpeg,.png,.gif,.pdf" style="display: none;">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('archivoVerificador').click();">
                            <i class="bi bi-folder-open"></i> Seleccionar archivo
                        </button>
                        <small class="text-muted d-block mt-2">✓ Formatos: JPG, PNG, GIF, PDF (máximo 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comentarios" class="form-label">Comentarios (Opcional)</label>
                        <textarea class="form-control" id="comentarios" name="comentarios" rows="3" 
                                  placeholder="Notas sobre la verificación..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Cargar Verificador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Ver Verificador -->
<div class="modal fade" id="modalVerVerificador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-check"></i> Verificador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVerVerificadorBody">
                Cargando...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDocumento(docId, itemNombre, titulo) {
    fetch(`get_documento.php?doc_id=${docId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerDocumentoBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalVerDocumentoBody').innerHTML = '<p class="text-danger">Error al cargar el documento</p>';
        });
}

function verVerificador(verifId, archivo) {
    fetch(`get_verificador.php?verif_id=${verifId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerVerificadorBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalVerVerificadorBody').innerHTML = '<p class="text-danger">Error al cargar el verificador</p>';
        });
}

function seleccionarDocumento(docId, itemId, usuarioId, itemNombre) {
    document.getElementById('docIdInput').value = docId;
    document.getElementById('itemIdInput').value = itemId;
    document.getElementById('usuarioIdInput').value = usuarioId;
    document.getElementById('itemNombreVerif').textContent = itemNombre;
    
    // Dar focus al área de paste cuando se abre el modal
    setTimeout(function() {
        const pasteArea = document.getElementById('pasteArea');
        if (pasteArea) {
            pasteArea.focus();
        }
    }, 100);
}

// Funciones para manejo de paste de imágenes
document.addEventListener('DOMContentLoaded', function() {
    const pasteArea = document.getElementById('pasteArea');
    const fileInput = document.getElementById('archivoVerificador');
    
    // Listener de paste en el DOCUMENTO completo
    document.addEventListener('paste', function(e) {
        // Solo si el modal está visible
        const modal = document.getElementById('modalCargarVerificador');
        if (modal && modal.classList.contains('show')) {
            e.preventDefault();
            const items = e.clipboardData.items;
            
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const file = items[i].getAsFile();
                    procesarImagenPegada(file);
                    return;
                }
            }
            
            alert('Por favor pega una imagen válida');
        }
    });
    
    // Listener de paste en el área (alternativo)
    pasteArea.addEventListener('paste', function(e) {
        e.preventDefault();
        const items = e.clipboardData.items;
        
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const file = items[i].getAsFile();
                procesarImagenPegada(file);
                return;
            }
        }
        
        alert('Por favor pega una imagen válida');
    });
    
    // Permitir drag & drop en el área
    pasteArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        pasteArea.style.backgroundColor = '#e7f3ff';
        pasteArea.style.borderColor = '#0066cc';
    });
    
    pasteArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        pasteArea.style.backgroundColor = '#f8f9fa';
        pasteArea.style.borderColor = '#dee2e6';
    });
    
    pasteArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        pasteArea.style.backgroundColor = '#f8f9fa';
        pasteArea.style.borderColor = '#dee2e6';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // Buscar primera imagen
            for (let i = 0; i < files.length; i++) {
                if (files[i].type.indexOf('image') !== -1 || files[i].type === 'application/pdf') {
                    procesarImagenPegada(files[i]);
                    return;
                }
            }
            alert('Por favor arrastra una imagen válida (JPG, PNG, GIF, PDF)');
        }
    });
    
    // Permitir click en el área para abrir selector
    pasteArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Manejar selección de archivo
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            procesarImagenPegada(e.target.files[0]);
        }
    });
});

// Función para ver imagen en pantalla completa
function verEnPantallaCompleta(archivo) {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: pointer;';
    
    // Crear contenedor de imagen
    const contenedor = document.createElement('div');
    contenedor.style.cssText = 'position: relative; width: 95%; height: 95%; display: flex; align-items: center; justify-content: center; overflow: auto;';
    
    // Crear imagen
    const img = document.createElement('img');
    img.src = '<?php echo SITE_URL; ?>uploads/' + decodeURIComponent(archivo);
    img.style.cssText = 'max-width: 100%; max-height: 100%; object-fit: contain;';
    img.alt = 'Verificador en pantalla completa';
    
    // Botón cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<i class="bi bi-x-lg" style="font-size: 1.5rem;"></i>';
    btnCerrar.style.cssText = 'position: absolute; top: 20px; right: 20px; background: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-size: 1.5rem; z-index: 10001; box-shadow: 0 2px 10px rgba(0,0,0,0.3);';
    btnCerrar.title = 'Cerrar (ESC)';
    btnCerrar.onclick = function(e) { 
        e.stopPropagation();
        overlay.remove(); 
    };
    
    // Assembler
    contenedor.appendChild(img);
    contenedor.appendChild(btnCerrar);
    overlay.appendChild(contenedor);
    
    // Agregar al documento
    document.body.appendChild(overlay);
    
    // Cerrar con click fuera
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
    
    // Cerrar con ESC
    const handleEsc = function(e) {
        if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', handleEsc);
        }
    };
    document.addEventListener('keydown', handleEsc);
}

function procesarImagenPegada(file) {
    // Validar tamaño (5MB max)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('El archivo es demasiado grande (máximo 5MB)');
        return;
    }
    
    // Validar tipo
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        alert('Formato no permitido. Solo: JPG, PNG, GIF, PDF');
        return;
    }
    
    // Mostrar preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImage').src = e.target.result;
        document.getElementById('previewContainer').classList.remove('d-none');
        document.getElementById('pasteArea').style.display = 'none';
    };
    reader.readAsDataURL(file);
    
    // Asignar archivo al input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    document.getElementById('archivoVerificador').files = dataTransfer.files;
}

function limpiarPreview() {
    document.getElementById('previewContainer').classList.add('d-none');
    document.getElementById('previewImage').src = '';
    document.getElementById('pasteArea').style.display = 'flex';
    document.getElementById('archivoVerificador').value = '';
}
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
