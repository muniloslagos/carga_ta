# Guía Rápida del Sistema de Transparencia Activa

## 🚀 Inicio Rápido

### 1️⃣ Configuración Inicial
1. Acceda a: `http://localhost/cumplimiento/`
2. Haga clic en "Verificar Sistema" para comprobar requisitos
3. Haga clic en "Configurar BD" para crear la base de datos
4. Los datos de administrador serán creados automáticamente

### 2️⃣ Acceso al Sistema
- **URL:** http://localhost/cumplimiento/login.php
- **Usuario Admin:** admin@cumplimiento.local
- **Contraseña:** admin123

---

## 👤 Guía por Perfil de Usuario

### 🔧 ADMINISTRATIVO

**Acceso:** Panel de Administración

**Funciones principales:**
- **Gestión de Usuarios**
  - Crear nuevos usuarios
  - Asignar perfiles (Admin, Director, Cargador, Publicador)
  - Asignar direcciones
  - Editar contraseñas
  - Desactivar usuarios

- **Gestión de Direcciones**
  - Crear direcciones/dependencias
  - Editar información
  - Desactivar direcciones
  - Ver usuarios asignados

- **Gestión de Items de Transparencia**
  - Crear items con numeración jerárquica (1, 1.1, 1.2, 2, etc)
  - Asignar dirección responsable
  - Definir periodicidad
  - Asignar usuarios cargadores
  - Editar información

- **Revisión de Documentos**
  - Ver todos los documentos subidos
  - Filtrar por estado (pendiente, aprobado, rechazado)
  - Descargar archivos
  - Revisar y aprobar/rechazar documentos
  - Agregar comentarios de revisión

- **Visualización de Logs**
  - Ver actividad de usuarios
  - Registros de acceso
  - Auditoría del sistema

**Acceso Rápido:**
```
Panel Admin → http://localhost/cumplimiento/admin/
  ├── Usuarios → admin/usuarios/
  ├── Direcciones → admin/direcciones/
  ├── Items → admin/items/
  └── Documentos → admin/documentos/
```

---

### 📋 DIRECTOR REVISOR

**Acceso:** Panel de Usuario (con permisos especiales)

**Funciones principales:**
- Ver documentos pendientes de revisión
- Revisar calidad de documentos
- Aprobar documentos conforme
- Rechazar documentos con comentarios
- Ver estado de revisiones

**Flujo de trabajo:**
1. Ingresa con su usuario
2. Ve documentos en estado "Pendiente"
3. Descarga y revisa el documento
4. Aprueba o rechaza con comentarios
5. El usuario cargador recibe notificación

---

### 📤 CARGADOR DE INFORMACIÓN

**Acceso:** Panel de Usuario

**Funciones principales:**
- Ver items asignados
- Subir documentos para cada item
- Ver estado de documentos (pendiente, aprobado, rechazado)
- Recibir comentarios de revisión

**Items por Periodicidad:**

**Mensual:**
- Debe cargar un documento cada mes
- Selecciona el mes específico
- Ejemplo: Reportes mensuales

**Trimestral:**
- Documentos en: Marzo, Junio, Septiembre, Diciembre
- Ejemplo: Reportes trimestrales

**Semestral:**
- 2 documentos al año
- Ejemplo: Evaluaciones semestrales

**Anual:**
- 1 documento al año (Enero)
- Ejemplo: Informe anual

**Ocurrencia:**
- Libre, según se requiera
- Ejemplo: Cambios organizacionales

**Cómo cargar un documento:**
1. Ingresa con su usuario
2. Selecciona el item en la pestaña correspondiente
3. Selecciona mes/período si aplica
4. Haz clic en "Enviar Documento"
5. Completa:
   - Título del documento
   - Descripción (opcional)
   - Selecciona el archivo
6. Haz clic en "Enviar"
7. Espera revisión del Director

**Formatos permitidos:**
- PDF, DOC, DOCX
- XLS, XLSX, CSV
- JPG, JPEG, PNG
- Máximo 10 MB

---

### 🌐 PUBLICADOR

**Acceso:** Panel de Usuario (con permisos limitados)

**Funciones principales:**
- Ver documentos aprobados
- Preparar para publicación
- Ver historial de publicaciones

---

## 📊 Estructura de Items

Los items se organizan jerárquicamente:

```
1. INFORMACIÓN DE IDENTIFICACIÓN
   1.1 Misión y Visión
   1.2 Estructura Administrativa
   1.3 Ubicación y Contacto

2. NORMATIVIDAD
   2.1 Reglamentos Internos
   2.2 Políticas
   2.3 Procedimientos

3. INFORMACIÓN FINANCIERA
   3.1 Presupuesto
   3.2 Estados Financieros
   3.3 Auditoría

4. CONTRATACIONES
   4.1 Procesos de Selección
   4.2 Contratos Vigentes

5. RECURSOS HUMANOS
   5.1 Nómina
   5.2 Beneficiarios
```

---

## 📋 Tabla de Responsabilidades

| Acción | Admin | Director | Cargador | Publicador |
|--------|-------|----------|----------|------------|
| Crear Items | ✅ | ❌ | ❌ | ❌ |
| Crear Usuarios | ✅ | ❌ | ❌ | ❌ |
| Cargar Documentos | ❌ | ❌ | ✅ | ❌ |
| Revisar Documentos | ✅ | ✅ | ❌ | ❌ |
| Publicar Documentos | ✅ | ❌ | ❌ | ✅ |
| Ver Documentos | ✅ | ✅ | ✅ | ✅ |
| Ver Logs | ✅ | ❌ | ❌ | ❌ |

---

## ⚙️ Configuración Técnica

### Variables de Entorno
Edite `config/config.php` para:
- Cambiar credenciales de BD
- Configurar zona horaria
- Definir URL del sitio

### Base de Datos
- **Host:** localhost (por defecto)
- **Usuario:** root (por defecto)
- **Base de Datos:** cumplimiento_db

### Carpetas Importantes
- `uploads/` - Almacenamiento de documentos
- `config/` - Configuración del sistema
- `classes/` - Modelos de datos
- `admin/` - Panel administrativo
- `usuario/` - Panel de usuario

---

## 🔐 Seguridad

### Buenas Prácticas
1. **Cambiar contraseña de admin** después del primer acceso
2. **No compartir credenciales** de administrador
3. **Crear usuarios individuales** para cada persona
4. **Revisar logs regularmente** para auditoría
5. **Hacer backups** periódicamente de la BD

### Contraseñas
- Mínimo 8 caracteres (recomendado)
- Incluir mayúsculas, minúsculas y números
- Las contraseñas se guardan hasheadas (bcrypt)

---

## 🐛 Troubleshooting

### "Error de conexión a base de datos"
- Verifique que MySQL esté ejecutándose
- Compruebe credenciales en `config/config.php`
- Ejecute `setup.php` si la BD no existe

### "Permiso denegado para subir archivos"
- Verifique permisos de la carpeta `uploads/`
- Asegúrese de que el usuario web tiene permisos de escritura

### "Archivo no se carga"
- Verifique que no exceda 10 MB
- Confirme que el formato sea permitido
- Intente con un archivo más pequeño

### "No veo mis items asignados"
- Verifique que el admin los haya asignado
- Revise que no esté desactivado el usuario
- Compruebe el perfil del usuario

---

## 📞 Soporte

Para problemas técnicos:
1. Revise el README.md
2. Consulte los logs del sistema
3. Verifique la configuración en `check.php`
4. Contacte al administrador del sistema

---

## 📅 Próximas Versiones

Mejoras planeadas:
- ✉️ Notificaciones por email
- 📊 Reportes automáticos
- 🔗 API REST
- 🔐 Autenticación LDAP
- 📄 Control de versiones
- ✍️ Firma digital
- 🌐 Multi-idioma

---

**Versión:** 1.0.0  
**Última actualización:** Diciembre 2025  
**Soporte:** contacto@cumplimiento.local
