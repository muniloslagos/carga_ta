#!/bin/bash
# Script de diagnóstico de capacidades de correo electrónico del servidor
# Municipalidad de Los Lagos - Sistema de Transparencia

echo "=========================================="
echo "DIAGNÓSTICO DE CORREO ELECTRÓNICO"
echo "=========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Verificar función mail() de PHP
echo -e "${BLUE}[1] Verificando función mail() de PHP${NC}"
echo "-----------------------------------"
php -r "if (function_exists('mail')) { echo '✓ La función mail() está disponible\n'; } else { echo '✗ La función mail() NO está disponible\n'; }"
echo ""

# 2. Verificar sendmail
echo -e "${BLUE}[2] Verificando sendmail${NC}"
echo "-----------------------------------"
if command -v sendmail &> /dev/null; then
    echo -e "${GREEN}✓${NC} sendmail está instalado"
    sendmail -V 2>&1 | head -1
    which sendmail
else
    echo -e "${RED}✗${NC} sendmail NO está instalado"
fi
echo ""

# 3. Verificar postfix
echo -e "${BLUE}[3] Verificando postfix${NC}"
echo "-----------------------------------"
if command -v postfix &> /dev/null; then
    echo -e "${GREEN}✓${NC} postfix está instalado"
    postconf mail_version 2>/dev/null
    systemctl status postfix --no-pager 2>/dev/null | grep "Active:"
else
    echo -e "${RED}✗${NC} postfix NO está instalado"
fi
echo ""

# 4. Verificar exim
echo -e "${BLUE}[4] Verificando exim${NC}"
echo "-----------------------------------"
if command -v exim &> /dev/null; then
    echo -e "${GREEN}✓${NC} exim está instalado"
    exim -bV | head -1
else
    echo -e "${RED}✗${NC} exim NO está instalado"
fi
echo ""

# 5. Verificar Composer
echo -e "${BLUE}[5] Verificando Composer${NC}"
echo "-----------------------------------"
if command -v composer &> /dev/null; then
    echo -e "${GREEN}✓${NC} Composer está instalado"
    composer --version
else
    echo -e "${RED}✗${NC} Composer NO está instalado"
fi
echo ""

# 6. Verificar extensiones PHP necesarias
echo -e "${BLUE}[6] Verificando extensiones PHP${NC}"
echo "-----------------------------------"
php -m | grep -E "openssl|sockets|mbstring|iconv" | while read ext; do
    echo -e "${GREEN}✓${NC} $ext"
done
echo ""

# 7. Verificar puertos SMTP abiertos
echo -e "${BLUE}[7] Verificando conectividad a puertos SMTP${NC}"
echo "-----------------------------------"
echo "Puerto 25 (SMTP):"
nc -zv -w3 smtp.gmail.com 25 2>&1 | tail -1

echo "Puerto 587 (SMTP TLS):"
nc -zv -w3 smtp.gmail.com 587 2>&1 | tail -1

echo "Puerto 465 (SMTP SSL):"
nc -zv -w3 smtp.gmail.com 465 2>&1 | tail -1
echo ""

# 8. Verificar configuración PHP sendmail_path
echo -e "${BLUE}[8] Configuración PHP para correos${NC}"
echo "-----------------------------------"
echo -n "sendmail_path: "
php -r "echo ini_get('sendmail_path');" && echo ""
echo -n "SMTP: "
php -r "echo ini_get('SMTP');" && echo ""
echo -n "smtp_port: "
php -r "echo ini_get('smtp_port');" && echo ""
echo ""

# 9. Verificar si PHPMailer ya está instalado
echo -e "${BLUE}[9] Verificando PHPMailer${NC}"
echo "-----------------------------------"
if [ -d "vendor/phpmailer/phpmailer" ]; then
    echo -e "${GREEN}✓${NC} PHPMailer está instalado en vendor/phpmailer/phpmailer"
    if [ -f "vendor/phpmailer/phpmailer/VERSION" ]; then
        echo -n "Versión: "
        cat vendor/phpmailer/phpmailer/VERSION
    fi
elif [ -f "composer.json" ]; then
    echo -e "${YELLOW}!${NC} composer.json existe, pero PHPMailer no está instalado"
    grep "phpmailer" composer.json
else
    echo -e "${RED}✗${NC} PHPMailer NO está instalado"
fi
echo ""

# 10. Resumen y recomendaciones
echo "=========================================="
echo -e "${BLUE}RESUMEN Y RECOMENDACIONES${NC}"
echo "=========================================="
echo ""

HAS_SENDMAIL=false
HAS_POSTFIX=false
HAS_COMPOSER=false
HAS_PHPMAILER=false

command -v sendmail &> /dev/null && HAS_SENDMAIL=true
command -v postfix &> /dev/null && HAS_POSTFIX=true
command -v composer &> /dev/null && HAS_COMPOSER=true
[ -d "vendor/phpmailer/phpmailer" ] && HAS_PHPMAILER=true

if [ "$HAS_PHPMAILER" = true ]; then
    echo -e "${GREEN}✓ RECOMENDACIÓN:${NC} Use PHPMailer (ya instalado)"
    echo "  Clase EmailSender.php está lista para usarlo"
elif [ "$HAS_COMPOSER" = true ]; then
    echo -e "${YELLOW}→ RECOMENDACIÓN:${NC} Instale PHPMailer con Composer"
    echo "  Comando: composer require phpmailer/phpmailer"
elif [ "$HAS_SENDMAIL" = true ] || [ "$HAS_POSTFIX" = true ]; then
    echo -e "${YELLOW}→ OPCIÓN DISPONIBLE:${NC} Use la función mail() de PHP"
    echo "  El servidor tiene un MTA instalado (sendmail/postfix)"
    echo "  Puede funcionar, pero PHPMailer es más confiable"
else
    echo -e "${RED}! ATENCIÓN:${NC} No se detectó ningún sistema de correo"
    echo "  Necesita instalar PHPMailer o configurar un MTA"
fi

echo ""
echo "=========================================="
