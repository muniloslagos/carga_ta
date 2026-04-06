#!/bin/bash
# ============================================================================
# Script para eliminar archivos físicos de documentos
# ============================================================================
# Ejecutar en el servidor de producción DESPUÉS de ejecutar reset_sistema.sql
# ============================================================================

PROYECTO_DIR="/var/www/html/carga_ta"  # Ajustar según la instalación
UPLOADS_DIR="$PROYECTO_DIR/uploads"

echo "============================================================================"
echo "ELIMINACIÓN DE ARCHIVOS FÍSICOS DE DOCUMENTOS"
echo "============================================================================"
echo ""
echo "Directorio: $UPLOADS_DIR"
echo ""

# Verificar que existe el directorio
if [ ! -d "$UPLOADS_DIR" ]; then
    echo "ERROR: El directorio $UPLOADS_DIR no existe"
    exit 1
fi

# Mostrar cuántos archivos hay
NUM_ARCHIVOS=$(find "$UPLOADS_DIR" -type f | wc -l)
echo "Archivos encontrados: $NUM_ARCHIVOS"
echo ""

# Pedir confirmación
read -p "¿Desea eliminar TODOS los archivos en $UPLOADS_DIR? (escriba 'SI' para confirmar): " confirmacion

if [ "$confirmacion" != "SI" ]; then
    echo "Operación cancelada."
    exit 0
fi

echo ""
echo "Eliminando archivos..."

# Eliminar todos los archivos (mantener estructura de carpetas)
find "$UPLOADS_DIR" -type f -delete

echo "Archivos eliminados: $NUM_ARCHIVOS"
echo ""

# Verificar que se eliminaron
NUM_REST=$(find "$UPLOADS_DIR" -type f | wc -l)
echo "Archivos restantes: $NUM_REST"
echo ""

if [ $NUM_REST -eq 0 ]; then
    echo "✓ Eliminación completada exitosamente"
else
    echo "⚠ Algunos archivos no se pudieron eliminar"
fi

# Asegurar permisos correctos
echo ""
echo "Configurando permisos..."
chown -R www-data:www-data "$UPLOADS_DIR"
chmod -R 755 "$UPLOADS_DIR"

echo ""
echo "============================================================================"
echo "Proceso completado"
echo "============================================================================"
