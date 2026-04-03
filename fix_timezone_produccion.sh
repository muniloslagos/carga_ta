#!/bin/bash
# Script para actualizar el timezone en el servidor de producción

echo "=== Actualización de Timezone en Producción ==="
echo ""
echo "Este script actualizará America/Bogota -> America/Santiago en config.php"
echo ""
read -p "¿Continuar? (s/n): " confirm

if [ "$confirm" != "s" ]; then
    echo "Cancelado."
    exit 0
fi

CONFIG_FILE="/home/transparencia/public_html/carga_ta/config/config.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "ERROR: No se encuentra $CONFIG_FILE"
    exit 1
fi

echo ""
echo "Creando backup..."
cp "$CONFIG_FILE" "$CONFIG_FILE.backup.$(date +%Y%m%d_%H%M%S)"

echo "Aplicando cambio..."
sed -i "s/date_default_timezone_set('America\/Bogota')/date_default_timezone_set('America\/Santiago')/g" "$CONFIG_FILE"

echo ""
echo "Verificando cambio..."
grep "date_default_timezone_set" "$CONFIG_FILE"

echo ""
echo "✅ Completado. El timezone ahora es America/Santiago"
echo ""
echo "Verifica en el footer del sistema que la hora sea correcta."
