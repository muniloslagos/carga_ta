# ============================================================================
# Script PowerShell para eliminar archivos físicos de documentos
# ============================================================================
# Ejecutar en el servidor Windows DESPUÉS de ejecutar reset_sistema.sql
# ============================================================================

$PROYECTO_DIR = "C:\xampp\htdocs\cumplimiento"  # Ajustar según la instalación
$UPLOADS_DIR = Join-Path $PROYECTO_DIR "uploads"

Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host "ELIMINACIÓN DE ARCHIVOS FÍSICOS DE DOCUMENTOS" -ForegroundColor Cyan
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Directorio: $UPLOADS_DIR"
Write-Host ""

# Verificar que existe el directorio
if (-not (Test-Path $UPLOADS_DIR)) {
    Write-Host "ERROR: El directorio $UPLOADS_DIR no existe" -ForegroundColor Red
    exit 1
}

# Mostrar cuántos archivos hay
$archivos = Get-ChildItem -Path $UPLOADS_DIR -Recurse -File
$numArchivos = $archivos.Count
Write-Host "Archivos encontrados: $numArchivos" -ForegroundColor Yellow
Write-Host ""

# Pedir confirmación
$confirmacion = Read-Host "¿Desea eliminar TODOS los archivos en $UPLOADS_DIR? (escriba 'SI' para confirmar)"

if ($confirmacion -ne "SI") {
    Write-Host "Operación cancelada." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Eliminando archivos..." -ForegroundColor Yellow

# Eliminar todos los archivos (mantener estructura de carpetas)
Get-ChildItem -Path $UPLOADS_DIR -Recurse -File | Remove-Item -Force

Write-Host "Archivos eliminados: $numArchivos" -ForegroundColor Green
Write-Host ""

# Verificar que se eliminaron
$archivosRest = Get-ChildItem -Path $UPLOADS_DIR -Recurse -File
$numRest = $archivosRest.Count
Write-Host "Archivos restantes: $numRest"
Write-Host ""

if ($numRest -eq 0) {
    Write-Host "✓ Eliminación completada exitosamente" -ForegroundColor Green
} else {
    Write-Host "⚠ Algunos archivos no se pudieron eliminar" -ForegroundColor Yellow
    Write-Host "Archivos restantes:" -ForegroundColor Yellow
    $archivosRest | ForEach-Object { Write-Host "  - $($_.FullName)" }
}

Write-Host ""
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host "Proceso completado" -ForegroundColor Cyan
Write-Host "============================================================================" -ForegroundColor Cyan
