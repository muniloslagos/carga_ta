<?php

/**
 * Configuracion institucional almacenada en archivo para no depender de la BD.
 */
function municipalidad_config($recargar = false) {
    static $configuracion = null;

    if ($configuracion !== null && !$recargar) {
        return $configuracion;
    }

    $configuracion = [
        'nombre' => 'Municipalidad de Los Lagos',
        'logo' => 'https://muniloslagos.cl/wp-content/uploads/2025/02/logo2025.png',
        'correo_contacto' => 'transparencia@muniloslagos.cl',
    ];

    $ruta = dirname(__DIR__) . '/config/municipalidad.json';
    if (is_file($ruta) && is_readable($ruta)) {
        $contenido = file_get_contents($ruta);
        $guardada = json_decode((string)$contenido, true);
        if (is_array($guardada)) {
            $nombre = trim((string)($guardada['nombre'] ?? ''));
            $logo = trim((string)($guardada['logo'] ?? ''));
            $correoContacto = trim((string)($guardada['correo_contacto'] ?? ''));
            if ($nombre !== '') {
                $configuracion['nombre'] = $nombre;
            }
            if ($logo !== '') {
                $configuracion['logo'] = $logo;
            }
            if (filter_var($correoContacto, FILTER_VALIDATE_EMAIL)) {
                $configuracion['correo_contacto'] = $correoContacto;
            }
        }
    }

    return $configuracion;
}

function municipalidad_nombre() {
    return municipalidad_config()['nombre'];
}

function municipalidad_correo_contacto() {
    return municipalidad_config()['correo_contacto'];
}

function municipalidad_logo_url() {
    $logo = municipalidad_config()['logo'];
    if (preg_match('#^https?://#i', $logo)) {
        return $logo;
    }

    return rtrim(SITE_URL, '/') . '/' . ltrim($logo, '/');
}

function municipalidad_guardar($nombre, $logo = null, $correoContacto = null) {
    $nombre = trim((string)$nombre);
    if ($nombre === '') {
        throw new InvalidArgumentException('El nombre de la municipalidad es obligatorio.');
    }

    $actual = municipalidad_config();
    $correoContacto = trim((string)$correoContacto);
    if (!filter_var($correoContacto, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('El correo institucional no es válido.');
    }

    $datos = [
        'nombre' => $nombre,
        'logo' => $logo !== null && trim((string)$logo) !== '' ? trim((string)$logo) : $actual['logo'],
        'correo_contacto' => $correoContacto,
    ];

    $ruta = dirname(__DIR__) . '/config/municipalidad.json';
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($ruta, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar config/municipalidad.json. Revise los permisos de escritura.');
    }

    return municipalidad_config(true);
}
