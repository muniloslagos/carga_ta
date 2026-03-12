# Sistema de Numeración - Municipalidad de Los Lagos

Sistema de gestión de turnos y atención al público con múltiples categorías.

## Características

- ✅ Múltiples categorías de atención (Permisos, Social, Salud, etc.)
- ✅ Hasta 10 módulos de atención
- ✅ Pantalla pública con audio (síntesis de voz)
- ✅ Emisión de tickets (impresión o manual)
- ✅ Panel de administración completo
- ✅ Estadísticas en tiempo real
- ✅ Filtro de pantallas por categoría

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB
- Servidor web (Apache/Nginx)
- Navegador moderno con soporte de Web Speech API

## Instalación

### 1. Configurar Base de Datos

Editar `config/database.php` con los datos de conexión:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'numeracion_muni');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 2. Ejecutar Instalación

Acceder a: `http://tu-servidor/numeracion/install.php`

Esto creará:
- La base de datos
- Las tablas necesarias
- El usuario administrador

### 3. Credenciales por defecto

- **Usuario:** admin
- **Contraseña:** admin123

**⚠️ IMPORTANTE:** Cambiar la contraseña después del primer acceso y eliminar `install.php`

## URLs del Sistema

| Componente | URL |
|------------|-----|
| Login | `/numeracion/login.php` |
| Panel Admin | `/numeracion/admin/` |
| Panel Girador | `/numeracion/girador/` |
| Emisor Tickets | `/numeracion/emisor/` |
| Pantalla Pública | `/numeracion/pantalla/` |

## Pantallas Filtradas por Categoría

```
# Solo Permisos de Circulación
/numeracion/pantalla/?cat=1

# Solo Asistencia Social
/numeracion/pantalla/?cat=2

# Múltiples categorías
/numeracion/pantalla/?cat=1,2,3

# Por perfil guardado
/numeracion/pantalla/?perfil=permisos
```

## Estructura de Archivos

```
/numeracion
├── /admin          # Panel de administración
├── /api            # Endpoints API
├── /assets         # CSS, JS, imágenes
├── /config         # Configuración
├── /emisor         # Emisor de tickets
├── /girador        # Panel de módulos
├── /includes       # Funciones PHP
├── /pantalla       # Pantalla pública
├── /sql            # Scripts SQL
├── index.php       # Redirección principal
├── login.php       # Login
├── logout.php      # Logout
└── install.php     # Instalador (ELIMINAR)
```

## Flujo de Uso

1. **Ciudadano** → Emisor → Selecciona categoría → Obtiene ticket
2. **Funcionario** → Girador → Selecciona módulo → Llama turnos
3. **Pantalla** → Muestra número + módulo + audio

## Configuración de Audio

La pantalla pública usa la API Web Speech para síntesis de voz.
Configurar velocidad y tono desde el panel de administración.

## Soporte

Sistema desarrollado para Municipalidad de Los Lagos.

---
Versión 1.0.0
