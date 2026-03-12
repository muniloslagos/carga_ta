# ESTRUCTURA DE PERFILES - SISTEMA CUMPLIMIENTO

## Perfiles Actuales (ENUM en BD)

### 1. **administrativo** 
- **ID 1: Administrador** (admin@cumplimiento.local)
- Acceso completo al panel `/admin/index.php`
- Gestiona usuarios, direcciones, items, documentos
- Puede revisar y aprobar documentos

### 2. **cargador_informacion**
- **ID 6: Marianela Jaramillo** (secretariadaf@muniloslagosl.cl)
- **ID 8: Juan Fica** (informatica@muniloslagos.cl)
- Acceso a `/usuario/dashboard.php`
- Cargan documentos según items asignados
- **NO tienen acceso a `/admin/`**

### 3. **director_revisor** (disponible para futuros usuarios)
- Rol reservado para directores que necesiten revisar documentos
- Acceso a `/admin/` pero con funcionalidades limitadas
- Requiere asignación y creación de nuevos usuarios

### 4. **publicador** (disponible para futuros usuarios)
- Rol para cargar verificadores/imágenes de documentos
- Acceso a `/admin/publicador/`
- Carga imágenes de verificación en documentos aprobados

## Problemas Resueltos ✓

1. **Marianela tenía acceso a admin**: ❌ ANTES
   - Estaba marcada como 'administrativo'
   - Podía acceder a `/admin/index.php` (NO debería)
   
2. **Ahora Marianela tiene acceso correcto**: ✅ DESPUÉS
   - Marcada como 'cargador_informacion'
   - Solo puede acceder a `/usuario/dashboard.php` (cargar documentos)
   - No puede acceder a `/admin/`

## Control de Acceso

```php
// admin/index.php
require_role('administrativo');  // Solo ID 1 (Administrador)

// usuario/dashboard.php
require_login();  // Cualquier usuario autenticado

// admin/publicador/index.php
require_role('administrativo' o 'director_revisor' o 'publicador')
```

## Flujo de Redirección al Login

- **Perfil = 'administrativo'** → Redirige a `/admin/index.php`
- **Otros perfiles** → Redirige a `/usuario/dashboard.php`

## Próximos Pasos (Opcional)

1. Crear usuario con perfil 'director_revisor' si es necesario
2. Crear usuario con perfil 'publicador' para verificadores
3. Asignar items a Marianela para que cargue documentos
