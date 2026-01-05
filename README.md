# Administración de Carga Unificada y Control de Transparencia

## Descripción
Sistema de gestión integral para el control de transparencia activa en instituciones públicas. Permite la administración de usuarios, items de transparencia, documentos y su revisión.

## Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- XAMPP o servidor web compatible
- Bootstrap 5.3
- jQuery 3.6

## Instalación

### 1. Descargar/Clonar el proyecto
```bash
# El proyecto debe estar en: C:\xampp\htdocs\cumplimiento\
```

### 2. Crear la base de datos
1. Abra su navegador y vaya a: `http://localhost/cumplimiento/`
2. Haga clic en "Configurar BD"
3. La base de datos `cumplimiento_db` será creada automáticamente

### 3. Acceder al sistema
- **URL:** `http://localhost/cumplimiento/`
- **Email (admin):** admin@cumplimiento.local
- **Contraseña (admin):** admin123

## Estructura del Proyecto

```
cumplimiento/
├── admin/                    # Panel administrativo
│   ├── index.php           # Dashboard admin
│   ├── usuarios/           # Gestión de usuarios
│   ├── direcciones/        # Gestión de direcciones
│   ├── items/              # Gestión de items
│   └── documentos/         # Revisión de documentos
├── usuario/                # Panel de usuario
│   ├── dashboard.php       # Dashboard usuario
│   └── enviar_documento.php # Cargar documentos
├── config/                 # Configuración
│   ├── config.php         # Configuración general
│   └── Database.php       # Clase de conexión
├── classes/               # Clases del sistema
│   ├── Usuario.php        # Modelo de usuario
│   ├── Direccion.php      # Modelo de dirección
│   ├── Item.php           # Modelo de item
│   └── Documento.php      # Modelo de documento
├── includes/              # Includes comunes
│   ├── header.php        # Encabezado
│   └── footer.php        # Pie de página
├── css/                  # Estilos
│   └── style.css
├── js/                   # Scripts
│   └── main.js
├── uploads/              # Almacenamiento de documentos
├── login.php            # Página de login
├── logout.php           # Cierre de sesión
├── setup.php            # Script de instalación
└── index.php            # Página de inicio
```

## Base de Datos

### Tablas principales

#### usuarios
- id
- nombre
- email
- password
- perfil (administrativo, director_revisor, cargador_informacion, publicador)
- direccion_id
- activo
- fecha_creacion
- fecha_actualizacion

#### direcciones
- id
- nombre
- descripcion
- activa
- fecha_creacion
- fecha_actualizacion

#### items_transparencia
- id
- numeracion (1, 1.1, 1.2, 2, etc)
- nombre
- descripcion
- direccion_id
- periodicidad (mensual, trimestral, semestral, anual, ocurrencia)
- activo
- fecha_creacion
- fecha_actualizacion

#### item_usuarios
- id
- item_id
- usuario_id
- fecha_asignacion

#### documentos
- id
- item_id
- usuario_id
- titulo
- descripcion
- archivo
- estado (pendiente, aprobado, rechazado)
- comentarios_revision
- revisado_por
- fecha_subida
- fecha_revision
- fecha_actualizacion

#### logs
- id
- usuario_id
- accion
- descripcion
- ip_address
- fecha

## Funcionalidades

### Panel de Administración
- ✅ Gestión de usuarios (crear, editar, desactivar)
- ✅ Gestión de direcciones (crear, editar, desactivar)
- ✅ Gestión de items de transparencia (crear, editar, desactivar)
- ✅ Asignación de usuarios a items
- ✅ Revisión de documentos subidos
- ✅ Visualización de logs de actividad

### Panel de Usuario
- ✅ Ver items asignados
- ✅ Agrupar items por periodicidad
- ✅ Subir documentos
- ✅ Ver estado de documentos (pendiente, aprobado, rechazado)

### Seguridad
- ✅ Contraseñas hasheadas con bcrypt
- ✅ Validación de sesión en todas las páginas
- ✅ Protección por perfil de usuario
- ✅ Log de todas las acciones
- ✅ Validación de archivos subidos

## Perfiles de Usuario

### 1. Administrativo
Acceso completo al sistema:
- Gestionar usuarios
- Gestionar direcciones
- Gestionar items
- Revisar documentos
- Ver logs

### 2. Director Revisor
Encargado de revisar documentos:
- Ver documentos pendientes
- Aprobar o rechazar documentos
- Agregar comentarios

### 3. Cargador de Información
Encargado de cargar documentos:
- Ver items asignados
- Subir documentos
- Ver estado de sus documentos

### 4. Publicador
Encargado de publicar información:
- Ver documentos aprobados
- Preparar publicación

## Periodicidades Soportadas

- **Mensual:** Requiere seleccionar el mes específico
- **Trimestral:** Marzo, Junio, Septiembre, Diciembre
- **Semestral:** 2 veces al año
- **Anual:** Una vez al año (enero)
- **Ocurrencia:** Libre

## Formatos de Archivo Permitidos

- PDF
- DOC, DOCX
- XLS, XLSX
- CSV
- JPG, JPEG
- PNG

**Tamaño máximo:** 10 MB

## Configuración de Email (Futuro)

En el archivo `config/config.php` puede configurar los parámetros de email:

```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USER', 'su@email.com');
define('MAIL_PASS', 'contraseña');
define('MAIL_FROM', 'noreply@cumplimiento.local');
```

## Troubleshooting

### Error: "Error de conexión"
- Verifique que MySQL esté corriendo
- Revise los parámetros en `config/config.php`

### Error: "Tabla no existe"
- Acceda a `http://localhost/cumplimiento/setup.php` para crear la BD

### Error al subir archivos
- Verifique que la carpeta `uploads/` tenga permisos de escritura
- El archivo excede 10MB

## Próximas Mejoras

- Sistema de notificaciones por email
- Reportes y estadísticas
- API REST
- Integración LDAP
- Sistema de versiones de documentos
- Firma digital
- Panel de publicación
- Integración con SharePoint

## Soporte

Para reportar problemas o sugerencias, contacte al administrador del sistema.

## Licencia

Privado - Uso interno

---

**Versión:** 1.0.0  
**Última actualización:** Diciembre 2025
