# 🔄 Reset del Sistema de Transparencia Activa

Este directorio contiene los scripts necesarios para resetear el sistema y comenzar la operación desde cero.

## 📋 ¿Qué se elimina?

- ✗ **Todos los documentos cargados** (tabla `documentos`)
- ✗ **Todas las observaciones** de documentos e ítems
- ✗ **Archivos físicos** en carpeta `uploads/` (manual)

## ✅ ¿Qué se mantiene?

- ✓ **Usuarios** (con sus contraseñas actuales)
- ✓ **Direcciones** (estructura organizacional)
- ✓ **Directores** (responsables de las direcciones)
- ✓ **Items de transparencia** (configuración de ítems)
- ✓ **Plantillas de correo** (templates de emails)
- ✓ **Años configurados** (años disponibles)
- ✓ **Configuración del Alcalde** (datos del alcalde y subrogantes)
- ✓ **Historial de envíos de correo** (para referencia)
- ✓ **Tokens de resumen público** (enlaces de correos siguen funcionando)

## 🚀 Procedimiento de Reset

### Paso 1: Respaldar Base de Datos (Recomendado)

```bash
# En el servidor de producción
mysqldump -u usuario -p nombre_db > backup_antes_reset_$(date +%Y%m%d_%H%M%S).sql
```

### Paso 2: Ejecutar Reset SQL

```bash
# Conectarse a MySQL
mysql -u usuario -p nombre_db

# Ejecutar el script
source sql/reset_sistema.sql;

# O desde la terminal directamente:
mysql -u usuario -p nombre_db < sql/reset_sistema.sql
```

### Paso 3: Eliminar Archivos Físicos

#### Opción A: Linux/Unix (Servidor de Producción)

```bash
# Dar permisos de ejecución
chmod +x sql/reset_uploads.sh

# Ejecutar script
sudo ./sql/reset_uploads.sh
```

#### Opción B: Windows (Desarrollo Local)

```powershell
# Ejecutar PowerShell como Administrador
cd C:\xampp\htdocs\cumplimiento

# Ejecutar script
.\sql\reset_uploads.ps1
```

#### Opción C: Manual

```bash
# Linux/Mac
rm -rf /ruta/del/proyecto/uploads/*

# Windows
Remove-Item "C:\xampp\htdocs\cumplimiento\uploads\*" -Recurse -Force
```

### Paso 4: Verificar Reset

Ejecutar en MySQL:

```sql
-- Verificar que no hay documentos
SELECT COUNT(*) FROM documentos;  -- Debe ser 0

-- Verificar que no hay observaciones
SELECT COUNT(*) FROM observaciones_documentos;  -- Debe ser 0
SELECT COUNT(*) FROM observaciones_sin_movimiento;  -- Debe ser 0

-- Verificar que se mantienen usuarios
SELECT COUNT(*) FROM usuarios WHERE activo = 1;

-- Verificar que se mantienen direcciones
SELECT COUNT(*) FROM direcciones WHERE activa = 1;
```

## ⚠️ ADVERTENCIAS IMPORTANTES

1. **Respaldo:** Siempre haz un respaldo completo antes de ejecutar estos scripts
2. **Irreversible:** Una vez eliminados, los datos NO se pueden recuperar
3. **Archivos físicos:** Los scripts SQL NO eliminan archivos del disco
4. **Producción:** Ejecutar solo cuando estés 100% seguro
5. **Usuarios:** Los usuarios mantendrán sus contraseñas actuales

## 📝 Orden de Ejecución

1. ✅ Respaldar base de datos
2. ✅ Ejecutar `reset_sistema.sql`
3. ✅ Ejecutar `reset_uploads.sh` o `reset_uploads.ps1`
4. ✅ Verificar que todo se eliminó correctamente
5. ✅ Informar a los usuarios que el sistema está listo

## 🔙 Restaurar desde Respaldo

Si algo sale mal:

```bash
# Restaurar base de datos
mysql -u usuario -p nombre_db < backup_antes_reset_YYYYMMDD_HHMMSS.sql
```

## 📞 Soporte

Si tienes dudas sobre el proceso de reset, consulta con el administrador del sistema antes de ejecutar.

---

**Última actualización:** 6 de abril de 2026
