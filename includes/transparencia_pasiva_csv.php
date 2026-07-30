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

function tp_esta_habilitada(mysqli $conexion): bool
{
    $stmt = $conexion->prepare(
        "SELECT valor FROM configuracion WHERE clave = 'transparencia_pasiva_activa' LIMIT 1"
    );
    if ($stmt === false) {
        return true;
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return true;
    }

    $resultado = $stmt->get_result();
    $fila = $resultado ? $resultado->fetch_assoc() : null;
    $stmt->close();

    // Activa por defecto para mantener el comportamiento previo.
    return $fila === null || (int)$fila['valor'] === 1;
}

function tp_validar_estructura_csv(string $ruta, array $columnasRequeridas): void
{
    $archivo = @fopen($ruta, 'rb');
    if ($archivo === false) {
        throw new RuntimeException('No se pudo abrir el CSV para validarlo.');
    }

    try {
        $encabezados = fgetcsv($archivo, 0, ';');
        if ($encabezados === false) {
            throw new RuntimeException('El CSV no contiene encabezados.');
        }

        if (isset($encabezados[0])) {
            $encabezados[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$encabezados[0]);
        }
        $encabezados = array_map('trim', $encabezados);

        $faltantes = array_values(array_diff($columnasRequeridas, $encabezados));
        if ($faltantes !== []) {
            throw new RuntimeException(
                'Faltan columnas requeridas: ' . implode(', ', $faltantes) . '.'
            );
        }

        $primeraFila = fgetcsv($archivo, 0, ';');
        if ($primeraFila === false) {
            throw new RuntimeException('El CSV no contiene solicitudes.');
        }
    } finally {
        fclose($archivo);
    }
}

function tp_validar_csv_subido(array $archivo, array $columnasRequeridas): void
{
    $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('La carga del CSV no se completó correctamente.');
    }

    $rutaTemporal = (string)($archivo['tmp_name'] ?? '');
    if ($rutaTemporal === '' || !is_uploaded_file($rutaTemporal)) {
        throw new RuntimeException('El archivo recibido no es una carga válida.');
    }

    $nombreOriginal = (string)($archivo['name'] ?? '');
    if (strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) !== 'csv') {
        throw new RuntimeException('Solo se permiten archivos con extensión .csv.');
    }

    $tamano = (int)($archivo['size'] ?? 0);
    if ($tamano < 1 || $tamano > 20 * 1024 * 1024) {
        throw new RuntimeException('El CSV debe pesar entre 1 byte y 20 MB.');
    }

    if (function_exists('mb_check_encoding')) {
        $contenido = @file_get_contents($rutaTemporal);
        if ($contenido === false || !mb_check_encoding($contenido, 'UTF-8')) {
            throw new RuntimeException('El CSV debe estar codificado en UTF-8.');
        }
    }

    tp_validar_estructura_csv($rutaTemporal, $columnasRequeridas);
}

function tp_reemplazar_csv_subido(array $archivo, string $rutaDestino): void
{
    $directorio = dirname($rutaDestino);
    if (!is_dir($directorio) || !is_writable($directorio)) {
        throw new RuntimeException('La carpeta de Transparencia Pasiva no permite actualizar archivos.');
    }

    $rutaTemporalDestino = $rutaDestino . '.upload-' . bin2hex(random_bytes(6));
    if (!move_uploaded_file((string)$archivo['tmp_name'], $rutaTemporalDestino)) {
        throw new RuntimeException('No fue posible guardar temporalmente el CSV.');
    }

    $rutaRespaldo = null;
    if (is_file($rutaDestino)) {
        $rutaRespaldo = $rutaDestino . '.backup-' . bin2hex(random_bytes(6));
        if (!@rename($rutaDestino, $rutaRespaldo)) {
            @unlink($rutaTemporalDestino);
            throw new RuntimeException('No fue posible preparar el reemplazo del CSV actual.');
        }
    }

    if (!@rename($rutaTemporalDestino, $rutaDestino)) {
        @unlink($rutaTemporalDestino);
        if ($rutaRespaldo !== null) {
            @rename($rutaRespaldo, $rutaDestino);
        }
        throw new RuntimeException('No fue posible reemplazar el CSV actual.');
    }

    if ($rutaRespaldo !== null) {
        @unlink($rutaRespaldo);
    }
    @chmod($rutaDestino, 0640);
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
    $solicitudes = tp_obtener_solicitudes($directorioCsv);
    $pendientes = 0;

    foreach ($solicitudes as $solicitud) {
        if (
            trim((string)$solicitud['estado']) === 'En Proceso'
            && trim((string)$solicitud['estado_actual']) === 'SOLICITUD INTERNA'
        ) {
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
