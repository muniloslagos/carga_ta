<?php

/**
 * Funciones de lectura para la sección de Transparencia Pasiva.
 *
 * Los datos se consultan directamente desde los archivos CSV y no se
 * almacenan en la base de datos.
 */

function tp_leer_csv_asociativo(string $ruta): array
{
    if (!is_file($ruta) || !is_readable($ruta)) {
        throw new RuntimeException('No se pudo acceder al archivo: ' . basename($ruta));
    }

    $archivo = @fopen($ruta, 'rb');
    if ($archivo === false) {
        throw new RuntimeException('No se pudo abrir el archivo: ' . basename($ruta));
    }

    try {
        $encabezados = fgetcsv($archivo, 0, ';');
        if ($encabezados === false) {
            throw new RuntimeException('El archivo no contiene encabezados: ' . basename($ruta));
        }

        if (isset($encabezados[0])) {
            $encabezados[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$encabezados[0]);
        }

        $encabezados = array_map('trim', $encabezados);
        $filas = [];

        while (($valores = fgetcsv($archivo, 0, ';')) !== false) {
            if ($valores === [null] || $valores === []) {
                continue;
            }

            if (count($valores) < count($encabezados)) {
                $valores = array_pad($valores, count($encabezados), '');
            } elseif (count($valores) > count($encabezados)) {
                $valores = array_slice($valores, 0, count($encabezados));
            }

            $fila = array_combine($encabezados, $valores);
            if ($fila !== false) {
                $filas[] = $fila;
            }
        }

        return $filas;
    } finally {
        fclose($archivo);
    }
}

function tp_obtener_solicitudes(string $directorioCsv): array
{
    $rutaInternas = $directorioCsv . DIRECTORY_SEPARATOR . 'Listado Solicitudes internas.csv';
    $rutaGeneral = $directorioCsv . DIRECTORY_SEPARATOR . 'Listado Reporte general de solicitudes.csv';

    $solicitudesInternas = tp_leer_csv_asociativo($rutaInternas);
    $reporteGeneral = tp_leer_csv_asociativo($rutaGeneral);

    $reportePorCodigo = [];
    foreach ($reporteGeneral as $solicitudGeneral) {
        $codigo = trim((string)($solicitudGeneral['Código'] ?? ''));
        if ($codigo !== '') {
            $reportePorCodigo[$codigo] = $solicitudGeneral;
        }
    }

    $solicitudes = [];
    foreach ($solicitudesInternas as $solicitudInterna) {
        $codigo = trim((string)($solicitudInterna['Código'] ?? ''));
        if ($codigo === '') {
            continue;
        }

        $solicitudGeneral = $reportePorCodigo[$codigo] ?? [];

        // Se conserva una fila por cada asignación/unidad del CSV interno.
        $solicitudes[] = [
            'codigo' => $codigo,
            'informacion' => (string)($solicitudInterna['Información solicitada'] ?? ''),
            'estado' => (string)($solicitudInterna['Estado'] ?? ''),
            'estado_actual' => (string)($solicitudGeneral['Estado actual'] ?? ''),
            'unidad' => (string)($solicitudInterna['Unidad'] ?? ''),
            'fecha_ingreso' => (string)($solicitudGeneral['Fecha ingreso'] ?? ''),
            'fecha_caducidad' => (string)($solicitudGeneral['Fecha caducidad'] ?? ''),
            'prorroga' => (string)($solicitudGeneral['Prórroga'] ?? ''),
        ];
    }

    return $solicitudes;
}

function tp_contar_solicitudes_en_proceso(string $directorioCsv): int
{
    $rutaInternas = $directorioCsv . DIRECTORY_SEPARATOR . 'Listado Solicitudes internas.csv';
    $solicitudesInternas = tp_leer_csv_asociativo($rutaInternas);
    $pendientes = 0;

    foreach ($solicitudesInternas as $solicitud) {
        if (trim((string)($solicitud['Estado'] ?? '')) === 'En Proceso') {
            $pendientes++;
        }
    }

    return $pendientes;
}

function tp_resumir_texto(string $texto, int $limite = 30): string
{
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($texto, 'UTF-8') > $limite
            ? mb_substr($texto, 0, $limite, 'UTF-8') . '…'
            : $texto;
    }

    return strlen($texto) > $limite
        ? substr($texto, 0, $limite) . '...'
        : $texto;
}
