# ⚠️ MIGRACIÓN URGENTE - Agregar Perfil Revisor a Base de Datos

## 🔴 Error Encontrado

Al intentar asignar el perfil "Revisor" a un usuario, se produce el siguiente error:

```
Fatal error: Uncaught mysqli_sql_exception: 
Data truncated for column 'perfil' at row 1 
in Usuario.php:192
```

## 🎯 Causa del Problema

Las tablas `usuarios` y `usuario_perfiles` tienen columnas `perfil` definidas como **ENUM** que **NO incluyen** el valor `'revisor'`:

```sql
-- ENUM ANTIGUO (INCORRECTO)
ENUM('administrativo','director_revisor','cargador_informacion','publicador','auditor')

-- Falta: 'revisor'
```

## ✅ Solución

Ejecutar la migración SQL que actualiza los ENUM para incluir `'revisor'`.

---

## 📋 PASOS PARA EJECUTAR LA MIGRACIÓN

### **Opción 1: phpMyAdmin (Recomendado)**

1. Abre **phpMyAdmin**
2. Selecciona tu base de datos
3. Ve a la pestaña **SQL**
4. Copia y pega el siguiente código:

```sql
-- ============================================================================
-- MIGRACIÓN: Agregar perfil 'revisor' al ENUM
-- ============================================================================

-- 1. Modificar tabla usuarios
ALTER TABLE `usuarios` 
MODIFY COLUMN `perfil` ENUM(
    'administrativo',
    'director_revisor',
    'cargador_informacion',
    'revisor',
    'publicador',
    'auditor'
) COLLATE utf8mb4_unicode_ci NOT NULL;

-- 2. Modificar tabla usuario_perfiles
ALTER TABLE `usuario_perfiles` 
MODIFY COLUMN `perfil` ENUM(
    'administrativo',
    'director_revisor',
    'cargador_informacion',
    'revisor',
    'publicador',
    'auditor'
) COLLATE utf8mb4_unicode_ci NOT NULL;

-- 3. Verificar cambios
SHOW COLUMNS FROM `usuarios` LIKE 'perfil';
SHOW COLUMNS FROM `usuario_perfiles` LIKE 'perfil';

SELECT 'Perfil revisor agregado exitosamente' AS resultado;
```

5. Haz clic en **Ejecutar**
6. Verifica que aparezca: "Perfil revisor agregado exitosamente"

---

### **Opción 2: MySQL Command Line**

```bash
mysql -u root -p nombre_base_datos < /ruta/sql/migration_agregar_perfil_revisor.sql
```

---

### **Opción 3: Ejecutar archivo completo**

El archivo está en:
```
sql/migration_agregar_perfil_revisor.sql
```

Contenido completo disponible en el repositorio.

---

## 🔍 Verificación

Después de ejecutar la migración, verifica que funcionó:

```sql
-- Verificar ENUM en tabla usuarios
SHOW COLUMNS FROM `usuarios` LIKE 'perfil';

-- Verificar ENUM en tabla usuario_perfiles
SHOW COLUMNS FROM `usuario_perfiles` LIKE 'perfil';
```

**Resultado esperado:**
```
Type: enum('administrativo','director_revisor','cargador_informacion','revisor','publicador','auditor')
```

---

## ✅ Después de la Migración

1. **Recarga la página** de administración de usuarios
2. **Edita un usuario**
3. **Verás el perfil "Revisor de Documentos"** disponible para seleccionar
4. **Asigna el perfil** sin errores

---

## 📊 Tablas Afectadas

| Tabla | Columna | Cambio |
|-------|---------|--------|
| `usuarios` | `perfil` | ENUM actualizado con 'revisor' |
| `usuario_perfiles` | `perfil` | ENUM actualizado con 'revisor' |

---

## 🛡️ Seguridad

Esta migración es **100% segura**:
- ✅ No elimina datos existentes
- ✅ No modifica valores actuales
- ✅ Solo amplía las opciones disponibles en el ENUM
- ✅ Todos los perfiles existentes siguen funcionando

---

## 🚨 Si el Error Persiste

Si después de ejecutar la migración el error persiste:

1. **Limpia la caché del navegador** (Ctrl + Shift + Del)
2. **Cierra sesión y vuelve a iniciar**
3. Verifica que la migración se ejecutó correctamente:
   ```sql
   SHOW COLUMNS FROM `usuario_perfiles` LIKE 'perfil';
   ```
4. Revisa que el resultado incluya `'revisor'` en el ENUM

---

## 📝 Orden Completo de Migraciones

Para un sistema desde cero o para verificar que todo está actualizado:

```sql
-- 1. Configuración general
source sql/migration_configuracion_general.sql

-- 2. Sistema revisor (tabla revisiones_documentos)
source sql/migration_revisor_perfil_safe.sql

-- 3. ⚠️ ESTA MIGRACIÓN (Agregar revisor a ENUM)
source sql/migration_agregar_perfil_revisor.sql
```

---

## ✅ Resultado Final

Después de ejecutar esta migración:

1. Podrás **asignar el perfil Revisor** a usuarios sin errores
2. Los usuarios con perfil Revisor podrán acceder a su dashboard
3. El sistema de revisión previa funcionará completamente

---

## 🔗 Commit

- **Commit**: `952e3ef`
- **Mensaje**: "fix: Agregar 'revisor' a ENUM de columnas perfil"
- **GitHub**: ✅ Pusheado

---

## 📞 Soporte

Si tienes dudas o problemas:
1. Revisa los logs de MySQL para errores específicos
2. Verifica que estás ejecutando el SQL en la base de datos correcta
3. Asegúrate de tener permisos de ALTER TABLE

---

**IMPORTANTE**: Esta migración debe ejecutarse **ANTES** de intentar asignar el perfil Revisor a cualquier usuario.
