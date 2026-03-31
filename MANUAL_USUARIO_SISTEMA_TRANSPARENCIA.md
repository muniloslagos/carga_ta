# MANUAL DE USUARIO
## SISTEMA DE GESTIÓN DE TRANSPARENCIA ACTIVA
### Municipalidad de Los Lagos

---

## 1. INTRODUCCIÓN AL SISTEMA

### ¿Qué es este Sistema?
El Sistema de Gestión de Transparencia Activa es una plataforma web que centraliza y automatiza el cumplimiento de las obligaciones de publicación de información pública establecidas en la Ley N° 20.285.

### ¿Para qué sirve?
- **Organizar**: Items de transparencia con responsables claros
- **Recordar**: Alertas automáticas antes de vencimientos
- **Evidenciar**: Registro completo de cargas y publicaciones
- **Proteger**: Documentación ante fiscalizaciones del CPLT
- **Eficientar**: Reducción de trabajo manual y errores

### ¿Quién debe usarlo?
- Funcionarios designados como cargadores de información
- Directores de unidades (supervisión)
- Personal de transparencia (publicación)
- Administrativos (gestión general)

---

## 2. ACCESO AL SISTEMA

### URL de Acceso
**Producción**: https://app.muniloslagos.cl/carga_ta/login.php  
**Desarrollo**: http://localhost/cumplimiento/login.php

### Credenciales
Sus credenciales serán proporcionadas por el Administrador del Sistema. Al primer ingreso deberá cambiar su contraseña.

### Primer Ingreso
1. Abra su navegador web (Chrome, Firefox, Edge)
2. Ingrese la URL del sistema
3. Escriba su correo electrónico institucional
4. Ingrese su contraseña temporal
5. El sistema le solicitará cambiar la contraseña
6. Configure una contraseña segura (mínimo 8 caracteres)

### ¿Olvidó su contraseña?
Contacte al Administrador del Sistema para que reinicie su contraseña.

---

## 3. GUÍA POR PERFIL DE USUARIO

### 3.1 CARGADOR DE INFORMACIÓN

#### ¿Cuál es mi función?
Cargar los documentos correspondientes a los items de transparencia asignados dentro de los plazos establecidos.

#### Pantalla Principal: Dashboard
Al ingresar verá:

**Tarjetas de Items**
- Número y título del item (ej: "7a - Dotación de Personal")
- Periodicidad (Mensual, Trimestral, etc.)
- Próximo plazo de envío
- Semáforo de estado (Verde/Amarillo/Rojo)
- Botón "Cargar Documento"

#### Cómo Cargar un Documento

**Paso 1: Identificar el Item**
- Busque en su dashboard el item que debe cargar
- Verifique el mes/período correspondiente
- Revise el plazo de envío

**Paso 2: Preparar el Archivo**
- Formato preferido: PDF
- Tamaño máximo: 10 MB
- Nombre sugerido: `7a_Enero_2026_Dotacion.pdf`
- Asegúrese que contenga información completa y actualizada

**Paso 3: Subir al Sistema**
1. Haga clic en "Cargar Documento"
2. Seleccione el mes y año correspondiente
3. Haga clic en "Seleccionar archivo"
4. Busque el archivo en su computador
5. (Opcional) Agregue observaciones en el campo de comentarios
6. Haga clic en "Subir Documento"
7. Espere la confirmación de carga exitosa

**Paso 4: Verificar**
- El semáforo cambiará a verde
- Aparecerá el mensaje "Documento cargado correctamente"
- Podrá ver el historial de cargas

#### Interpretación de Semáforos

🟢 **Verde** = Documento cargado en plazo  
🟡 **Amarillo** = Faltan 3 días o menos para el vencimiento  
🔴 **Rojo** = Plazo vencido, debe cargar urgente

#### Filtros y Búsqueda
Use los filtros superiores para:
- Ver solo items pendientes
- Filtrar por mes específico
- Buscar por número de item

#### Historial de Cargas
Haga clic en "Ver Historial" para consultar:
- Todas sus cargas anteriores
- Fechas de carga
- Estados de revisión (Pendiente, Aprobado, Rechazado)
- Comentarios de revisión

---

### 3.2 PUBLICADOR

#### ¿Cuál es mi función?
Publicar los documentos aprobados en el sitio web municipal y cargar las evidencias de publicación (verificadores).

#### Flujo de Trabajo

**Paso 1: Revisar Documentos Aprobados**
1. Ingrese a su panel
2. Filtre por "Estado: Aprobado"
3. Descargue el documento a publicar

**Paso 2: Publicar en Sitio Web**
1. Ingrese al CMS del sitio web municipal
2. Navegue a la sección correspondiente (ej: Transparencia Activa > Personal)
3. Suba o actualice el documento
4. Verifique que el enlace funcione correctamente
5. Copie la URL exacta de publicación

**Paso 3: Tomar Evidencia (Verificador)**
1. Abra la página donde publicó
2. Tome captura de pantalla completa (use tecla Impr Pant)
3. Pegue en Paint u otro editor
4. Recorte mostrando: fecha, título, documento visible
5. Guarde como imagen (PNG o JPG)

**Paso 4: Cargar Verificador al Sistema**
1. En el Sistema, busque el documento publicado
2. Haga clic en "Cargar Verificador"
3. Seleccione la imagen capturada
4. Pegue la URL de publicación
5. Agregue comentarios si es necesario
6. Haga clic en "Guardar Verificador"

#### Buenas Prácticas
- Publique dentro de las 24 horas de la aprobación
- Use nombres descriptivos para verificadores: `7a_Enero_2026_Verificador.png`
- Asegúrese que el verificador sea legible
- Verifique que la fecha en el verificador sea visible

---

### 3.3 ADMINISTRATIVO

#### ¿Cuál es mi función?
Gestión completa del sistema: usuarios, items, direcciones, revisión de documentos y generación de reportes.

#### Panel de Administración
Acceso: `/admin/index.php`

**Secciones disponibles:**
1. Dashboard (métricas generales)
2. Usuarios
3. Direcciones
4. Items de Transparencia
5. Documentos (revisión)
6. Auditoría

#### Gestión de Usuarios

**Crear Usuario**
1. Panel Admin > Usuarios > "Nuevo Usuario"
2. Complete los datos:
   - Nombre completo
   - Correo institucional
   - Perfil (Cargador, Publicador, Auditor)
   - Dirección asignada
3. Marque "Activo"
4. Guarde

**Asignar Items a Usuario**
1. Panel Admin > Items
2. Use filtros para buscar items
3. Seleccione items con checkboxes
4. Haga clic en "Asignación Masiva"
5- Seleccione el usuario
6. Confirme la asignación

#### Gestión de Items

**Crear Nuevo Item**
1. Panel Admin > Items > "Nuevo Item"
2. Complete:
   - Número (ej: 7a, 7b, 1.1)
   - Título descriptivo
   - Periodicidad
   - Dirección responsable
3. Guarde

**Configurar Plazos**
Los plazos se calculan automáticamente según periodicidad:
- **Mensual**: Plazo envío día 8, publicación día 15
- **Trimestral**: Plazo envío día 15, publicación día 22
- **Semestral**: Plazo envío día 20, publicación último día mes
- **Anual**: Plazo envío 20 enero, publicación 31 enero

#### Revisión de Documentos

**Flujo de Revisión**
1. Panel Admin > Documentos
2. Filtre por "Estado: Pendiente"
3. Haga clic en "Revisar" en el documento
4. Descargue y revise el archivo
5. Decida:
   - **Aprobar**: Si cumple requisitos
   - **Rechazar**: Si tiene errores

**Criterios de Aprobación**
✅ Información completa y actualizada  
✅ Formato PDF legible  
✅ Corresponde al período solicitado  
✅ Cumple con estándares de transparencia  

**Al Rechazar**
- Sea específico en los comentarios
- Indique qué debe corregirse
- El cargador recibirá notificación automática

#### Reportes y Auditoría

**Consultar Logs**
1. Panel Admin > Auditoría
2. Filtre por:
   - Usuario
   - Fecha
   - Tipo de acción
3. Exporte a Excel si necesita

**Métricas de Cumplimiento**
- Porcentaje de items al día
- Items vencidos por dirección
- Usuarios con mayor cumplimiento
- Tendencias mensuales

---

### 3.4 AUDITOR

#### ¿Cuál es mi función?
Visualizar métricas, consultar documentos y generar reportes de fiscalización sin capacidad de modificación.

#### Panel de Auditoría
- Indicadores de cumplimiento general
- Estado de publicación por item
- Direcciones con atrasos
- Verificadores cargados
- Exportación de reportes

---

## 4. PLAZOS Y PERIODICIDADES

### Tabla de Plazos Estándar

| Periodicidad | Genera Obligación | Plazo Envío Sistema | Plazo Publicación Web |
|--------------|-------------------|---------------------|----------------------|
| Mensual      | Cada mes          | Día 8 mes siguiente | Día 15 mes siguiente |
| Trimestral   | Cada 3 meses      | Día 15 mes siguiente | Día 22 mes siguiente |
| Semestral    | Cada 6 meses      | Día 20 mes siguiente | Último día del mes |
| Anual        | Una vez al año    | 20 de enero         | 31 de enero |

### Ejemplo Práctico
**Item**: 7a - Dotación de Personal (Mensual)  
**Mes a informar**: Febrero 2026  
**Plazo envío sistema**: 8 de marzo 2026  
**Plazo publicación web**: 15 de marzo 2026

---

## 5. PREGUNTAS FRECUENTES

### ¿Qué hago si no tengo el documento a tiempo?
Contacte inmediatamente a su jefe directo y al Administrador del Sistema. Explique el motivo del retraso y la nueva fecha estimada.

### ¿Puedo reemplazar un documento ya cargado?
Sí, puede cargar una nueva versión. El sistema mantendrá el historial de versiones.

### ¿Qué pasa si cargo el archivo equivocado?
Contacte inmediatamente al Administrador para que elimine el archivo incorrecto antes de la revisión.

### ¿Por qué fue rechazado mi documento?
Revise los comentarios del revisor en la sección de historial. Corrija lo indicado y vuelva a cargar.

### ¿Cómo sé si mi item es mensual, trimestral o anual?
Está indicado en su dashboard, en la tarjeta del item, bajo el título.

### ¿Dónde veo mis items asignados?
En su dashboard principal al ingresar al sistema. Solo verá los items que le fueron asignados.

### ¿Puedo descargar documentos de otros usuarios?
No, solo puede ver y descargar sus propios documentos. Los Administradores y Auditores pueden ver todos.

### ¿El sistema envía recordatorios?
Sí, recibirá correos automáticos a 7, 3 y 1 día antes del vencimiento.

---

## 6. BUENAS PRÁCTICAS

### Para Cargadores
✅ Revise el sistema al menos 2 veces por semana  
✅ No espere al último día para cargar  
✅ Use formatos PDF preferentemente  
✅ Nombre sus archivos de forma descriptiva  
✅ Si tiene dudas sobre el contenido, consulte a su jefatura antes de cargar

### Para Publicadores
✅ Publique dentro de 24 horas de la aprobación  
✅ Tome verificadores nítidos y completos  
✅ Guarde las URLs de publicación  
✅ Verifique que los enlaces funcionen correctamente

### Para Administradores
✅ Revise documentos dentro de 48 horas  
✅ Sea específico en comentarios de rechazo  
✅ Monitoree el dashboard de cumplimiento diariamente  
✅ Genere reportes mensuales para la dirección

---

## 7. SOPORTE TÉCNICO

### ¿Problema con el Sistema?

**Nivel 1 - Soporte Básico**
- Usuario: Administrador del Sistema
- Email: transparencia@muniloslagos.cl
- Horario: Lunes a viernes, 8:30 - 17:00 hrs

**Nivel 2 - Soporte Técnico**
- Usuario: Departamento de Informática
- Email: informatica@muniloslagos.cl

### Reporte de Errores
Al reportar un error incluya:
- Perfil de usuario
- Acción que estaba realizando
- Mensaje de error (captura de pantalla)
- Navegador y sistema operativo usado

---

## 8. GLOSARIO DE TÉRMINOS

**Item**: Cada documento o antecedente que debe publicarse según Ley 20.285

**Periodicidad**: Frecuencia de actualización (mensual, trimestral, etc.)

**Plazo Envío**: Fecha límite para cargar documento al sistema

**Plazo Publicación**: Fecha límite para publicar en sitio web

**Verificador**: Captura de pantalla que evidencia la publicación

**Semáforo**: Indicador visual de estado (verde/amarillo/rojo)

**Dashboard**: Pantalla principal con resumen de items

**Historial**: Registro de todas las cargas anteriores

**CPLT**: Consejo para la Transparencia (organismo fiscalizador)

---

**Versión**: 1.0  
**Fecha**: Marzo 2026  
**Municipalidad de Los Lagos**

**Para consultas o sugerencias sobre este manual:**  
transparencia@muniloslagos.cl
