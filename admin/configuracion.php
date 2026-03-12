<?php
/**
 * Configuración del Sistema
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

require_once dirname(__DIR__) . '/config/config.php';

requireRole('admin');

$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Resetear numeración global
    if (isset($_POST['resetear_numeracion'])) {
        update("UPDATE numeracion_global SET letra_actual = 'A', numero_actual = 0");
        $mensaje = 'Numeración global reseteada a A1';
    } elseif (isset($_POST['resetear_manual'])) {
        $letraReset = strtoupper(sanitize($_POST['letra_reseteo'] ?? 'A'));
        $numeroReset = max(0, (int)($_POST['numero_reseteo'] ?? 0));
        
        // Validar que la letra esté en el rango A-G
        if (!preg_match('/^[A-G]$/', $letraReset)) {
            $error = 'La letra debe estar entre A y G';
        } else {
            update("UPDATE numeracion_global SET letra_actual = ?, numero_actual = ?", [$letraReset, $numeroReset]);
            $mensaje = 'Numeración configurada manualmente a ' . $letraReset . ($numeroReset + 1);
        }
    } else {
        $configs = [
            'nombre_municipalidad',
            'audio_activo',
            'tiempo_rellamado',
            'mostrar_ultimos',
            'voz_velocidad',
            'voz_tono',
            'letra_maxima',
            'numero_maximo'
        ];
        
        foreach ($configs as $clave) {
            if (isset($_POST[$clave])) {
                setConfig($clave, sanitize($_POST[$clave]));
            }
        }
        
        $mensaje = 'Configuración guardada exitosamente';
    }
}

// Obtener configuraciones actuales
$config = [
    'nombre_municipalidad' => getConfig('nombre_municipalidad', 'Municipalidad de Los Lagos'),
    'audio_activo' => getConfig('audio_activo', '1'),
    'tiempo_rellamado' => getConfig('tiempo_rellamado', '30'),
    'mostrar_ultimos' => getConfig('mostrar_ultimos', '4'),
    'voz_velocidad' => getConfig('voz_velocidad', '1'),
    'voz_tono' => getConfig('voz_tono', '1'),
    'letra_maxima' => getConfig('letra_maxima', 'A'),
    'numero_maximo' => getConfig('numero_maximo', '99')
];

// Obtener estado actual de numeración global
$estadoNumeracion = fetchOne("SELECT * FROM numeracion_global ORDER BY id DESC LIMIT 1");
if (!$estadoNumeracion) {
    $estadoNumeracion = ['letra_actual' => 'A', 'numero_actual' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema de Numeración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="bi bi-gear me-2"></i>Configuración del Sistema</h2>
                <p class="text-muted mb-0">Ajuste los parámetros generales del sistema</p>
            </div>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nombre_municipalidad" class="form-label">Nombre de la Municipalidad</label>
                                <input type="text" class="form-control" id="nombre_municipalidad" 
                                       name="nombre_municipalidad" value="<?= htmlspecialchars($config['nombre_municipalidad']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-123 me-2"></i>Sistema de Numeración</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <small>El sistema genera números en formato: A1, A2...A99, B1, B2...B99, etc. Al llegar a la letra máxima, se reinicia automáticamente en A1.</small>
                            </div>
                            <div class="mb-3">
                                <label for="letra_maxima" class="form-label">Letra Máxima</label>
                                <select class="form-select" id="letra_maxima" name="letra_maxima">
                                    <option value="A" <?= $config['letra_maxima'] == 'A' ? 'selected' : '' ?>>A (Solo A1-A99)</option>
                                    <option value="B" <?= $config['letra_maxima'] == 'B' ? 'selected' : '' ?>>B (A1-A99, B1-B99)</option>
                                    <option value="C" <?= $config['letra_maxima'] == 'C' ? 'selected' : '' ?>>C (A1-A99, B1-B99, C1-C99)</option>
                                    <option value="D" <?= $config['letra_maxima'] == 'D' ? 'selected' : '' ?>>D (Hasta D1-D99)</option>
                                    <option value="E" <?= $config['letra_maxima'] == 'E' ? 'selected' : '' ?>>E (Hasta E1-E99)</option>
                                    <option value="F" <?= $config['letra_maxima'] == 'F' ? 'selected' : '' ?>>F (Hasta F1-F99)</option>
                                    <option value="G" <?= $config['letra_maxima'] == 'G' ? 'selected' : '' ?>>G (Hasta G1-G99)</option>
                                </select>
                                <small class="text-muted">Al llegar a esta letra con el número máximo, el sistema se reinicia en A1</small>
                            </div>
                            <div class="mb-3">
                                <label for="numero_maximo" class="form-label">Número Máximo por Letra</label>
                                <input type="number" class="form-control" id="numero_maximo" 
                                       name="numero_maximo" value="<?= $config['numero_maximo'] ?>" 
                                       min="10" max="999" step="1">
                                <small class="text-muted">Ejemplo: Si configura 99, cada letra irá de 1 a 99 (A1-A99, B1-B99...)</small>
                            </div>
                            <div class="border-top pt-3">
                                <p class="mb-2 fw-bold">Resetear Numeración</p>
                                <div class="row mb-3">
                                    <div class="col-12 mb-2">
                                        <button type="button" class="btn btn-warning btn-sm" onclick="resetearNumeracion()">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Resetear a A1
                                        </button>
                                        <small class="text-muted d-block mt-1">Reinicia rápidamente al inicio (A1)</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <label class="form-label small fw-bold">Configuración Manual</label>
                                        <div class="alert alert-info alert-sm py-2">
                                            <small>
                                                <i class="bi bi-info-circle me-1"></i>
                                                Estado actual: <strong><?= $estadoNumeracion['letra_actual'] ?><?= $estadoNumeracion['numero_actual'] ?></strong>
                                                | Próximo a llamar: <strong><?php 
                                                    $proxNum = $estadoNumeracion['numero_actual'] + 1;
                                                    $proxLetra = $estadoNumeracion['letra_actual'];
                                                    if ($proxNum > (int)$config['numero_maximo']) {
                                                        $proxNum = 1;
                                                        $proxLetra = chr(ord($proxLetra) + 1);
                                                        if (ord($proxLetra) > ord($config['letra_maxima'])) {
                                                            $proxLetra = 'A';
                                                        }
                                                    }
                                                    echo $proxLetra . $proxNum;
                                                ?></strong>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label for="letra_reseteo" class="form-label small">Letra Actual</label>
                                        <select class="form-select form-select-sm" id="letra_reseteo" name="letra_reseteo">
                                            <option value="A" <?= $estadoNumeracion['letra_actual'] == 'A' ? 'selected' : '' ?>>A</option>
                                            <option value="B" <?= $estadoNumeracion['letra_actual'] == 'B' ? 'selected' : '' ?>>B</option>
                                            <option value="C" <?= $estadoNumeracion['letra_actual'] == 'C' ? 'selected' : '' ?>>C</option>
                                            <option value="D" <?= $estadoNumeracion['letra_actual'] == 'D' ? 'selected' : '' ?>>D</option>
                                            <option value="E" <?= $estadoNumeracion['letra_actual'] == 'E' ? 'selected' : '' ?>>E</option>
                                            <option value="F" <?= $estadoNumeracion['letra_actual'] == 'F' ? 'selected' : '' ?>>F</option>
                                            <option value="G" <?= $estadoNumeracion['letra_actual'] == 'G' ? 'selected' : '' ?>>G</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label for="numero_reseteo" class="form-label small">Número Actual</label>
                                        <input type="number" class="form-control form-control-sm" id="numero_reseteo" 
                                               name="numero_reseteo" value="<?= $estadoNumeracion['numero_actual'] ?>" min="0" max="999">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <button type="button" class="btn btn-success btn-sm" onclick="resetearManual()">
                                            <i class="bi bi-check-circle me-1"></i>Aplicar Configuración Manual
                                        </button>
                                        <small class="text-muted d-block mt-1">El próximo número será: <strong id="proximoNumero"><?= $proxLetra . $proxNum ?></strong></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-tv me-2"></i>Pantalla Pública</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="mostrar_ultimos" class="form-label">Cantidad de Últimos Llamados a Mostrar</label>
                                <input type="number" class="form-control" id="mostrar_ultimos" 
                                       name="mostrar_ultimos" value="<?= $config['mostrar_ultimos'] ?>" min="1" max="10">
                            </div>
                            <div class="mb-3">
                                <label for="tiempo_rellamado" class="form-label">Segundos para Auto Re-llamado</label>
                                <input type="number" class="form-control" id="tiempo_rellamado" 
                                       name="tiempo_rellamado" value="<?= $config['tiempo_rellamado'] ?>" min="10" max="120">
                                <small class="text-muted">Tiempo para que el sistema sugiera volver a llamar</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-volume-up me-2"></i>Configuración de Audio</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="audio_activo" 
                                           name="audio_activo" value="1" <?= $config['audio_activo'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="audio_activo">Audio Activo</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="voz_velocidad" class="form-label">
                                    Velocidad de la Voz: <span id="velocidadValor"><?= $config['voz_velocidad'] ?></span>
                                </label>
                                <input type="range" class="form-range" id="voz_velocidad" 
                                       name="voz_velocidad" value="<?= $config['voz_velocidad'] ?>" 
                                       min="0.5" max="2" step="0.1">
                                <small class="text-muted">0.5 = Lento, 1 = Normal, 2 = Rápido</small>
                            </div>
                            <div class="mb-3">
                                <label for="voz_tono" class="form-label">
                                    Tono de la Voz: <span id="tonoValor"><?= $config['voz_tono'] ?></span>
                                </label>
                                <input type="range" class="form-range" id="voz_tono" 
                                       name="voz_tono" value="<?= $config['voz_tono'] ?>" 
                                       min="0" max="2" step="0.1">
                                <small class="text-muted">0 = Grave, 1 = Normal, 2 = Agudo</small>
                            </div>
                            <button type="button" class="btn btn-outline-secondary" onclick="probarVoz()">
                                <i class="bi bi-play-fill me-1"></i>Probar Voz
                            </button>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>URLs del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label text-muted small">Pantalla Pública</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" 
                                           value="<?= $_SERVER['HTTP_HOST'] . BASE_URL ?>/pantalla/" readonly>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" 
                                            onclick="copiarUrl(this.previousElementSibling)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small">Emisor de Tickets</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" 
                                           value="<?= $_SERVER['HTTP_HOST'] . BASE_URL ?>/emisor/" readonly>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" 
                                            onclick="copiarUrl(this.previousElementSibling)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted small">Panel Girador</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" 
                                           value="<?= $_SERVER['HTTP_HOST'] . BASE_URL ?>/girador/" readonly>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" 
                                            onclick="copiarUrl(this.previousElementSibling)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-1"></i>Guardar Configuración
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar valores de sliders
        document.getElementById('voz_velocidad').addEventListener('input', function() {
            document.getElementById('velocidadValor').textContent = this.value;
        });
        
        document.getElementById('voz_tono').addEventListener('input', function() {
            document.getElementById('tonoValor').textContent = this.value;
        });
        
        // Probar voz
        function probarVoz() {
            const texto = "Número PC-001, Permisos de Circulación, Módulo 1, lo atenderá Carla Orellana";
            const velocidad = parseFloat(document.getElementById('voz_velocidad').value);
            const tono = parseFloat(document.getElementById('voz_tono').value);
            
            const utterance = new SpeechSynthesisUtterance(texto);
            utterance.lang = 'es-ES';
            utterance.rate = velocidad;
            utterance.pitch = tono;
            
            speechSynthesis.speak(utterance);
        }
        
        // Copiar URL
        function copiarUrl(input) {
            input.select();
            document.execCommand('copy');
            alert('URL copiada al portapapeles');
        }
        
        // Resetear numeración a A1
        function resetearNumeracion() {
            if (confirm('¿Está seguro de resetear la numeración global a A1? Esta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="resetear_numeracion" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Resetear numeración manualmente
        function resetearManual() {
            const letra = document.getElementById('letra_reseteo').value;
            const numero = document.getElementById('numero_reseteo').value;
            const proximoNumero = letra + (parseInt(numero) + 1);
            
            if (confirm(`¿Confirma configurar la numeración?\n\nLetra actual: ${letra}\nNúmero actual: ${numero}\nPróximo número a llamar: ${proximoNumero}`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="resetear_manual" value="1">
                    <input type="hidden" name="letra_reseteo" value="${letra}">
                    <input type="hidden" name="numero_reseteo" value="${numero}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Actualizar preview del próximo número
        function actualizarProximoNumero() {
            const letra = document.getElementById('letra_reseteo').value;
            const numero = parseInt(document.getElementById('numero_reseteo').value) || 0;
            const letraMaxima = document.getElementById('letra_maxima').value;
            const numeroMaximo = parseInt(document.getElementById('numero_maximo').value) || 99;
            
            let proximoNumero = numero + 1;
            let proximaLetra = letra;
            
            // Calcular próximo número según configuración
            if (proximoNumero > numeroMaximo) {
                proximoNumero = 1;
                const proximoOrd = letra.charCodeAt(0) + 1;
                proximaLetra = String.fromCharCode(proximoOrd);
                
                if (proximoOrd > letraMaxima.charCodeAt(0)) {
                    proximaLetra = 'A';
                }
            }
            
            document.getElementById('proximoNumero').textContent = proximaLetra + proximoNumero;
        }
        
        // Eventos para actualizar preview
        document.getElementById('letra_reseteo').addEventListener('change', actualizarProximoNumero);
        document.getElementById('numero_reseteo').addEventListener('input', actualizarProximoNumero);
        document.getElementById('letra_maxima').addEventListener('change', actualizarProximoNumero);
        document.getElementById('numero_maximo').addEventListener('input', actualizarProximoNumero);
    </script>
</body>
</html>
