# GUÍA: Asignación de Múltiples Perfiles en Panel Admin

## 📍 Ubicación
**URL**: https://app.muniloslagos.cl/carga_ta/admin/usuarios/

## ✨ Nueva Funcionalidad

Ahora puedes asignar **múltiples perfiles** a un usuario desde el panel de administración, sin necesidad de ejecutar SQL manualmente.

## 🆕 Crear Usuario con Múltiples Perfiles

1. Clic en botón **"Nuevo Usuario"**
2. Completar datos básicos (nombre, email, contraseña)
3. En la sección **"Perfiles"**:
   - ✅ Marcar **uno o más** checkboxes de perfiles
   - El **primer perfil marcado** será el perfil principal (⭐)
   - Puedes marcar todos los que necesites

4. Seleccionar dirección (opcional)
5. Clic en **"Guardar"**

### Ejemplo:
```
Usuario: María González
Email: maria.gonzalez@muniloslagos.cl

Perfiles seleccionados:
☑ Auditor (será principal ⭐)
☑ Cargador de Información
☐ Publicador
☐ Administrador
☐ Director / Revisor

Resultado: María podrá actuar como auditora O como cargadora
```

## ✏️ Editar Perfiles de Usuario Existente

1. Clic en botón **lápiz** (editar) junto al usuario
2. El modal mostrará los perfiles actualmente asignados (marcados)
3. **Modificar perfiles**:
   - ✅ Marcar nuevos perfiles → Se agregarán
   - ☐ Desmarcar perfiles → Se eliminarán
   - Los perfiles que ya estaban marcados se **conservan**

4. Clic en **"Guardar"**

### Ejemplo de Edición:
```
Usuario: Juan Pérez
Perfiles actuales: Cargador de Información

Modificación:
☐ Cargador de Información (desmarcado)
☑ Auditor (nuevo)
☑ Publicador (nuevo)

Resultado: Juan ahora es Auditor + Publicador
(ya no es Cargador)
```

## 👁️ Visualización en Tabla

En la lista de usuarios, la columna **"Perfil"** ahora muestra:

- **Badge azul (⭐)**: Perfil principal
- **Badge celeste**: Perfiles adicionales

Ejemplo visual:
```
María González
[Auditor ⭐] [Cargador de Información]

Juan Pérez  
[Publicador ⭐]
```

## ⚠️ Validaciones

- ✅ **Obligatorio**: Al menos un perfil debe estar seleccionado
- ✅ El **primer perfil** seleccionado será el principal
- ✅ Los perfiles existentes se **conservan** al editar (solo cambian los que modifiques)
- ✅ Si desactivas un usuario, sus perfiles se mantienen en la base de datos

## 🔄 Orden de Perfiles

El orden en que marcas los checkboxes **sí importa**:

1. **Primer checkbox marcado** = Perfil principal (⭐)
2. Resto = Perfiles secundarios

Al editar, los checkboxes aparecen en este orden:
1. Administrador
2. Cargador de Información
3. Publicador
4. Auditor
5. Director / Revisor

## 💡 Casos de Uso Comunes

### Caso 1: Auditor que Carga sus Documentos
```
☑ Auditor (principal)
☑ Cargador de Información

→ El usuario puede cambiar entre perfiles según necesite
```

### Caso 2: Director con Acceso Administrativo
```
☑ Director / Revisor (principal)
☑ Administrador

→ Mantiene su rol de director pero puede acceder al panel admin
```

### Caso 3: Usuario Solo de Carga
```
☑ Cargador de Información

→ Funciona igual que antes (un solo perfil)
```

## 🔧 Migración Automática

**¿Qué pasó con los usuarios existentes?**

Cuando ejecutaste `migration_multiples_perfiles.sql`:
- ✅ Todos los usuarios existentes fueron migrados automáticamente
- ✅ Su perfil actual se mantuvo como perfil principal
- ✅ Pueden seguir trabajando sin cambios
- ✅ Ahora puedes agregarles perfiles adicionales desde el panel

## 📊 Verificación

Para verificar los perfiles de un usuario en la base de datos:

```sql
SELECT 
  u.nombre,
  u.email,
  GROUP_CONCAT(up.perfil ORDER BY up.es_principal DESC) AS perfiles
FROM usuarios u
LEFT JOIN usuario_perfiles up ON u.id = up.usuario_id
WHERE u.email = 'correo@usuario.com'
GROUP BY u.id;
```

## 🎯 Experiencia del Usuario

Cuando un usuario con múltiples perfiles hace login:

1. **Si tiene 1 perfil**: Entra directo (como siempre)
2. **Si tiene 2+ perfiles**: Ve pantalla de selección
3. **Dentro del sistema**: Puede cambiar de perfil desde el menú

Ver más detalles en: [GUIA_MULTIPLES_PERFILES.md](GUIA_MULTIPLES_PERFILES.md)

## 🐛 Troubleshooting

### "No puedo guardar usuario sin perfil"
**Solución**: Debes marcar al menos un checkbox de perfil.

### "Los perfiles no se cargan al editar"
**Solución**: Verifica que ejecutaste la migración `migration_multiples_perfiles.sql`

### "Aparece perfil duplicado en la tabla"
**Solución**: Refresca la página (F5) después de guardar cambios.

## 📝 Notas Importantes

- ✅ **Retrocompatible**: Usuarios con un solo perfil siguen funcionando igual
- ✅ **Conserva perfiles**: Al editar solo cambias lo que modificas
- ✅ **Sin pérdida de datos**: Los perfiles anteriores se mantienen en base de datos
- ✅ **Seguro**: Solo administradores pueden asignar perfiles

---

**Última actualización**: 2026-04-06  
**Commit**: 0e0a5d4  
**Requiere**: Migración `migration_multiples_perfiles.sql` ejecutada
