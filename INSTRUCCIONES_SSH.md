# Instrucciones para Instalación en Servidor de Producción

## 📋 Pasos para conectarse y ejecutar la instalación

### 1. Conectarse al servidor por SSH

```bash
ssh appmuniloslagos@ip_del_servidor
```

O si tiene un dominio configurado:
```bash
ssh appmuniloslagos@app.muniloslagos.cl
```

### 2. Una vez conectado, ejecute estos comandos:

```bash
# Ir al directorio de la aplicación
cd /var/www/html/app.muniloslagos.cl/carga_ta

# Dar permisos de ejecución al script
chmod +x install_smtp_production.sh

# Ejecutar el script de instalación
./install_smtp_production.sh
```

---

## 🔧 Instalación Manual (Si el script falla)

### Opción A: Con Composer

```bash
cd /var/www/html/app.muniloslagos.cl/carga_ta

# Instalar PHPMailer
composer require phpmailer/phpmailer

# Crear la tabla en la base de datos
php ejecutar_migracion_smtp.php
```

### Opción B: Sin Composer (Descarga manual)

```bash
cd /var/www/html/app.muniloslagos.cl/carga_ta

# Crear directorio vendor
mkdir -p vendor/phpmailer
cd vendor/phpmailer

# Descargar PHPMailer
wget https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.tar.gz

# Extraer
tar -xzf v6.9.1.tar.gz
mv PHPMailer-6.9.1 phpmailer
rm v6.9.1.tar.gz

# Volver al directorio principal
cd ../..

# Ajustar permisos
chmod -R 755 vendor/
chown -R www-data:www-data vendor/

# Crear la tabla en la base de datos
php ejecutar_migracion_smtp.php
```

### Opción C: Usar MySQL directamente

Si el script PHP de migración falla:

```bash
cd /var/www/html/app.muniloslagos.cl/carga_ta

# Conectarse a MySQL
mysql -u root -p

# Dentro de MySQL, seleccionar la base de datos
USE cumplimiento_db;

# Copiar y pegar el contenido del archivo sql/migration_smtp_config.sql
```

---

## 📧 Configuración SMTP Posterior

Una vez instalado, acceda a:

```
https://app.muniloslagos.cl/carga_ta/admin/smtp/
```

### Configuración recomendada para Gmail:

- **Servidor SMTP:** smtp.gmail.com
- **Puerto:** 587
- **Encriptación:** TLS
- **Usuario:** correo completo (ejemplo@gmail.com)
- **Contraseña:** Use una [App Password](https://myaccount.google.com/apppasswords)

### Configuración para Office 365:

- **Servidor SMTP:** smtp.office365.com
- **Puerto:** 587
- **Encriptación:** TLS
- **Usuario:** correo completo

### Configuración para servidor propio:

Consulte con su proveedor de correo o administrador del servidor.

---

## 🧪 Probar la configuración

1. En la página de configuración SMTP, active el sistema
2. Ingrese un correo de destino en el formulario de prueba
3. Haga clic en "Enviar Prueba"
4. Verifique que el correo llegue correctamente

---

## ⚠️ Solución de Problemas

### Error: "No se puede conectar al servidor SMTP"
- Verifique que el servidor SMTP y el puerto sean correctos
- Asegúrese de que el firewall permita conexiones salientes al puerto 587 o 465
- Verifique que las credenciales sean correctas

### Error: "Authentication failed"
- Para Gmail, use una App Password en lugar de la contraseña normal
- Verifique que el usuario sea el correo completo
- Asegúrese de que la cuenta tenga habilitado SMTP

### Error: PHPMailer no se encuentra
- Verifique que la carpeta vendor/phpmailer/phpmailer exista
- Verifique los permisos de la carpeta vendor

---

## 📁 Archivos creados

- `admin/smtp/index.php` - Interfaz de configuración SMTP
- `classes/EmailSender.php` - Clase para envío de correos
- `sql/migration_smtp_config.sql` - Script de creación de tabla
- `ejecutar_migracion_smtp.php` - Script de migración
- `install_smtp_production.sh` - Script de instalación automática

---

## 🔐 Seguridad

- Las contraseñas SMTP se almacenan en la base de datos
- Solo usuarios con perfil "Administrativo" pueden acceder a la configuración
- Se recomienda usar contraseñas de aplicación en lugar de contraseñas principales
- Mantenga el archivo config/config.php fuera del directorio web público
