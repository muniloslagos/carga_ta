<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Cambiar en producción
define('DB_PASS', ''); // Configurar contraseña en producción
define('DB_NAME', 'cumplimiento_db');

// Configuración general
define('SITE_URL', 'http://localhost/cumplimiento/'); // Cambiar en producción a https://app.muniloslagos.cl/carga_ta/
define('SITE_NAME', 'Administración de Carga Unificada y Control de Transparencia');

// Timezone - IMPORTANTE: Usar America/Santiago para Chile
date_default_timezone_set('America/Santiago');

// Errores (desactivar en producción)
ini_set('display_errors', 1); // Cambiar a 0 en producción
error_reporting(E_ALL);

// Perfiles de usuario
define('PROFILE_ADMIN', 'administrativo');
define('PROFILE_DIRECTOR', 'director_revisor');
define('PROFILE_CARGADOR', 'cargador_informacion');
define('PROFILE_PUBLICADOR', 'publicador');

$PROFILES = [
    'administrativo' => 'Administrador',
    'director_revisor' => 'Director Revisor',
    'cargador_informacion' => 'Cargador de Información',
    'publicador' => 'Publicador'
];

// Periodicidades
$PERIODICIDADES = [
    'mensual' => 'Mensual',
    'trimestral' => 'Trimestral',
    'semestral' => 'Semestral',
    'anual' => 'Anual',
    'ocurrencia' => 'Ocurrencia'
];

// Meses
$MESES = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
];

// Trimestres
$TRIMESTRES = [
    'marzo' => 'Trimestre 1 (Marzo)',
    'junio' => 'Trimestre 2 (Junio)',
    'septiembre' => 'Trimestre 3 (Septiembre)',
    'diciembre' => 'Trimestre 4 (Diciembre)'
];
