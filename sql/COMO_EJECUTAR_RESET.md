# 📖 Cómo Ejecutar el Reset del Sistema

## ⚠️ IMPORTANTE: Error Común

Si ves este error:
```
#1701 - Cannot truncate a table referenced in a foreign key constraint
```

**Significa que estás ejecutando líneas individuales en lugar del script completo.**

## ✅ Forma Correcta de Ejecutar

### Opción 1: Desde Terminal (Recomendado)

```bash
# En el servidor de producción
mysql -u usuario -p nombre_base_datos < sql/reset_sistema.sql
```

### Opción 2: phpMyAdmin - Importar Archivo

1. Abre phpMyAdmin
2. Selecciona tu base de datos
3. Ve a la pestaña **"Importar"** (no "SQL")
4. Haz clic en **"Elegir archivo"**
5. Selecciona: `sql/reset_sistema.sql`
6. Haz clic en **"Continuar"**

**NO ejecutes el script copiando y pegando líneas en la pestaña SQL.**

### Opción 3: phpMyAdmin - SQL (Con Precaución)

Si DEBES usar la pestaña SQL:

1. Abre el archivo `sql/reset_sistema.sql` en un editor de texto
2. **Copia TODO el contenido** (desde la primera línea hasta la última)
3. En phpMyAdmin, pestaña SQL
4. Pega TODO el contenido
5. Haz clic en "Continuar"

## 🔍 ¿Por Qué Ocurre el Error?

El script comienza con:
```sql
SET FOREIGN_KEY_CHECKS = 0;
```

Esta línea desactiva temporalmente las verificaciones de claves foráneas, permitiendo hacer TRUNCATE en tablas relacionadas.

**Si ejecutas solo una línea** (por ejemplo, solo `TRUNCATE TABLE documentos;`), MySQL no sabe que debe ignorar las foreign keys y arroja el error.

## ✅ Verificación de Éxito

Si el script se ejecutó correctamente, verás al final:

```
Reset completado exitosamente. Sistema listo para iniciar desde cero.
```

Y las verificaciones mostrarán:
- Documentos restantes: 0
- Seguimiento de documentos restantes: 0
- Observaciones restantes: 0

## 🆘 Solución Rápida

Si ya ejecutaste varias líneas y tienes errores:

1. **Cierra phpMyAdmin** completamente
2. Vuelve a abrir phpMyAdmin
3. **Importa el archivo completo** usando Opción 1 o 2

## 📞 Soporte

Si tienes problemas, asegúrate de:
- ✅ Ejecutar el script COMPLETO desde el inicio
- ✅ No omitir la línea `SET FOREIGN_KEY_CHECKS = 0;`
- ✅ Tener permisos suficientes en la base de datos
- ✅ Haber hecho un respaldo antes de ejecutar

---

**Recuerda:** Este script elimina TODOS los documentos. Es irreversible. Haz un respaldo primero.
