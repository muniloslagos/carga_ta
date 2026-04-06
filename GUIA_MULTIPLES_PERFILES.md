# GUÍA: Sistema de Múltiples Perfiles por Usuario

## 📋 Descripción

El sistema ahora permite que un usuario tenga **múltiples perfiles asignados**. Por ejemplo:
- Un **auditor** puede también actuar como **cargador de información** en sus items asignados
- Un **director** puede tener acceso como **publicador** para gestionar sus propios documentos
- Un **administrativo** puede tener perfil de **cargador** para pruebas

## 🎯 Características

### ✅ Para Usuarios con Un Solo Perfil
- **Sin cambios**: El login funciona exactamente igual que antes
- Redirige automáticamente a su dashboard correspondiente
- No ven opciones de cambio de perfil

### ✅ Para Usuarios con Múltiples Perfiles
- **Al hacer login**: Se muestra pantalla de selección de perfil
- **Durante la sesión**: Pueden cambiar de perfil desde el menú superior
- **Flexibilidad**: Actúan según el perfil seleccionado en ese momento

## 📝 Instrucciones de Implementación

### 1️⃣ Ejecutar Migración (OBLIGATORIO)

```sql
-- En phpMyAdmin, ejecutar el archivo:
sql/migration_multiples_perfiles.sql
```

**¿Qué hace esta migración?**
- ✅ Crea tabla `usuario_perfiles` para la relación usuario-perfil
- ✅ Migra automáticamente todos los usuarios existentes
- ✅ NO elimina datos, todo se preserva
- ✅ Marca el perfil actual de cada usuario como perfil principal

### 2️⃣ Verificar Migración

Después de ejecutar, verificar que todos los usuarios tienen su perfil:

```sql
SELECT 
  u.id,
  u.nombre,
  u.perfil AS perfil_original,
  GROUP_CONCAT(up.perfil) AS perfiles_asignados
FROM usuarios u
LEFT JOIN usuario_perfiles up ON u.id = up.usuario_id
WHERE u.activo = 1
GROUP BY u.id;
```

## 🔧 Cómo Asignar Múltiples Perfiles

### Opción 1: Directamente en la Base de Datos

```sql
-- Agregar perfil adicional a un usuario
INSERT INTO usuario_perfiles (usuario_id, perfil, es_principal) 
VALUES (ID_USUARIO, 'cargador_informacion', 0);

-- Ejemplo: Agregar perfil "cargador_informacion" al usuario ID 5
INSERT INTO usuario_perfiles (usuario_id, perfil, es_principal) 
VALUES (5, 'cargador_informacion', 0);
```

### Perfiles Disponibles:
- `administrativo`
- `director_revisor`
- `cargador_informacion`
- `publicador`
- `auditor`

### Opción 2: Usando Código PHP (Futuro)

```php
// En admin panel (por implementar)
$usuarioClass->agregarPerfil($usuario_id, 'cargador_informacion', $_SESSION['user_id']);
```

## 👤 Experiencia del Usuario

### Flujo con Un Solo Perfil
```
Login → Autenticación → Dashboard (directo)
```

### Flujo con Múltiples Perfiles
```
Login → Autenticación → Selección de Perfil → Dashboard
```

### Cambio de Perfil Durante la Sesión
```
Usuario en Dashboard → Clic en "Cambiar Perfil" → Selección → Nuevo Dashboard
```

## 📁 Archivos Creados/Modificados

### Nuevos Archivos:
- `sql/migration_multiples_perfiles.sql` - Migración de base de datos
- `seleccionar_perfil.php` - Pantalla de selección de perfil

### Archivos Modificados:
- `classes/Usuario.php` - Métodos para gestionar perfiles múltiples
- `login.php` - Detección de múltiples perfiles
- `includes/header.php` - Opción "Cambiar Perfil" en menú

## 🔍 Casos de Uso

### Caso 1: Auditor que Carga sus Propios Items

```sql
-- Usuario María (ID: 10) es auditora pero necesita cargar sus items asignados
INSERT INTO usuario_perfiles (usuario_id, perfil, es_principal) 
VALUES (10, 'cargador_informacion', 0);

-- Ahora María puede:
-- 1. Entrar como "Auditor" → revisar cumplimiento
-- 2. Cambiar a "Cargador" → subir sus documentos
-- 3. Volver a "Auditor" → continuar auditoría
```

### Caso 2: Director con Acceso Administrativo

```sql
-- Director Juan (ID: 15) necesita acceso administrativo temporal
INSERT INTO usuario_perfiles (usuario_id, perfil, es_principal) 
VALUES (15, 'administrativo', 0);

-- Juan mantiene "director_revisor" como perfil principal
-- Pero puede acceder a panel administrativo cuando necesite
```

## ⚠️ Consideraciones Importantes

### Seguridad
- ✅ Solo se pueden seleccionar perfiles asignados al usuario
- ✅ No se puede "hackear" la sesión para obtener perfiles no asignados
- ✅ Todos los cambios de perfil se registran en logs

### Permisos
- El perfil activo determina qué puede hacer el usuario
- Los items/documentos asignados siguen siendo específicos del perfil
- Un auditor actuando como cargador solo ve sus propios items

### Retrocompatibilidad
- ✅ Usuarios existentes siguen funcionando sin cambios
- ✅ La columna `perfil` en tabla `usuarios` se mantiene
- ✅ Código legacy que verifica perfil sigue funcionando

## 🐛 Troubleshooting

### Problema: "No aparece opción Cambiar Perfil"
**Solución**: Verificar que el usuario tenga más de un perfil asignado

```sql
SELECT perfil FROM usuario_perfiles WHERE usuario_id = TU_ID;
```

### Problema: "Loop infinito en login"
**Solución**: Limpiar sesiones y volver a intentar

```php
// En la consola del navegador o eliminar cookies manualmente
```

### Problema: "Usuario no puede cambiar de perfil"
**Solución**: Verificar que `seleccionar_perfil.php` existe y es accesible

## 📊 Consultas Útiles

### Ver usuarios con múltiples perfiles:
```sql
SELECT 
  u.nombre,
  GROUP_CONCAT(up.perfil ORDER BY up.es_principal DESC) AS perfiles
FROM usuarios u
INNER JOIN usuario_perfiles up ON u.id = up.usuario_id
WHERE u.activo = 1
GROUP BY u.id
HAVING COUNT(up.id) > 1;
```

### Ver historial de cambios de perfil:
```sql
SELECT 
  u.nombre,
  l.accion,
  l.fecha,
  l.ip_address
FROM logs l
INNER JOIN usuarios u ON l.usuario_id = u.id
WHERE l.accion LIKE '%perfil%'
ORDER BY l.fecha DESC
LIMIT 50;
```

## 🚀 Próximos Pasos Sugeridos

1. ✅ Ejecutar migración en producción
2. ⏳ Identificar usuarios que necesitan múltiples perfiles
3. ⏳ Asignar perfiles adicionales según necesidad
4. ⏳ Capacitar usuarios sobre cómo cambiar de perfil
5. ⏳ Crear interfaz administrativa para gestionar perfiles (futuro)

---

**Fecha de Implementación**: 2026-04-06  
**Commit**: 9431ec9  
**Estado**: ✅ Completado y probado
