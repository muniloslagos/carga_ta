#!/bin/bash
# Script de instalación del sistema SMTP para el servidor de producción
# Municipalidad de Los Lagos - Sistema de Transparencia Activa

echo "=========================================="
echo "Instalación del Sistema de Correos SMTP"
echo "=========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Sin color

# Paso 1: Verificar directorio
echo -e "${YELLOW}[1/5]${NC} Verificando directorio de la aplicación..."
cd /var/www/html/app.muniloslagos.cl/carga_ta || { 
    echo -e "${RED}Error: No se pudo acceder al directorio de la aplicación${NC}"
    exit 1
}
echo -e "${GREEN}✓${NC} Directorio verificado"
echo ""

# Paso 2: Crear la tabla de configuración SMTP
echo -e "${YELLOW}[2/5]${NC} Creando tabla de configuración SMTP en la base de datos..."
php ejecutar_migracion_smtp.php

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Tabla creada exitosamente"
else
    echo -e "${RED}✗${NC} Error al crear la tabla. Intentando con MySQL directo..."
    mysql -u root -p cumplimiento_db < sql/migration_smtp_config.sql
fi
echo ""

# Paso 3: Verificar si Composer está instalado
echo -e "${YELLOW}[3/5]${NC} Verificando instalación de Composer..."
if command -v composer &> /dev/null; then
    echo -e "${GREEN}✓${NC} Composer está instalado"
    COMPOSER_EXISTS=true
else
    echo -e "${YELLOW}!${NC} Composer no está instalado globalmente"
    COMPOSER_EXISTS=false
fi
echo ""

# Paso 4: Instalar PHPMailer
echo -e "${YELLOW}[4/5]${NC} Instalando PHPMailer..."

if [ "$COMPOSER_EXISTS" = true ]; then
    # Opción 1: Con Composer
    echo "Instalando con Composer..."
    composer require phpmailer/phpmailer
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} PHPMailer instalado con Composer"
    else
        echo -e "${RED}✗${NC} Error al instalar con Composer"
    fi
else
    # Opción 2: Descarga manual
    echo "Descargando PHPMailer manualmente..."
    mkdir -p vendor/phpmailer
    cd vendor/phpmailer
    
    wget https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.tar.gz
    
    if [ $? -eq 0 ]; then
        tar -xzf v6.9.1.tar.gz
        mv PHPMailer-6.9.1 phpmailer
        rm v6.9.1.tar.gz
        echo -e "${GREEN}✓${NC} PHPMailer descargado manualmente"
    else
        echo -e "${RED}✗${NC} Error al descargar PHPMailer"
        echo "Puede descargarlo manualmente desde: https://github.com/PHPMailer/PHPMailer"
    fi
    
    cd ../..
fi
echo ""

# Paso 5: Verificar permisos
echo -e "${YELLOW}[5/5]${NC} Ajustando permisos..."
chmod -R 755 vendor/
chown -R www-data:www-data vendor/
echo -e "${GREEN}✓${NC} Permisos ajustados"
echo ""

# Resumen final
echo "=========================================="
echo -e "${GREEN}INSTALACIÓN COMPLETADA${NC}"
echo "=========================================="
echo ""
echo "Próximos pasos:"
echo ""
echo "1. Acceda a la configuración SMTP:"
echo "   https://app.muniloslagos.cl/carga_ta/admin/smtp/"
echo ""
echo "2. Configure los parámetros SMTP:"
echo "   - Servidor SMTP"
echo "   - Puerto (587 para TLS, 465 para SSL)"
echo "   - Usuario y contraseña"
echo "   - Correo del remitente"
echo ""
echo "3. Pruebe la conexión enviando un correo de prueba"
echo ""
echo "=========================================="
