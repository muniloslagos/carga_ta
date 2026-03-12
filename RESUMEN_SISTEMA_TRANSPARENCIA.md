# 📊 MANUAL DEL SISTEMA DE ADMINISTRACIÓN DE CARGA UNIFICADA Y CONTROL DE TRANSPARENCIA

## 📖 Introducción al Sistema

### ¿Qué es este Sistema?

El **Sistema de Administración de Carga Unificada y Control de Transparencia** es una plataforma web integral diseñada específicamente para ayudar a instituciones públicas chilenas (municipalidades, servicios públicos, ministerios) a cumplir con las obligaciones de **Transparencia Activa** establecidas en la **Ley N° 20.285 sobre Acceso a la Información Pública**.

Este sistema centraliza, automatiza y documenta todo el proceso de gestión de información pública, desde la asignación de responsabilidades hasta la verificación de publicación efectiva, creando una **cadena de trazabilidad completa** que protege a la institución ante fiscalizaciones y demuestra el cumplimiento legal.

---

### ¿Por qué existe este Sistema?

#### **Problema Tradicional: El Caos de la Transparencia**

Antes de implementar sistemas como este, las instituciones enfrentan múltiples desafíos:

**🔴 Problema 1: Dispersión de la Información**
- Funcionarios envían documentos por email
- Correos se pierden en bandejas llenas
- No hay registro de quién envió qué
- Documentos guardados en múltiples carpetas sin orden
- Versiones desactualizadas circulan sin control

**🔴 Problema 2: Falta de Claridad en Responsabilidades**
- No está claro quién debe cargar cada item
- Funcionarios asumen que "otro lo hará"
- Cuando hay fiscalización, nadie sabe quién era responsable
- Conflictos entre áreas sobre a quién le corresponde

**🔴 Problema 3: Olvidos y Plazos Vencidos**
- No hay recordatorios automatizados
- Se olvidan items menos frecuentes (semestrales, anuales)
- Se enteran del incumplimiento cuando llega la multa
- Pánico de último minuto para "ponerse al día"

**🔴 Problema 4: Falta de Evidencia**
- Institución dice que publicó, pero no tiene pruebas
- CPLT fiscaliza y no pueden demostrar cumplimiento
- Multas de hasta 15 UTM por item incumplido
- Costo anual en multas: millones de pesos

**🔴 Problema 5: Trabajo Manual Excesivo**
- Administrativo revisa manualmente 50+ documentos al mes
- Pierde horas buscando "quién no ha enviado X"
- Genera reportes manualmente en Excel
- Trabajo repetitivo que genera errores humanos

#### **Solución: Sistema Integral de Control**

Este sistema resuelve todos estos problemas mediante:

✅ **Centralización**: Un solo lugar para todo  
✅ **Automatización**: Recordatorios, cálculos, alertas  
✅ **Trazabilidad**: Historial completo de cada acción  
✅ **Evidencia**: Verificadores visuales de publicación  
✅ **Eficiencia**: 60% menos tiempo administrativo  
✅ **Cumplimiento**: Protección legal ante fiscalizaciones  

---

### Marco Legal: Ley 20.285

#### **¿Qué es la Transparencia Activa?**

La **Transparencia Activa** es la obligación legal de las instituciones públicas de publicar cierta información de manera **permanente, actualizada y accesible** en sus sitios web, **sin que nadie la solicite**.

**Diferencia con Transparencia Pasiva**:
- **Activa**: Institución publica proactivamente (sin solicitud)
- **Pasiva**: Ciudadano solicita información específica (portal OIRS)

#### **Artículo 7 de la Ley 20.285**

El Artículo 7 establece los **16 tipos de información** (literales) que toda institución pública debe publicar:

```
7a) Personal: Dotación, remuneraciones, viáticos
7b) Contrataciones: Licitaciones, contratos
7c) Transferencias: Subsidios, aportes a personas
7d) Actos/resoluciones con efectos sobre terceros
7e) Trámites y requisitos
7f) Mecanismos de participación ciudadana
7g) Presupuesto: Ingresos y gastos
7h) Compras y contrataciones
7i) Marcos normativos vigentes
7j) Beneficiarios de programas sociales
7k) Actas de concejo/consejo
7l) Planes y políticas institucionales
7m) Auditorías y evaluaciones
7n) Estadísticas de atención ciudadana
7ñ) Uso de publicidad y RRSS
7o) Beneficiarios de vacaciones judiciales
```

#### **Consecuencias del Incumplimiento**

**Multas del CPLT (Consejo para la Transparencia)**:
- Entre 20% y 50% de la remuneración del jefe de servicio
- Multas hasta **15 UTM por cada incumplimiento**
- Publicación del incumplimiento (daño reputacional)
- Responsabilidad funcionaria

**Ejemplo Real**:
Una municipalidad que no publica 10 items mensuales durante 6 meses:
- 10 items × 6 meses = 60 incumplimientos
- 60 × 10 UTM (promedio) = 600 UTM
- 600 UTM × $65.000 = **$39.000.000 en multas**

**Este sistema ayuda a evitar estos costos mediante prevención proactiva.**

---

## 🏗️ Arquitectura del Sistema

### Visión General

El sistema está construido sobre una arquitectura modular que separa claramente las responsabilidades de cada componente, permitiendo escalabilidad, mantenibilidad y seguridad.

### Componentes Principales

#### **1. Gestión de Usuarios y Perfiles**
**Propósito**: Controlar quién accede al sistema y qué puede hacer.

**Funcionalidades**:
- Sistema de autenticación seguro (login/logout)
- Cuatro perfiles diferenciados (administrativo, cargador, revisor, publicador)
- Permisos granulares por función
- Gestión de sesiones con timeout automático
- Registro de actividad por usuario

**Tecnología**:
- PHP Sessions para manejo de sesiones
- BCrypt para hash de contraseñas (irreversible)
- Middleware de autenticación en cada página protegida

---

#### **2. Administración de Direcciones/Departamentos**
**Propósito**: Organizar la institución en unidades administrativas.

**Funcionalidades**:
- Crear/editar/desactivar direcciones
- Asignar múltiples usuarios a cada dirección
- Ver métricas de cumplimiento por dirección
- Comparar desempeño entre direcciones
- Identificar direcciones con rezago

**Beneficio**: Permite distribuir responsabilidades según organigrama real de la institución.

---

#### **3. Control de Items de Transparencia**
**Propósito**: Definir QUÉ información debe publicarse.

**Funcionalidades**:
- Catálogo completo de items según Ley 20.285
- Numeración jerárquica (1, 1.1, 1.2, 2, 2.1, etc.)
- Asignación de periodicidad (mensual/trimestral/etc.)
- Asignación de dirección responsable
- Asignación de usuarios específicos por item
- Activación/desactivación temporal de items

**Beneficio**: Claridad absoluta sobre qué debe publicarse y con qué frecuencia.

---

#### **4. Carga y Revisión de Documentos**
**Propósito**: Gestionar el flujo de documentos desde la carga hasta la aprobación.

**Funcionalidades**:
- Carga de archivos múltiples formatos (PDF, Excel, Word, CSV, imágenes)
- Asignación de metadatos (título, descripción, período)
- Sistema de estados (pendiente/aprobado/rechazado)
- Flujo de revisión con observaciones
- Historial de versiones
- Descarga de documentos

**Beneficio**: Workflow estructurado que asegura calidad antes de publicar.

---

#### **5. Sistema de Plazos Internos**
**Propósito**: Gestionar CUÁNDO debe publicarse cada información.

**Funcionalidades**:
- Configuración de plazos internos por item/mes/año
- Configuración de fechas de publicación en portal externo
- Cálculo automático de "mes de carga" (mes anterior)
- Alertas automáticas días antes del vencimiento
- Vista de calendario mensual/anual
- Copiado masivo de plazos de un año a otro

**Beneficio**: Prevención proactiva de incumplimientos mediante recordatorios oportunos.

---

#### **6. Historial y Auditoría**
**Propósito**: Registrar TODO lo que sucede en el sistema.

**Funcionalidades**:
- Log automático de cada acción (crear/editar/eliminar/aprobar/rechazar)
- Registro de usuario, fecha/hora, IP, navegador
- Almacenamiento de valores anteriores y nuevos (audit trail)
- Búsqueda y filtrado de logs
- Exportación para auditorías externas
- Detección de patrones anormales

**Beneficio**: Trazabilidad completa ante fiscalizaciones del CPLT o Contraloría.

---

#### **7. Panel de Publicación**
**Propósito**: Gestionar la publicación efectiva en portales externos y crear evidencia.

**Funcionalidades**:
- Lista de documentos aprobados pendientes de publicar
- Registro de fecha de publicación efectiva
- Carga de verificadores (capturas de pantalla del portal)
- Almacenamiento de URLs de publicación
- Historial de publicaciones con evidencia
- Generación de reportes para fiscalización

**Beneficio**: Prueba irrefutable de que la información fue efectivamente publicada.

---

### Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                     │
│  (Interfaz Web - HTML5 + Bootstrap 5 + JavaScript)          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [Login]  [Dashboard]  [Admin Panel]  [Usuario Panel]      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                            ↕️
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE LÓGICA DE NEGOCIO                │
│               (PHP 7.4+ - Orientado a Objetos)              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Classes:                                                   │
│  ├─ Usuario.php        (Gestión de usuarios)               │
│  ├─ Direccion.php      (Gestión de direcciones)            │
│  ├─ Item.php           (Gestión de items)                  │
│  ├─ Documento.php      (Gestión de documentos)             │
│  ├─ ItemPlazo.php      (Gestión de plazos)                 │
│  ├─ Historial.php      (Registro de auditoría)             │
│  └─ Verificador.php    (Gestión de verificadores)          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                            ↕️
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE ACCESO A DATOS                   │
│                  (PDO - Prepared Statements)                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Database.php - Clase singleton de conexión                │
│  - Prevención de SQL Injection                             │
│  - Manejo de transacciones                                 │
│  - Pool de conexiones                                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                            ↕️
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE PERSISTENCIA                     │
│                    (MySQL 5.7+ / MariaDB)                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Tablas Principales:                                        │
│  ├─ usuarios              (Cuentas de acceso)              │
│  ├─ direcciones           (Departamentos)                  │
│  ├─ items_transparencia   (Catálogo de items)              │
│  ├─ item_usuarios         (Asignaciones)                   │
│  ├─ documentos            (Archivos cargados)              │
│  ├─ documento_seguimiento (Tracking por período)           │
│  ├─ item_plazos           (Plazos configurados)            │
│  ├─ historial_cambios     (Audit log)                      │
│  └─ verificadores         (Evidencia de publicación)       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                            ↕️
┌─────────────────────────────────────────────────────────────┐
│                    SISTEMA DE ARCHIVOS                      │
│                     (Almacenamiento)                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  /uploads/                                                  │
│  ├─ documentos/    (Archivos de transparencia)             │
│  ├─ verificadores/ (Capturas de pantalla)                  │
│  └─ temporales/    (Procesamiento)                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Datos

**Ejemplo: Usuario carga un documento**

```
1. Usuario hace clic en "Cargar Documento" (PRESENTACIÓN)
        ↓
2. JavaScript valida formulario en cliente (VALIDACIÓN CLIENTE)
        ↓
3. Envío POST a /usuario/enviar_documento.php (HTTP REQUEST)
        ↓
4. PHP valida sesión y permisos (AUTENTICACIÓN)
        ↓
5. Clase Documento::create() procesa (LÓGICA DE NEGOCIO)
        ↓
6. Documento::validarArchivo() verifica formato/tamaño
        ↓
7. Archivo guardado en /uploads/ (SISTEMA DE ARCHIVOS)
        ↓
8. INSERT en tabla 'documentos' (BASE DE DATOS)
        ↓
9. INSERT en tabla 'documento_seguimiento' (TRACKING)
        ↓
10. INSERT en tabla 'historial_cambios' (AUDITORÍA)
        ↓
11. Response JSON con confirmación (HTTP RESPONSE)
        ↓
12. JavaScript actualiza interfaz (PRESENTACIÓN)
        ↓
13. Usuario ve mensaje: "✅ Documento enviado exitosamente"
```

---

### Tecnologías Utilizadas

| Componente | Tecnología | Versión | Propósito |
|------------|-----------|---------|-----------|
| **Backend** | PHP | 7.4+ | Lógica del servidor |
| **Base de Datos** | MySQL | 5.7+ | Almacenamiento de datos |
| **Frontend** | HTML5 + CSS3 | - | Estructura y estilos |
| **Framework CSS** | Bootstrap | 5.3 | Diseño responsivo |
| **JavaScript** | Vanilla JS + jQuery | 3.6 | Interactividad |
| **Iconos** | Bootstrap Icons | 1.10 | Iconografía |
| **Servidor Web** | Apache | 2.4+ | Servidor HTTP |
| **Control de Versiones** | Git | - | Versionamiento (opcional) |

---

### Seguridad del Sistema

#### **Autenticación**
- ✅ Contraseñas hasheadas con BCrypt (costo 12)
- ✅ Salt automático por contraseña
- ✅ No se almacenan contraseñas en texto plano
- ✅ Sesiones PHP con regeneración de ID
- ✅ Timeout de sesión configurable

#### **Autorización**
- ✅ Middleware `check_auth.php` en todas las páginas protegidas
- ✅ Función `require_role()` para permisos específicos
- ✅ Validación de permisos en backend (no solo frontend)
- ✅ Usuario solo ve sus items asignados

#### **Prevención de Vulnerabilidades**
- ✅ **SQL Injection**: Prepared Statements en todas las consultas
- ✅ **XSS**: htmlspecialchars() en todas las salidas
- ✅ **CSRF**: Tokens en formularios críticos (opcional)
- ✅ **Path Traversal**: Validación de rutas de archivo
- ✅ **Upload de Archivos**: Validación de extensión, MIME type y tamaño

#### **Auditoría**
- ✅ Registro de todos los accesos al sistema
- ✅ Log de cambios con usuario/fecha/IP
- ✅ Historial inmutable (no se pueden editar logs)
- ✅ Detección de intentos de acceso no autorizados

---

## 👥 Perfiles de Usuario y Roles - Detalle Completo

### 1. 🔧 **ADMINISTRATIVO** (Superusuario)

**Responsabilidad General**: Gestión integral del sistema, configuración, supervisión y control total del cumplimiento de transparencia.

---

#### 📋 Funciones Principales

##### A. GESTIÓN DE USUARIOS
**¿Qué puede hacer?**
- ✅ Crear nuevos usuarios del sistema
- ✅ Editar información de usuarios existentes (nombre, email, perfil)
- ✅ Cambiar contraseñas de usuarios
- ✅ Activar/desactivar cuentas (sin eliminar datos)
- ✅ Asignar usuarios a direcciones específicas
- ✅ Ver historial de actividad por usuario

**Panel**: `/admin/usuarios/index.php`

**Flujo de Trabajo**:
```
1. Click en "Usuarios" en menú administrativo
2. Ver tabla con todos los usuarios del sistema
3. Filtrar por nombre, email o perfil
4. Click "Nuevo Usuario":
   ├─ Ingresar nombre completo
   ├─ Ingresar email corporativo
   ├─ Seleccionar perfil (administrativo/cargador/revisor/publicador)
   ├─ Asignar a dirección municipal
   └─ Contraseña inicial generada automáticamente
5. Click "Guardar"
6. Usuario recibe credenciales por email (opcional)
```

**Casos de Uso Reales**:
- 📌 **Nuevo funcionario**: El administrativo crea cuenta para nuevo encargado de presupuesto
- 📌 **Cambio de departamento**: Reasigna usuario de Finanzas a Educación
- 📌 **Licencia médica**: Desactiva temporalmente usuario sin perder su historial
- 📌 **Rotación de personal**: Cambia responsable de items de usuario saliente

**Validaciones del Sistema**:
- ⚠️ Email debe ser único en el sistema
- ⚠️ No se puede eliminar usuarios con documentos asociados
- ⚠️ Cambio de perfil requiere confirmación (afecta permisos)

---

##### B. GESTIÓN DE DIRECCIONES MUNICIPALES
**¿Qué puede hacer?**
- ✅ Crear nuevas direcciones/departamentos
- ✅ Editar nombre y descripción
- ✅ Activar/desactivar direcciones
- ✅ Asignar múltiples usuarios a cada dirección
- ✅ Ver items asignados por dirección
- ✅ Generar reportes de cumplimiento por dirección

**Panel**: `/admin/direcciones/index.php`

**Flujo de Trabajo**:
```
1. Click en "Direcciones" en menú
2. Ver listado de direcciones municipales
3. Click "Nueva Dirección":
   ├─ Nombre (ej: "Dirección de Educación")
   ├─ Descripción (opcional)
   └─ Estado (activa/inactiva)
4. Click "Asignar Usuarios" en dirección creada
5. Mover usuarios de "Disponibles" a "Asignados"
6. Ver resumen: X usuarios, Y items asignados
```

**Casos de Uso Reales**:
- 📌 **Reorganización municipal**: Crea nueva "Dirección de Innovación"
- 📌 **Fusión de áreas**: Combina "Cultura" y "Deportes" en una sola dirección
- 📌 **Control de carga**: Identifica que "Finanzas" tiene 15 items vs "RRHH" con 3
- 📌 **Cumplimiento**: Ve que "Educación" tiene 80% de items al día

**Información Visualizada**:
- 📊 Cantidad de usuarios asignados
- 📊 Cantidad de items de transparencia
- 📊 % de cumplimiento mensual
- 📊 Items pendientes vs aprobados

---

##### C. GESTIÓN DE ITEMS DE TRANSPARENCIA
**¿Qué puede hacer?**
- ✅ Crear nuevos items según Ley 20.285
- ✅ Asignar numeración jerárquica (1, 1.1, 1.2, 2, 2.1, etc.)
- ✅ Definir periodicidad (mensual/trimestral/semestral/anual/ocurrencia)
- ✅ Asignar dirección responsable
- ✅ Asignar múltiples usuarios responsables por item
- ✅ Editar descripción y requisitos
- ✅ Activar/desactivar items temporalmente
- ✅ Ver historial de modificaciones

**Panel**: `/admin/items/index.php`

**Flujo de Trabajo Detallado**:
```
1. Click en "Items de Transparencia"
2. Ver árbol jerárquico de items (1 > 1.1 > 1.2)
3. Click "Nuevo Item":
   ├─ Numeración: 7a (Personal a contrata)
   ├─ Nombre: "Dotación de Personal a Contrata"
   ├─ Descripción: "Nómina mensual de personal a contrata..."
   ├─ Dirección: Seleccionar "RRHH"
   ├─ Periodicidad: "Mensual"
   └─ Día límite: 10 de cada mes
4. Click "Guardar"
5. Click "Asignar Usuarios" en el item creado
6. Seleccionar usuarios responsables de la carga
7. Usuarios ven el item en su dashboard automáticamente
```

**Casos de Uso Reales**:
- 📌 **Nueva obligación legal**: Agregan Art. 7j "Transferencias a personas jurídicas"
- 📌 **División de responsabilidades**: Item 7g antes lo cargaba 1 persona, ahora 3
- 📌 **Cambio de periodicidad**: Item que era anual ahora debe ser semestral
- 📌 **Desactivación temporal**: Item 7k en pausa por cambio normativo

**Estructura de Numeración**:
```
1. Marco normativo
   1.1 Leyes
   1.2 Reglamentos
2. Organización
   2.1 Estructura orgánica
   2.2 Autoridades
7. Obligaciones de transparencia activa
   7a. Personal
   7b. Contrataciones
   7c. Transferencias
   7d. Actos con efectos sobre terceros
   ...
```

---

##### D. CONFIGURACIÓN DE PLAZOS INTERNOS
**¿Qué puede hacer?**
- ✅ Configurar fecha de plazo interno por item/mes/año
- ✅ Configurar fecha de publicación en portal externo
- ✅ Ver calendario anual completo de plazos
- ✅ Establecer plazos diferentes por mes (ej: diciembre antes por fiestas)
- ✅ Copiar configuración de plazos de un año a otro
- ✅ Generar alertas automáticas X días antes del vencimiento

**Panel**: `/admin/items/plazos.php`

**Flujo de Trabajo**:
```
1. Click en "Gestión de Plazos"
2. Seleccionar Item (ej: "Personal a Contrata - Art. 7a")
3. Seleccionar Año: 2024
4. Ver tabla con 12 meses:
   ┌────────┬─────────────┬──────────────┬──────────┐
   │ Mes    │ Plazo Int.  │ Pub. Portal  │ Acciones │
   ├────────┼─────────────┼──────────────┼──────────┤
   │ Enero  │ 10/01/2024  │ 15/01/2024   │ [Editar] │
   │ Febrero│ 10/02/2024  │ 15/02/2024   │ [Editar] │
   │ ...    │ ...         │ ...          │ ...      │
   └────────┴─────────────┴──────────────┴──────────┘
5. Click "Editar" en un mes:
   ├─ Plazo Interno: [Calendario visual]
   ├─ Fecha Portal: [Calendario visual]
   └─ Notas: "Plazo extendido por feriado largo"
6. Click "Guardar"
7. Usuarios ven automáticamente el plazo en su dashboard
```

**Casos de Uso Reales**:
- 📌 **Ajuste por feriados**: Diciembre tiene plazo 5 días antes por año nuevo
- 📌 **Urgencia administrativa**: Acorta plazo interno para auditoría externa
- 📌 **Nuevo año**: Copia plazos de 2024 a 2025 con un click
- 📌 **Extensión excepcional**: Extiende plazo de marzo por paro de funcionarios

**Lógica de Alertas Automáticas**:
```
Si hoy es 5 días antes del plazo interno:
  ├─ Usuario recibe alerta en dashboard
  ├─ Email opcional al usuario
  └─ Badge rojo en menú principal

Si hoy es el día del vencimiento:
  ├─ Alerta crítica destacada
  └─ Notificación al administrativo

Si plazo vencido sin carga:
  ├─ Item marcado en rojo
  ├─ Dirección aparece en reporte de incumplimientos
  └─ Notificación a director de la dirección
```

---

##### E. REVISIÓN Y APROBACIÓN DE DOCUMENTOS
**¿Qué puede hacer?**
- ✅ Ver todos los documentos cargados del sistema
- ✅ Filtrar por estado (pendiente/aprobado/rechazado)
- ✅ Filtrar por dirección, item, fecha o usuario
- ✅ Descargar y revisar contenido del documento
- ✅ Aprobar documentos correctos
- ✅ Rechazar documentos con observaciones detalladas
- ✅ Ver historial completo de versiones de un documento
- ✅ Generar reportes de documentos revisados

**Panel**: `/admin/documentos/index.php`

**Flujo de Trabajo de Revisión**:
```
1. Click en "Documentos" (muestra badge con cantidad pendiente)
2. Ver tabla de documentos:
   ┌──────────┬────────┬────────┬────────┬──────────┬──────────┐
   │ Item     │ Usuario│ Período│ Estado │ Enviado  │ Acciones │
   ├──────────┼────────┼────────┼────────┼──────────┼──────────┤
   │ 7a Pers. │ Juan   │ Feb 24 │ 🟡 Pend│ 10/03/24 │ [Revisar]│
   │ 7b Contr.│ María  │ Feb 24 │ 🟢 Aprob│09/03/24 │ [Ver]    │
   │ 7g Trans.│ Pedro  │ Q1 24  │ 🔴 Rech│ 08/03/24 │ [Revisar]│
   └──────────┴────────┴────────┴────────┴──────────┴──────────┘
3. Click "Revisar" en documento pendiente
4. Modal muestra:
   ├─ Información del item
   ├─ Usuario que cargó
   ├─ Fecha/hora de carga
   ├─ Título del documento
   ├─ Descripción proporcionada
   ├─ [Botón Descargar Documento]
   └─ Vista previa (si es PDF/imagen)
5. Después de revisar contenido:
   
   OPCIÓN A: APROBAR
   ├─ Click "Aprobar Documento"
   ├─ Mensaje: "Documento aprobado" ✅
   ├─ Estado cambia a "Aprobado"
   ├─ Usuario recibe notificación
   └─ Documento aparece en panel de publicador
   
   OPCIÓN B: RECHAZAR
   ├─ Click "Rechazar Documento"
   ├─ Popup pide: "Motivo del rechazo" [textarea]
   ├─ Ejemplos:
   │  - "Formato incorrecto, debe ser Excel"
   │  - "Información incompleta, falta columna de RUT"
   │  - "Período equivocado, corresponde a enero no febrero"
   ├─ Click "Confirmar Rechazo"
   ├─ Estado cambia a "Rechazado"
   ├─ Usuario ve observaciones en su dashboard
   └─ Usuario puede cargar nueva versión
```

**Casos de Uso Reales**:
- 📌 **Documento correcto**: Revisa nómina de personal, todo OK, aprueba en 30 segundos
- 📌 **Error de formato**: Usuario cargó JPG cuando debe ser Excel, rechaza con observación
- 📌 **Información sensible**: Detecta RUT sin digito verificador enmascarado, rechaza
- 📌 **Período erróneo**: Usuario cargó datos de enero en vez de febrero, rechaza
- 📌 **Revisión masiva**: Filtra "pendientes" de última semana, revisa 10 documentos seguidos

**Criterios de Revisión**:
```
✅ Formato correcto (según lo definido en el item)
✅ Período correcto (mes/trimestre/año corresponde)
✅ Información completa (tiene todas las columnas requeridas)
✅ Sin datos sensibles expuestos (RUTs, datos personales protegidos)
✅ Legible y sin errores evidentes
✅ Cumple estándares de transparencia
```

---

##### F. REPORTES Y ESTADÍSTICAS
**¿Qué puede hacer?**
- ✅ Dashboard con KPIs principales
- ✅ Reporte de cumplimiento por dirección
- ✅ Reporte de items vencidos
- ✅ Reporte de documentos rechazados (con motivos)
- ✅ Gráficos de tendencias mensuales
- ✅ Exportar reportes a Excel/PDF
- ✅ Comparación año actual vs año anterior

**Panel**: `/admin/index.php` (Dashboard principal)

**Visualizaciones Disponibles**:
```
┌─ DASHBOARD ADMINISTRATIVO ───────────────────────────┐
│                                                        │
│  📊 INDICADORES CLAVE ESTE MES                        │
│  ┌─────────────┬─────────────┬─────────────┬────────┐│
│  │✅ Aprobados │⏳ Pendientes│🔴 Rechazados│📅 Vencer││
│  │     45      │      12     │      3      │    8   ││
│  └─────────────┴─────────────┴─────────────┴────────┘│
│                                                        │
│  📈 CUMPLIMIENTO POR DIRECCIÓN                        │
│  ┌────────────────────────┬─────────┬────────┐       │
│  │ RRHH                   │ 25/30   │ 83% ████│       │
│  │ Finanzas               │ 18/20   │ 90% █████│      │
│  │ Educación              │ 12/18   │ 67% ███ │       │
│  │ Salud                  │  5/8    │ 63% ███ │       │
│  └────────────────────────┴─────────┴────────┘       │
│                                                        │
│  ⚠️ ITEMS PRÓXIMOS A VENCER (5 días)                 │
│  • 7a Personal (RRHH) - Vence: 15/03/2024            │
│  • 7g Transferencias (Finanzas) - Vence: 16/03/2024  │
│  • 7b Contrataciones (Adquisiciones) - Vence: 17/03  │
│                                                        │
│  📉 TENDENCIA DE CUMPLIMIENTO (últimos 6 meses)      │
│  100%│     █                                          │
│   80%│   █ █ █                                        │
│   60%│ █ █ █ █ █ █                                    │
│      │ E F M A M J                                    │
│                                                        │
│  🔝 MEJORES CUMPLIMIENTOS: Finanzas (90%), RRHH (83%)│
│  ⚠️ REQUIEREN ATENCIÓN: Salud (63%), Educación (67%) │
└────────────────────────────────────────────────────────┘
```

**Casos de Uso Reales**:
- 📌 **Reunión con alcalde**: Exporta PDF con cumplimiento general del municipio
- 📌 **Identificar cuellos de botella**: Ve que Educación tiene 5 documentos rechazados repetidamente
- 📌 **Planificación**: Compara tendencia y ve que abril siempre baja cumplimiento
- 📌 **Auditoría interna**: Genera reporte de todos los documentos de 2024

---

##### G. AUDITORÍA Y LOGS
**¿Qué puede hacer?**
- ✅ Ver historial completo de acciones del sistema
- ✅ Filtrar logs por usuario, fecha o tipo de acción
- ✅ Investigar quién hizo qué cambio y cuándo
- ✅ Rastrear cadena de aprobaciones de un documento
- ✅ Identificar intentos de acceso no autorizados
- ✅ Exportar logs para auditoría externa

**Casos de Uso**:
- 📌 **Investigación**: ¿Quién cambió el plazo del item 7a el 10 de marzo?
- 📌 **Auditoría**: ¿Cuántos documentos aprobó María en febrero?
- 📌 **Seguridad**: ¿Hubo intentos de login fallidos desde IP externa?

---

**✨ BENEFICIOS CLAVE DEL PERFIL ADMINISTRATIVO**:
- 🎯 Control total del sistema desde un solo lugar
- 🎯 Visibilidad completa del cumplimiento institucional
- 🎯 Capacidad de tomar decisiones informadas con datos reales
- 🎯 Prevención proactiva de incumplimientos
- 🎯 Respuesta rápida ante fiscalizaciones
- 🎯 Optimización de recursos asignando usuarios eficientemente

---

### 2. 📝 **CARGADOR DE INFORMACIÓN** (Usuario Regular)

**Responsabilidad General**: Cargar documentos de transparencia en los plazos establecidos para los items asignados.

---

#### 📋 Funciones Principales

##### A. DASHBOARD PERSONALIZADO
**¿Qué puede hacer?**
- ✅ Ver solo los items que le fueron asignados
- ✅ Organizar items por periodicidad (pestañas)
- ✅ Consultar plazos de cada item
- ✅ Ver estado de sus documentos
- ✅ Recibir alertas de vencimientos próximos
- ✅ Filtrar por periodo (mes/trimestre/año)

**Panel**: `/usuario/dashboard.php`

**Interfaz Visual**:
```
┌─ MI PANEL DE CARGA ──────────────────────────────────┐
│                                                        │
│  Hola Juan Fica,                                      │
│  Tienes 8 items asignados                             │
│                                                        │
│  [📅 Mensual] [📊 Trimestral] [📈 Anual] [⚡Ocurrencia]│
│                                                        │
│  ─── PESTAÑA MENSUAL ACTIVA ───                       │
│                                                        │
│  Seleccionar período: [Febrero ▼] [2024 ▼]           │
│                                                        │
│  ┌─────┬──────────┬─────────┬────────┬─────────────┐ │
│  │Num  │ Item     │Mes Carga│Plazo   │Estado/Acción││
│  ├─────┼──────────┼─────────┼────────┼─────────────┤ │
│  │7a   │Personal  │Feb 2024 │10/03/24│✅ Aprobado  ││
│  │     │a contrata│         │        │[Ver Docs]   ││
│  ├─────┼──────────┼─────────┼────────┼─────────────┤ │
│  │7b   │Contrata- │Feb 2024 │15/03/24│🟡 Pendiente ││
│  │     │ciones    │         │(2 días)│[Ver Docs]   ││
│  ├─────┼──────────┼─────────┼────────┼─────────────┤ │
│  │7c   │Transf.   │Feb 2024 │15/03/24│❌ Sin enviar││
│  │     │bancarias │         │(2 días)│[Cargar Doc] ││
│  └─────┴──────────┴─────────┴────────┴─────────────┘ │
│                                                        │
│  ⚠️ ALERTAS: 2 items vencen en menos de 3 días       │
└────────────────────────────────────────────────────────┘
```

**Código de Colores**:
- 🟢 Verde: Documento aprobado (cumplimiento OK)
- 🟡 Amarillo: Documento pendiente de revisión
- 🔴 Rojo: Documento rechazado o plazo vencido
- ⚪ Blanco: Sin enviar aún (dentro de plazo)
- 🔵 Azul: Plazo próximo a vencer (menos de 3 días)

---

##### B. CARGA DE DOCUMENTOS
**¿Qué puede hacer?**
- ✅ Cargar archivos (PDF, Excel, Word, CSV, imágenes)
- ✅ Asignar título descriptivo al documento
- ✅ Agregar descripción opcional
- ✅ Ver confirmación inmediata de carga exitosa
- ✅ Cargar múltiples versiones si es rechazado
- ✅ Drag & drop (arrastrar archivo al navegador)

**Panel**: `/usuario/enviar_documento.php` (popup modal)

**Flujo de Carga Paso a Paso**:
```
PASO 1: Seleccionar item
├─ En dashboard, click botón [Cargar Documento]
├─ O navegar a "Enviar Documento" en menú
└─ Modal se abre automáticamente

PASO 2: Formulario de carga
┌─ CARGAR DOCUMENTO: 7a - Personal a Contrata ─┐
│                                                │
│  Item: 7a - Personal a Contrata [fijo]        │
│  Período: Febrero 2024 [auto-detectado]       │
│                                                │
│  Título del Documento: *                      │
│  [___________________________________]         │
│  Ej: "Nómina Personal Contrata Febrero 2024"  │
│                                                │
│  Descripción (opcional):                       │
│  [___________________________________]         │
│  [___________________________________]         │
│  Ej: "Incluye 45 funcionarios activos"        │
│                                                │
│  Archivo: *                                    │
│  [📎 Seleccionar archivo o arrastrar aquí]    │
│                                                │
│  Formatos: PDF, DOC, DOCX, XLS, XLSX, CSV,    │
│  JPG, PNG - Máximo 10 MB                      │
│                                                │
│  [Cancelar]  [✅ Enviar Documento]            │
└────────────────────────────────────────────────┘

PASO 3: Validaciones automáticas
├─ ✅ Título no vacío
├─ ✅ Archivo seleccionado
├─ ✅ Tamaño menor a 10 MB
├─ ✅ Formato permitido
└─ ✅ Item corresponde al usuario

PASO 4: Carga y procesamiento
├─ Barra de progreso visual
├─ Upload del archivo al servidor
├─ Registro en base de datos
├─ Generación de nombre único
└─ Registro en tabla de seguimiento

PASO 5: Confirmación
┌────────────────────────────────────────┐
│  ✅ DOCUMENTO ENVIADO EXITOSAMENTE     │
│                                        │
│  Tu documento ha sido recibido y está │
│  en proceso de revisión.               │
│                                        │
│  • Item: 7a Personal a Contrata       │
│  • Período: Febrero 2024               │
│  • Fecha envío: 13/03/2024 10:45 AM   │
│  • Archivo: nomina_feb_2024.xlsx       │
│                                        │
│  Recibirás notificación cuando sea     │
│  revisado.                             │
│                                        │
│  [Volver al Dashboard]                 │
└────────────────────────────────────────┘
```

**Casos de Uso Reales**:
- 📌 **Carga rutinaria**: El 10 de cada mes sube nómina de personal
- 📌 **Corrección rápida**: Documento rechazado, corrige y reenvía en 5 minutos
- 📌 **Carga múltiple**: Tiene 3 items que vencen el mismo día, carga uno tras otro
- 📌 **Distintos formatos**: Sube Excel para nómina, PDF para actas, JPG para certificados

**Mejores Prácticas Sugeridas**:
```
✅ TÍTULO DESCRIPTIVO:
   Bueno: "Nómina Personal Contrata - Febrero 2024"
   Malo: "documento.xlsx"

✅ DESCRIPCIÓN ÚTIL:
   Bueno: "45 funcionarios activos, incluye asignaciones"
   Malo: "archivo mensual"

✅ NOMBRAR ARCHIVO CLARO:
   Bueno: nomina_contrata_febrero_2024.xlsx
   Malo: doc1_final_v3.xlsx

✅ VERIFICAR ANTES DE ENVIAR:
   - ¿Es el período correcto?
   - ¿Tiene toda la información requerida?
   - ¿Formato solicitado?
   - ¿Sin datos sensibles expuestos?
```

---

##### C. CONSULTA DE ESTADO DE DOCUMENTOS
**¿Qué puede hacer?**
- ✅ Ver todos los documentos que ha enviado
- ✅ Consultar estado actual (pendiente/aprobado/rechazado)
- ✅ Leer observaciones de rechazo
- ✅ Ver fecha y hora de envío
- ✅ Ver quién revisó y cuándo
- ✅ Descargar documentos enviados anteriormente

**Vista en Dashboard**:
```
Click en [Ver Documentos] de un item:

┌─ HISTORIAL: 7a - Personal a Contrata ────────┐
│                                                │
│  Período: Febrero 2024                        │
│                                                │
│  ┌──────────┬───────────┬────────┬─────────┐ │
│  │ Enviado  │ Título    │ Estado │ Revisor  │ │
│  ├──────────┼───────────┼────────┼─────────┤ │
│  │10/03/24  │Nómina Feb │✅ Aprob│ Admin   │ │
│  │09:30 AM  │           │11/03/24│         │ │
│  │          │[Descargar]│        │         │ │
│  └──────────┴───────────┴────────┴─────────┘ │
│                                                │
│  Período: Enero 2024                          │
│                                                │
│  ┌──────────┬───────────┬────────┬─────────┐ │
│  │ Enviado  │ Título    │ Estado │ Revisor  │ │
│  ├──────────┼───────────┼────────┴─────────┤ │
│  │10/02/24  │Nómina Ene │🔴 Rechazado      │ │
│  │10:00 AM  │           │11/02/24          │ │
│  │          │[Descargar]│                   │ │
│  ├──────────┴───────────┤ 📝 OBSERVACIONES:│ │
│  │ "Formato incorrecto, debe ser Excel     │ │
│  │  con columnas: RUT, Nombre, Cargo,      │ │
│  │  Remuneración. Por favor reenviar."     │ │
│  └──────────────────────┴──────────────────┘ │
│                                                │
│  ┌──────────┬───────────┬────────┬─────────┐ │
│  │10/02/24  │Nómina Ene │✅ Aprob│ Admin   │ │
│  │14:30 PM  │CORREGIDO  │10/02/24│         │ │
│  │          │[Descargar]│        │         │ │
│  └──────────┴───────────┴────────┴─────────┘ │
│                                                │
│  [Cerrar]                                     │
└────────────────────────────────────────────────┘
```

---

##### D. GESTIÓN DE RECHAZOS
**¿Qué puede hacer?**
- ✅ Ver motivo detallado del rechazo
- ✅ Descargar documento rechazado para revisarlo
- ✅ Corregir errores identificados
- ✅ Reenviar versión corregida
- ✅ Ver historial de intentos

**Flujo cuando hay Rechazo**:
```
1. Usuario ingresa a dashboard
2. Ve badge rojo: "1 documento rechazado"
3. Item muestra: 🔴 RECHAZADO - Ver observaciones
4. Click para ver detalle:
   
   ┌──────────────────────────────────────────┐
   │  ⚠️ DOCUMENTO RECHAZADO                  │
   │                                          │
   │  Item: 7a Personal a Contrata            │
   │  Período: Febrero 2024                   │
   │  Enviado: 10/03/2024 09:30 AM            │
   │  Rechazado: 11/03/2024 02:15 PM          │
   │  Revisor: Administrador                  │
   │                                          │
   │  📝 MOTIVO DEL RECHAZO:                  │
   │  ┌────────────────────────────────────┐  │
   │  │ El archivo cargado está en formato │  │
   │  │ PDF, pero este item requiere un    │  │
   │  │ archivo Excel (.xlsx) con las      │  │
   │  │ siguientes columnas:               │  │
   │  │ - RUT                              │  │
   │  │ - Nombre Completo                  │  │
   │  │ - Cargo                            │  │
   │  │ - Remuneración Bruta                │  │
   │  │                                    │  │
   │  │ Por favor, corregir y reenviar.    │  │
   │  └────────────────────────────────────┘  │
   │                                          │
   │  [Descargar Doc. Rechazado]              │
   │  [🔄 Cargar Nueva Versión]              │
   └──────────────────────────────────────────┘

5. Usuario hace cambios necesarios
6. Click "Cargar Nueva Versión"
7. Sistema abre modal de carga pre-configurado
8. Envía documento corregido
9. Queda nuevamente "Pendiente" de revisión
```

**Casos de Rechazo Comunes**:
- 📌 **Formato incorrecto**: Cargó PDF cuando debe ser Excel
- 📌 **Información incompleta**: Faltan columnas requeridas
- 📌 **Período equivocado**: Subió datos de otro mes
- 📌 **Calidad ilegible**: Imagen escaneada muy borrosa
- 📌 **Datos sensibles**: RUT completo sin enmascarar

---

##### E. ALERTAS Y NOTIFICACIONES
**¿Qué recibe el usuario?**
- 🔔 Alerta en dashboard cuando plazo está próximo (3 días)
- 🔔 Badge numérico en menú con items pendientes
- 🔔 Email automático cuando documento es revisado (opcional)
- 🔔 Notificación visual cuando documento es rechazado
- 🔔 Mensaje de éxito cuando documento es aprobado

**Sistema de Alertas Visual**:
```
┌─ PANEL SUPERIOR ────────────────────────────┐
│  [🏠 Inicio] [📋 Dashboard (⚠️ 2)] [Enviar] │
│                                             │
│  ⚠️ ATENCIÓN: Tienes 2 items próximos a   │
│     vencer en menos de 3 días              │
│     [Ver detalles]                         │
└─────────────────────────────────────────────┘
```

---

**✨ BENEFICIOS CLAVE DEL PERFIL CARGADOR**:
- 🎯 Interfaz simple y enfocada solo en sus tareas
- 🎯 Claridad total sobre qué debe cargar y cuándo
- 🎯 Feedback inmediato sobre estado de documentos
- 🎯 No puede ver información de otros usuarios (privacidad)
- 🎯 Reducción de stress: sistema le recuerda vencimientos
- 🎯 Proceso de corrección claro y guiado

---

### 3. 👔 **DIRECTOR REVISOR** (Rol de Validación)

**Responsabilidad General**: Validar y aprobar documentos de su dirección antes de la publicación, asegurando calidad y exactitud de la información institucional.

---

#### 📋 Funciones Principales

##### A. PANEL DE REVISIÓN LIMITADO
**¿Qué puede hacer?**
- ✅ Ver documentos solo de su dirección asignada
- ✅ Revisar contenido antes que administrativo general
- ✅ Aprobar documentos de su área
- ✅ Rechazar con observaciones específicas
- ✅ Ver estadísticas de cumplimiento de su dirección
- ✅ Solicitar correcciones a sus funcionarios

**Acceso**: `/admin/documentos/index.php` (filtrado automático)

**Interfaz de Revisor**:
```
┌─ REVISIÓN DE DOCUMENTOS - DIRECCIÓN RRHH ───┐
│                                               │
│  📊 RESUMEN ESTA SEMANA                      │
│  • Pendientes de revisar: 5                  │
│  • Aprobados por mí: 12                      │
│  • Rechazados: 2                             │
│  • % Cumplimiento: 85%                       │
│                                               │
│  Filtros: [🟡 Solo Pendientes ▼] [Esta semana ▼]│
│                                               │
│  ┌─────┬─────────┬──────────┬────────┬─────┐│
│  │Item │Usuario  │Título    │Enviado │Acción││
│  ├─────┼─────────┼──────────┼────────┼─────┤│
│  │7a   │Juan F.  │Nómina Feb│10/03   │[Rev]││
│  │     │         │          │09:30   │     ││
│  ├─────┼─────────┼──────────┼────────┼─────┤│
│  │7b   │María G. │Contratos │11/03   │[Rev]││
│  │     │         │Febrero   │14:00   │     ││
│  └─────┴─────────┴──────────┴────────┴─────┘│
│                                               │
└───────────────────────────────────────────────┘
```

---

##### B. PROCESO DE VALIDACIÓN
**¿Qué valida?**
- ✅ Exactitud de la información (datos correctos)
- ✅ Completitud (no falta información)
- ✅ Cumplimiento de políticas internas
- ✅ Formato institucional (si aplica)
- ✅ Autorización necesaria (firma, V°B°)
- ✅ Confidencialidad (sin filtrar datos sensibles)

**Flujo de Validación**:
```
ETAPA 1: Recepción
├─ Usuario de su dirección carga documento
├─ Sistema notifica al Director Revisor
└─ Badge en menú: "Pendientes de revisar: 1"

ETAPA 2: Revisión Detallada
├─ Director abre panel de revisión
├─ Click en documento pendiente
├─ Modal muestra:
│  ├─ Información completa del item
│  ├─ Usuario que cargó
│  ├─ Fecha y hora
│  ├─ Título y descripción
│  ├─ Botón de descarga
│  └─ Vista previa (si es posible)
├─ Descarga y revisa contenido en detalle
└─ Verifica contra checklist interno

ETAPA 3: Decisión
OPCIÓN A - APROBAR:
├─ Todo correcto
├─ Click "Aprobar"
├─ Documento pasa al administrativo general
│  (segunda validación opcional)
└─ Usuario notificado del avance

OPCIÓN B - RECHAZAR:
├─ Detecta error o faltante
├─ Click "Rechazar"
├─ Escribe observación detallada:
│  "La nómina no incluye las asignaciones
│   de zona, que son obligatorias según
│   instructivo interno. Agregar columna
│   'Asig. Zona' y reenviar."
├─ Documento vuelve a usuario
└─ Usuario corrige y reenvía
```

**Checklist de Validación Típico**:
```
EJEMPLO: Nómina de Personal

□ ¿Incluye todos los funcionarios activos?
□ ¿Tiene las columnas requeridas por ley?
□ ¿Los montos son consistentes con planilla interna?
□ ¿RUTs están correctamente enmascarados?
□ ¿Está firmada digitalmente? (si aplica)
□ ¿Corresponde al mes/período correcto?
□ ¿Formato cumple estándar institucional?
□ ¿No hay datos extra que debieran ser privados?
```

---

##### C. APROBACIÓN CON OBSERVACIONES
**Característica especial**:
- ✅ Puede aprobar "con observaciones leves"
- ✅ Documento pasa pero queda nota interna
- ✅ Útil para no retrasar publicación por detalles menores

**Ejemplo**:
```
Item: 7a Personal a Contrata
Estado: ✅ Aprobado con observaciones

Observación del revisor:
"Aprobado. Nota: Para próximos meses, incluir
 columna de fecha de ingreso para mayor
 transparencia."

Acción: Documento se publica
        Usuario recibe sugerencia de mejora
        Para siguiente mes, incluye la columna
```

---

##### D. ESTADÍSTICAS DE SU DIRECCIÓN
**¿Qué puede ver?**
- ✅ Cumplimiento de su dirección vs otras
- ✅ Usuarios con mejor desempeño
- ✅ Usuarios que requieren apoyo
- ✅ Items más problemáticos (más rechazos)
- ✅ Tendencia mensual de cumplimiento

**Dashboard del Director**:
```
┌─ MI DIRECCIÓN: RRHH ──────────────────────────┐
│                                                │
│  📊 CUMPLIMIENTO ESTE MES: 85% ████████▌      │
│  Ranking: 2° de 8 direcciones                 │
│                                                │
│  👥 DESEMPEÑO POR USUARIO:                    │
│  ┌──────────────┬────────────┬──────────────┐ │
│  │ Usuario      │ Items Total│ Cumplimiento  │ │
│  ├──────────────┼────────────┼──────────────┤ │
│  │ Juan Fica    │ 3/3        │ 100% ██████████││
│  │ María Gómez  │ 2/3        │  67% ███████   ││
│  │ Pedro Soto   │ 1/2        │  50% █████     ││
│  └──────────────┴────────────┴──────────────┘ │
│                                                │
│  ⚠️ REQUIERE ATENCIÓN:                        │
│  • Pedro tiene 1 item vencido hace 3 días     │
│  • María tiene 1 documento rechazado 2 veces  │
│                                                │
│  💡 SUGERENCIA:                               │
│  Reunión con Pedro y María para revisar       │
│  dificultades y ofrecer apoyo                 │
│                                                │
└────────────────────────────────────────────────┘
```

---

##### E. ESCALACIÓN A ADMINISTRATIVO
**¿Cuándo escala?**
- Cuando el tema excede su competencia
- Cuando necesita cambiar configuración de item
- Cuando requiere extensión de plazo
- Cuando hay conflicto de personal

**Flujo**:
```
1. Director detecta problema estructural
2. No puede resolverlo en su nivel
3. Contacta a administrativo:
   - Por sistema (mensaje interno)
   - Por email registrado
   - Reunión presencial
4. Administrativo toma acción correctiva
5. Director es notificado de la solución
```

---

**✨ BENEFICIOS CLAVE DEL PERFIL DIRECTOR REVISOR**:
- 🎯 Control de calidad por quien conoce el área
- 🎯 Responsabilidad delegada apropiadamente
- 🎯 Descarga al administrativo central
- 🎯 Validación técnica especializada
- 🎯 Mejora la calidad de información publicada
- 🎯 Empodera a los directores de área

---

### 4. 📰 **PUBLICADOR** (Rol de Verificación)

**Responsabilidad General**: Publicar documentos aprobados en el portal externo de transparencia y cargar evidencia visual (verificadores) que acrediten la publicación efectiva.

---

#### 📋 Funciones Principales

##### A. PANEL DE DOCUMENTOS APROBADOS
**¿Qué puede hacer?**
- ✅ Ver todos los documentos aprobados pendientes de publicar
- ✅ Filtrar por dirección, item o fecha de aprobación
- ✅ Priorizar por fecha límite de publicación
- ✅ Marcar documentos como "en proceso de publicación"
- ✅ Descargar documentos para subir al portal externo

**Acceso**: `/admin/publicador/index.php`

**Interfaz del Publicador**:
```
┌─ PANEL DE PUBLICACIÓN ────────────────────────────────┐
│                                                         │
│  📊 RESUMEN                                            │
│  • Pendientes de publicar: 15                          │
│  • Publicados esta semana: 28                          │
│  • Verificadores pendientes: 3                         │
│                                                         │
│  Filtros: [Todas las direcciones ▼] [Este mes ▼]      │
│                                                         │
│  ┌──────┬──────────┬────────┬────────────┬──────────┐ │
│  │Item  │Título    │Aprob.  │Plazo Portal│Acción    │ │
│  ├──────┼──────────┼────────┼────────────┼──────────┤ │
│  │7a    │Nómina Feb│11/03   │15/03       │[Publicar]│ │
│  │      │Personal  │        │(4 días)    │[Descargar]││
│  ├──────┼──────────┼────────┼────────────┼──────────┤ │
│  │7b    │Contratos │10/03   │15/03       │[Publicar]│ │
│  │      │Febrero   │        │(4 días)    │[Descargar]││
│  ├──────┼──────────┼────────┼────────────┼──────────┤ │
│  │7g    │Transf. Q1│09/03   │20/03       │[Publicar]│ │
│  │      │2024      │        │(9 días)    │[Descargar]││
│  └──────┴──────────┴────────┴────────────┴──────────┘ │
│                                                         │
│  ⚠️ PRÓXIMOS A VENCER (menos de 5 días):              │
│  • 7a Personal a Contrata - 4 días                     │
│  • 7b Contrataciones - 4 días                          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

##### B. PROCESO DE PUBLICACIÓN PASO A PASO
**Flujo Completo**:

```
════════════════════════════════════════════════════
PASO 1: SELECCIONAR DOCUMENTO
════════════════════════════════════════════════════
│
├─ Publicador revisa panel de pendientes
├─ Prioriza por fecha límite
├─ Selecciona: "7a - Nómina Personal Contrata Feb"
└─ Click [Publicar]

════════════════════════════════════════════════════
PASO 2: DESCARGAR DOCUMENTO DEL SISTEMA
════════════════════════════════════════════════════
│
├─ Click [Descargar]
├─ Archivo se guarda localmente
├─ Verifica que descargó correctamente
└─ Tiene el archivo listo para subir

════════════════════════════════════════════════════
PASO 3: ACCEDER AL PORTAL EXTERNO
════════════════════════════════════════════════════
│
├─ Abre https://transparencia.ejemplo.gob.cl
├─ Login con credenciales institucionales
├─ Navega a sección correspondiente:
│  Ej: "Transparencia Activa" > "Personal"
└─ Ubica subsección correcta

════════════════════════════════════════════════════
PASO 4: SUBIR A PORTAL EXTERNO
════════════════════════════════════════════════════
│
├─ Click "Nuevo Documento" en portal
├─ Completa formulario del portal:
│  ├─ Título: "Personal a Contrata Febrero 2024"
│  ├─ Categoría: "Art. 7 letra a)"
│  ├─ Período: "Febrero 2024"
│  ├─ Archivo: [seleccionar archivo descargado]
│  └─ Descripción: (según requiere el portal)
├─ Click "Publicar" en el portal externo
└─ Documento ahora visible públicamente

════════════════════════════════════════════════════
PASO 5: CAPTURAR VERIFICADOR (EVIDENCIA)
════════════════════════════════════════════════════
│
├─ Con documento YA PUBLICADO en portal externo
├─ Tomar captura de pantalla que muestre:
│  ├─ URL del portal (barra de dirección visible)
│  ├─ Documento publicado
│  ├─ Fecha de publicación
│  └─ Título completo
│
├─ Herramientas sugeridas:
│  • Windows: Win + Shift + S
│  • Mac: Cmd + Shift + 4
│  • Extensión navegador: Full Page Screenshot
│
└─ Guardar imagen: verificador_7a_feb2024.png

════════════════════════════════════════════════════
PASO 6: SUBIR VERIFICADOR AL SISTEMA
════════════════════════════════════════════════════
│
├─ Volver al sistema interno
├─ En panel de publicador, click [Subir Verificador]
│
┌──────────────────────────────────────────────────┐
│  CARGAR VERIFICADOR                              │
│                                                  │
│  Item: 7a - Personal a Contrata                 │
│  Período: Febrero 2024                          │
│                                                  │
│  Fecha de Publicación en Portal: *              │
│  [__/__/____] 📅                                │
│  Ej: 13/03/2024                                 │
│                                                  │
│  URL del documento en portal: *                  │
│  [_____________________________________]         │
│  Ej: https://transparencia.ejemplo.gob.cl/...   │
│                                                  │
│  Imagen de Verificación: *                       │
│  [📷 Seleccionar captura de pantalla]           │
│  Formatos: JPG, PNG - Máximo 5 MB               │
│                                                  │
│  Observaciones (opcional):                       │
│  [_____________________________________]         │
│  Ej: "Publicado en sección Personal > Contrata" │
│                                                  │
│  [Cancelar] [✅ Confirmar Publicación]          │
└──────────────────────────────────────────────────┘
│
├─ Completar todos los campos
├─ Subir captura de pantalla
└─ Click "Confirmar Publicación"

════════════════════════════════════════════════════
PASO 7: CONFIRMACIÓN Y REGISTRO
════════════════════════════════════════════════════
│
├─ Sistema valida datos
├─ Guarda imagen de verificador
├─ Marca documento como "PUBLICADO"
├─ Registra:
│  ├─ Usuario publicador
│  ├─ Fecha/hora de registro
│  ├─ URL del portal
│  └─ Fecha efectiva de publicación
│
└─ Mensaje de éxito:
    "✅ Publicación registrada correctamente"

════════════════════════════════════════════════════
PASO 8: DOCUMENTO COMPLETA CICLO
════════════════════════════════════════════════════
│
├─ Item cambia a estado: ✅ PUBLICADO
├─ Aparece en reportes de cumplimiento
├─ Evidencia disponible para auditorías
├─ Administrativo ve en dashboard:
│  "7a Personal - Publicado: 13/03/2024"
└─ CICLO COMPLETO: ✅ Cargado → Aprobado → Publicado
```

---

##### C. GESTIÓN DE VERIFICADORES
**¿Qué es un verificador?**
- Captura de pantalla (screenshot) del portal externo
- Prueba visual de que el documento fue efectivamente publicado
- Evidencia para fiscalizaciones del CPLT
- Permite auditar que se cumplió la obligación legal

**Características del Verificador Ideal**:
```
✅ DEBE MOSTRAR:
├─ URL completa del portal en barra de navegación
├─ Fecha de publicación visible
├─ Título del documento claramente legible
├─ Sección/categoría donde fue publicado
└─ Preferiblemente: logo institucional

❌ NO SIRVE:
├─ Captura solo del archivo (sin contexto del portal)
├─ Imagen borrosa o ilegible
├─ Sin URL visible
└─ Captura antes de publicar (vista previa)
```

**Ejemplo Visual de Verificador**:
```
┌─────────────────────────────────────────────────────────┐
│ 🌐 https://transparencia.ejemplo.gob.cl/personal    [×] │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  [LOGO INSTITUCIONAL]    Portal de Transparencia       │
│                                                         │
│  Inicio > Transparencia Activa > Personal              │
│                                                         │
│  ══════════════════════════════════════════════════    │
│  📄 PERSONAL A CONTRATA - FEBRERO 2024                 │
│  ══════════════════════════════════════════════════    │
│                                                         │
│  Publicado: 13 de marzo de 2024                        │
│  Categoría: Art. 7 letra a)                            │
│  Formato: Excel (.xlsx)                                │
│  Tamaño: 156 KB                                        │
│                                                         │
│  [📥 Descargar Documento]                              │
│                                                         │
│  Descripción:                                          │
│  Nómina de personal a contrata activo durante el mes  │
│  de febrero de 2024. Incluye 45 funcionarios...       │
│                                                         │
└─────────────────────────────────────────────────────────┘
  ↑
  Esta captura completa es un VERIFICADOR VÁLIDO
```

---

##### D. VERIFICACIÓN DE PUBLICACIONES ANTERIORES
**¿Qué puede hacer?**
- ✅ Ver historial de todos los documentos publicados
- ✅ Consultar verificadores de meses anteriores
- ✅ Descargar evidencias para auditorías
- ✅ Verificar si URL sigue activa
- ✅ Identificar publicaciones faltantes

**Panel de Historial**:
```
┌─ HISTORIAL DE PUBLICACIONES ──────────────────────┐
│                                                    │
│  Filtros: [2024 ▼] [Febrero ▼] [Todas ▼]         │
│                                                    │
│  ┌──────┬────────────┬──────────┬──────────────┐ │
│  │Item  │Publicado   │Verificador│Acciones      │ │
│  ├──────┼────────────┼──────────┼──────────────┤ │
│  │7a    │13/03/2024  │✅ Si     │[Ver] [Descar]│ │
│  │      │10:30 AM    │          │              │ │
│  ├──────┼────────────┼──────────┼──────────────┤ │
│  │7b    │12/03/2024  │✅ Si     │[Ver] [Descar]│ │
│  │      │14:15 PM    │          │              │ │
│  ├──────┼────────────┼──────────┼──────────────┤ │
│  │7c    │11/03/2024  │⚠️ Pendiente│[Subir Verif]│ │
│  │      │09:00 AM    │          │              │ │
│  └──────┴────────────┴──────────┴──────────────┘ │
│                                                    │
│  ⚠️ 1 publicación sin verificador                │
│     Por favor, cargar evidencia                   │
│                                                    │
└────────────────────────────────────────────────────┘
```

---

##### E. REPORTES PARA FISCALIZACIÓN
**¿Qué puede generar?**
- ✅ Reporte de todos los items publicados en un período
- ✅ PDF con verificadores anexados
- ✅ Excel con URLs y fechas de publicación
- ✅ Evidencia completa para auditoría del CPLT

**Ejemplo de Reporte**:
```
═══════════════════════════════════════════════════
REPORTE DE CUMPLIMIENTO - FEBRERO 2024
Municipalidad de Ejemplo
═══════════════════════════════════════════════════

Item: 7a - Personal a Contrata
├─ Fecha carga interna: 10/03/2024
├─ Fecha aprobación: 11/03/2024
├─ Fecha publicación portal: 13/03/2024
├─ URL: https://transparencia.ejemplo.gob.cl/...
├─ Verificador: ✅ Adjunto (Imagen 1)
└─ Estado: CUMPLIDO ✅

Item: 7b - Contrataciones
├─ Fecha carga interna: 10/03/2024
├─ Fecha aprobación: 12/03/2024
├─ Fecha publicación portal: 12/03/2024
├─ URL: https://transparencia.ejemplo.gob.cl/...
├─ Verificador: ✅ Adjunto (Imagen 2)
└─ Estado: CUMPLIDO ✅

...

ANEXOS:
- Imagen 1: Verificador_7a_feb2024.png
- Imagen 2: Verificador_7b_feb2024.png
...

RESUMEN:
✅ Items publicados: 45/45 (100%)
✅ Verificadores: 45/45 (100%)
✅ En plazo: 43/45 (96%)
⚠️ Atrasados: 2/45 (4%)

Fecha emisión: 20/03/2024
Generado por: Sistema de Transparencia v2.0
═══════════════════════════════════════════════════
```

---

##### F. COORDINACIÓN CON OTRAS ÁREAS
**¿Con quién interactúa?**

**Con Administrativo**:
- Recibe documentos aprobados para publicar
- Reporta problemas en documentos aprobados
- Solicita extensiones de plazo si necesario
- Entrega reportes de cumplimiento

**Con Cargadores**:
- Solicita re-envío si archivo está corrupto
- Pide aclaraciones sobre contenido
- Notifica publicación exitosa

**Con TI/Webmaster del Portal Externo**:
- Coordina accesos al portal
- Reporta problemas técnicos
- Solicita creación de nuevas categorías
- Verifica funcionamiento de URLs

---

##### G. MANEJO DE SITUACIONES ESPECIALES

**Caso 1: Documento no sube al portal externo**
```
Problema: Portal externo rechaza archivo por tamaño
│
Solución:
├─ Publicador lo detecta
├─ Registra "En proceso" en sistema interno
├─ Contacta a usuario original
├─ Solicita archivo comprimido o formato alternativo
├─ Usuario reenvía versión optimizada
├─ Publicador vuelve a intentar
└─ Documenta incidencia
```

**Caso 2: Portal externo caído**
```
Problema: Portal de transparencia no disponible
│
Solución:
├─ Publicador registra intento fallido
├─ Notifica a administrativo
├─ Administrativo contacta proveed or técnico del portal
├─ Mientras tanto, documenta la incidencia
├─ Una vez resuelto, procede con publicación
└─ Adjunta evidencia de problema técnico (para justificar atraso)
```

**Caso 3: Verificador se perdió**
```
Problema: Publicación antigua sin verificador
│
Solución:
├─ Publicador accede al portal externo
├─ Busca el documento publicado
├─ Si aún existe: toma nueva captura
├─ Si no existe: registra como "No verificable"
├─ Notifica a administrativo para decisión
└─ Podría requerir re-publicación
```

---

**✨ BENEFICIOS CLAVE DEL PERFIL PUBLICADOR**:
- 🎯 Especialización en publicación efectiva
- 🎯 Separación de responsabilidades (carga vs publicación)
- 🎯 Evidencia irrefutable de cumplimiento
- 🎯 Protección ante fiscalizaciones
- 🎯 Trazabilidad completa del ciclo
- 🎯 Reducción de riesgo de multas por falta de pruebas

---

## 🔗 INTERACCIÓN ENTRE PERFILES

```
┌────────────────────────────────────────────────────┐
│         FLUJO COMPLETO DEL SISTEMA                 │
└────────────────────────────────────────────────────┘

1️⃣ ADMINISTRATIVO
   │ Configura sistema
   │ Crea usuarios
   │ Define items
   │ Asigna responsables
   │ Establece plazos
   ↓

2️⃣ CARGADOR
   │ Ve items en dashboard
   │ Carga documentos
   │ Espera revisión
   ↓

3️⃣ DIRECTOR REVISOR (opcional)
   │ Valida contenido
   │ Aprueba o rechaza
   │ Si rechaza → vuelve a CARGADOR
   │ Si aprueba → continúa ↓
   ↓

4️⃣ ADMINISTRATIVO
   │ Revisión final
   │ Aprueba o rechaza
   │ Si rechaza → vuelve a CARGADOR
   │ Si aprueba → continúa ↓
   ↓

5️⃣ PUBLICADOR
   │ Recibe documento aprobado
   │ Publica en portal externo
   │ Carga verificador
   │ Marca como PUBLICADO
   ↓

6️⃣ CUMPLIMIENTO COMPLETO ✅
   │ Evidencia documentada
   │ Trazabilidad total
   │ Preparado para auditoría
```

---

Esta estructura de perfiles garantiza **responsabilidades claras**, **validación en múltiples niveles** y **evidencia completa** de cumplimiento de transparencia activa

---

## 📋 Flujo de Trabajo Completo

```
┌─────────────────────────────────────────────────────────────────────┐
│                    ETAPA 1: CONFIGURACIÓN                           │
├─────────────────────────────────────────────────────────────────────┤
│ 1. Administrativo crea direcciones municipales                      │
│ 2. Administrativo define items de transparencia (1, 1.1, 1.2, etc.) │
│ 3. Administrativo asigna periodicidad (mensual/trimestral/etc.)     │
│ 4. Administrativo asigna usuarios responsables a cada item          │
│ 5. Administrativo configura plazos internos por mes/año             │
└─────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    ETAPA 2: CARGA DE INFORMACIÓN                    │
├─────────────────────────────────────────────────────────────────────┤
│ 6. Usuario ingresa a su dashboard                                   │
│ 7. Sistema muestra items asignados con plazos                       │
│ 8. Usuario selecciona mes/periodo (items mensuales = mes anterior)  │
│ 9. Usuario carga documento con título y descripción                 │
│ 10. Sistema registra fecha/hora de envío automáticamente            │
│ 11. Documento queda en estado "pendiente"                           │
└─────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    ETAPA 3: REVISIÓN Y VALIDACIÓN                   │
├─────────────────────────────────────────────────────────────────────┤
│ 12. Administrativo/Revisor accede a panel de documentos             │
│ 13. Filtra por estado, dirección, item o fecha                      │
│ 14. Revisa contenido del documento                                  │
│ 15. APRUEBA o RECHAZA con observaciones                             │
│ 16. Si rechaza: usuario recibe notificación y debe reenviar         │
│ 17. Si aprueba: documento pasa a "aprobado"                         │
└─────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    ETAPA 4: PUBLICACIÓN VERIFICADA                  │
├─────────────────────────────────────────────────────────────────────┤
│ 18. Publicador accede a panel de publicación                        │
│ 19. Ve listado de documentos aprobados pendientes de publicar       │
│ 20. Publica documento en portal externo de transparencia            │
│ 21. Toma captura de pantalla (verificador)                          │
│ 22. Carga verificador al sistema                                    │
│ 23. Sistema marca como "publicado" con fecha y evidencia            │
└─────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    ETAPA 5: AUDITORÍA Y CONTROL                     │
├─────────────────────────────────────────────────────────────────────┤
│ 24. Sistema mantiene historial completo de cambios                  │
│ 25. Log de quién hizo qué y cuándo                                  │
│ 26. Reportes de cumplimiento por dirección/item/periodo             │
│ 27. Alertas de items próximos a vencer                              │
│ 28. Dashboard con indicadores de cumplimiento                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ✨ Beneficios de Transparencia en Cada Etapa

### 🔵 ETAPA 1: Configuración

| Beneficio | Descripción |
|-----------|-------------|
| **Estandarización** | Todos los items siguen numeración oficial (Ley 20.285) |
| **Claridad de Responsabilidades** | Cada item tiene un usuario claramente asignado |
| **Planificación Anticipada** | Plazos configurados con anticipación evitan olvidos |
| **Trazabilidad de Asignaciones** | Historial de quién asignó cada item y cuándo |

**Impacto**: Organización clara desde el inicio, sin ambigüedades sobre qué debe publicarse y quién es responsable.

---

### 🟢 ETAPA 2: Carga de Información

| Beneficio | Descripción |
|-----------|-------------|
| **Centralización** | Un solo lugar para cargar todos los documentos |
| **Automatización de Fechas** | Sistema registra automáticamente fecha/hora de envío |
| **Visibilidad de Plazos** | Usuario ve claramente cuándo vence cada item |
| **Cálculo Automático** | Items mensuales muestran automáticamente mes anterior |
| **Formatos Estandarizados** | Control de tipos de archivo permitidos |
| **Límite de Tamaño** | Previene archivos excesivamente grandes |
| **Registro de Metadatos** | Título y descripción obligatorios para contextualizar |

**Impacto**: Usuarios saben exactamente qué cargar, cuándo y cómo. Reduce errores y omisiones.

---

### 🟡 ETAPA 3: Revisión y Validación

| Beneficio | Descripción |
|-----------|-------------|
| **Control de Calidad** | Documentos revisados antes de publicación |
| **Filtros Avanzados** | Búsqueda por estado, dirección, fecha, item |
| **Observaciones Documentadas** | Si se rechaza, el motivo queda registrado |
| **Ciclo de Corrección** | Usuario puede reenviar documento corregido |
| **Auditoría de Decisiones** | Quién aprobó/rechazó y cuándo |
| **Vista Previa** | Revisor puede ver documento sin descargar |
| **Historial Completo** | Todas las versiones y cambios registrados |

**Impacto**: Asegura que solo información correcta y completa llegue al público. Trazabilidad total de decisiones.

---

### 🔴 ETAPA 4: Publicación Verificada

| Beneficio | Descripción |
|-----------|-------------|
| **Evidencia Visual** | Captura de pantalla demuestra publicación efectiva |
| **Fecha de Publicación Real** | Se registra cuándo realmente se publicó |
| **Verificación Independiente** | Otro usuario valida que se publicó |
| **Cumplimiento Probado** | Ante fiscalización, hay evidencia documental |
| **Panel Específico** | Publicador tiene acceso solo a lo necesario |
| **Estado "Publicado"** | Claridad sobre qué está efectivamente en el portal |

**Impacto**: Prueba fehaciente de cumplimiento. Elimina riesgo de multas por falta de evidencia.

---

### 🟣 ETAPA 5: Auditoría y Control

| Beneficio | Descripción |
|-----------|-------------|
| **Historial Inmutable** | Registro permanente de todas las acciones |
| **Reportes Automáticos** | Dashboard con indicadores clave |
| **Alertas Tempranas** | Notificación de items próximos a vencer |
| **Métricas de Cumplimiento** | % de items al día por dirección |
| **Identificación de Rezagos** | Items atrasados destacados visualmente |
| **Base para Fiscalización** | Información organizada para auditorías |
| **Mejora Continua** | Análisis de tiempos y cuellos de botella |

**Impacto**: Visión estratégica del cumplimiento. Prevención proactiva de incumplimientos.

---

## 🎯 Ventajas Globales del Sistema

### Para la Institución

1. **Cumplimiento Legal Garantizado**
   - Seguimiento de todos los items obligatorios
   - Evidencia documental ante fiscalizaciones
   - Reducción de riesgo de multas (hasta 15 UTM por item incumplido)

2. **Eficiencia Operativa**
   - Automatización de recordatorios y cálculos
   - Eliminación de correos y Excel dispersos
   - Reducción de tiempo administrativo en un 60%

3. **Transparencia Interna**
   - Visibilidad de quién está al día y quién no
   - Identificación rápida de direcciones con rezago
   - Toma de decisiones basada en datos reales

4. **Auditabilidad Total**
   - Historial completo de cambios
   - Trazabilidad de responsabilidades
   - Preparación para fiscalizaciones del CPLT

---

### Para los Funcionarios

1. **Claridad de Tareas**
   - Saben exactamente qué deben cargar
   - Ven plazos claramente
   - No hay confusión sobre responsabilidades

2. **Facilidad de Uso**
   - Interfaz intuitiva estilo Instagram/Facebook
   - Carga de documentos arrastrar-soltar
   - Mensajes claros de éxito/error

3. **Retroalimentación Inmediata**
   - Estado de documentos en tiempo real
   - Observaciones claras si hay rechazo
   - Dashboard personalizado a su rol

---

### Para los Ciudadanos

1. **Acceso a Información Actualizada**
   - Sistema asegura publicación oportuna
   - Información completa y verificada
   - Cumplimiento de estándares de calidad

2. **Transparencia Real**
   - No solo publicación formal, sino verificación
   - Evidencia de que la información existe
   - Fechas reales de actualización

---

## 📊 Indicadores de Control de Transparencia

El sistema permite monitorear:

### Indicadores de Cumplimiento
- ✅ % de items publicados vs total obligatorio
- ⏱️ Tiempo promedio entre carga y publicación
- 📅 Items en plazo vs items atrasados
- 🔄 Tasa de rechazo por dirección
- 📈 Tendencia de cumplimiento mensual

### Indicadores de Eficiencia
- ⚡ Tiempo desde asignación hasta primera carga
- 🔁 Número de correcciones por documento
- 👥 Carga de trabajo por usuario
- 📊 Direcciones con mejor cumplimiento
- 🎯 Items críticos pendientes

### Indicadores de Calidad
- ✔️ Documentos aprobados en primera revisión
- 📝 Calidad de metadatos (títulos/descripciones)
- 🖼️ Verificadores cargados vs publicaciones
- 📆 Actualización en fecha correcta

---

## 🔐 Control de Acceso y Seguridad

### Autenticación
- Login con email y contraseña
- Contraseñas encriptadas con bcrypt (hash irreversible)
- Sesiones seguras con timeout automático
- Protección contra fuerza bruta

### Autorización
- Permisos por perfil de usuario
- Acceso granular a funcionalidades
- Validación en cada página (backend + frontend)
- Usuarios solo ven sus items asignados

### Auditoría
- Log de todos los accesos al sistema
- Registro de modificaciones con usuario y fecha
- IP y navegador registrados
- Histórico de cambios por documento

---

## 📚 Periodicidades Soportadas

El sistema maneja diferentes frecuencias de actualización según la ley:

| Periodicidad | Descripción | Ejemplo |
|--------------|-------------|---------|
| **Mensual** | Cada mes | Personal a contrata (Art. 7a) |
| **Trimestral** | Cada 3 meses | Transferencias (Art. 7g) |
| **Semestral** | Cada 6 meses | Auditorías (Art. 7m) |
| **Anual** | Una vez al año | Contrataciones (Art. 7b) |
| **Ocurrencia** | Cuando ocurre | Actas de concejo (Art. 7k) |

### Ventaja de Control
Cada periodicidad tiene su propia pestaña y lógica:
- **Mensuales**: Selector de mes, cálculo automático de mes anterior
- **Trimestrales**: Selector de trimestre (Q1, Q2, Q3, Q4)
- **Ocurrencia**: Carga libre sin selector de periodo

---

## 🔄 Sistema de Historial

### Tabla `historial_cambios`
Registra automáticamente:
- Quién hizo el cambio (usuario)
- Qué cambió (tabla y registro afectado)
- Cuándo se hizo (timestamp)
- Qué acción (CREATE, UPDATE, DELETE, APPROVE, REJECT)
- Valores anteriores y nuevos (JSON)

### Casos de Uso
- **Auditoría**: ¿Quién aprobó este documento?
- **Investigación**: ¿Cuándo se cambió este item?
- **Recuperación**: ¿Qué decía antes este campo?
- **Compliance**: Demostrar trazabilidad completa

---

## 🎨 Interfaz y Experiencia de Usuario

### Diseño Moderno
- Bootstrap 5.3 con tema personalizado
- Iconos de Bootstrap Icons
- Diseño responsivo (móvil, tablet, desktop)
- Colores institucionales adaptables

### Experiencia Intuitiva
- **Dashboard con badges de color**:
  - 🟢 Verde: Documento aprobado
  - 🔴 Rojo: Documento rechazado
  - 🟡 Amarillo: Pendiente de revisión
  - 🔵 Azul: Plazo próximo a vencer

- **Modales de confirmación**: Evitan acciones accidentales
- **Tooltips explicativos**: Ayuda contextual
- **Mensajes de feedback**: Éxito/error claramente diferenciados
- **Tablas con búsqueda**: Filtrado instantáneo
- **Carga drag & drop**: Arrastra archivo al navegador

---

## 📈 Casos de Éxito Esperados

### Antes del Sistema
❌ Documentos en correos perdidos  
❌ Excel dispersos sin control  
❌ No se sabe quién es responsable de qué  
❌ Olvidos frecuentes de items  
❌ Multas por falta de evidencia  
❌ Horas perdidas buscando información  
❌ Conflictos por responsabilidades no claras  

### Después del Sistema
✅ Toda la información centralizada  
✅ Control total de responsabilidades  
✅ Alertas automáticas antes de vencer  
✅ Evidencia documental completa  
✅ Cumplimiento legal garantizado  
✅ Tiempo administrativo reducido 60%  
✅ Claridad total para funcionarios  

---

## 🚀 Implementación y Despliegue

### Requisitos Técnicos
- **Servidor**: PHP 7.4+, MySQL 5.7+
- **Espacio**: 500 MB inicial (crece con documentos)
- **Backup**: Diario de base de datos
- **SSL**: Recomendado para producción

### Proceso de Puesta en Marcha
1. **Instalación**: Script automático `setup.php`
2. **Configuración**: Crear direcciones e items
3. **Usuarios**: Crear cuentas y asignar perfiles
4. **Capacitación**: Guías de usuario incluidas
5. **Prueba**: Período de prueba con datos reales
6. **Producción**: Activación completa

### Mantenimiento
- Backup automático configurado
- Limpieza de archivos huérfanos
- Actualización de plazos mensual/anual
- Revisión de usuarios activos

---

## 📞 Soporte y Documentación

### Documentos Incluidos
- `README.md` - Documentación técnica
- `GUIA_USUARIOS.md` - Manual para usuarios finales
- `GUIA_COMPLETA_V2.md` - Guía de administración
- `ESTRUCTURA_PERFILES.md` - Roles y permisos
- `DASHBOARD_NUEVO.md` - Uso del dashboard
- `IMPLEMENTACION_PUBLICADOR.md` - Panel de publicación

### Archivos de Diagnóstico
- `verificacion_sistema.php` - Verifica configuración
- `diagnostico_archivos.php` - Revisa archivos subidos
- `test_dashboard_query.php` - Prueba consultas

---

## 📚 GUÍA DE CAPACITACIÓN POR PERFIL

### Capacitación para Cargadores de Información (2 horas)

#### **MÓDULO 1: Introducción al Sistema (30 min)**

**Objetivos de Aprendizaje**:
- Entender qué es transparencia activa
- Conocer obligaciones legales básicas
- Comprender el rol del cargador en el proceso

**Contenido**:
```
1. ¿Qué es Transparencia Activa? (10 min)
   • Ley 20.285 explicada en lenguaje simple
   • Diferencia entre transparencia activa y pasiva
   • Consecuencias del incumplimiento
   • Importancia del rol de cada funcionario

2. Tour del Sistema (15 min)
   • Login y cambio de contraseña
   • Navegación por el dashboard
   • Interpretación de colores y estados
   • Dónde buscar ayuda

3. Preguntas y Respuestas (5 min)
```

#### **MÓDULO 2: Carga de Documentos Paso a Paso (45 min)**

**Práctica guiada**:
```
EJERCICIO 1: Cargar un Documento Simple (15 min)
├─ Instructor muestra en proyector
├─ Participantes siguen en sus computadores
├─ Item de prueba: "Ejemplo - Personal"
├─ Documento de práctica proporcionado
└─ Verificar carga exitosa

EJERCICIO 2: Interpretar Estados de Documentos (10 min)
├─ Ver documento pendiente
├─ Ver documento aprobado
├─ Simular documento rechazado
├─ Leer observaciones
└─ Comprender próximo paso en cada caso

EJERCICIO 3: Corregir un Documento Rechazado (15 min)
├─ Instructor "rechaza" documento de prueba
├─ Participantes leen observaciones
├─ Corrigen el archivo
├─ Reenvían versión corregida
└─ Verifican que queda "pendiente" nuevamente

PREGUNTAS FRECUENTES (5 min)
Q: ¿Qué hago si no tengo la información?
A: Contactar a tu jefe inmediato y al administrativo

Q: ¿Puedo cargar antes del plazo?
A: ¡Sí! Entre más temprano, mejor

Q: ¿Qué pasa si olvido cargar?
A: Recibirás alertas, pero afecta cumplimiento institucional
```

#### **MÓDULO 3: Mejores Prácticas y Errores Comunes (30 min)**

**Checklist de Buenas Prácticas**:
```
✅ ANTES DE CARGAR:
1. Verificar que tengo la información completa
2. Revisar que el formato es el solicitado
3. Verificar que el período es el correcto
4. Chequear que no hay datos sensibles
5. Probar que el archivo abre correctamente

✅ AL CARGAR:
1. Título descriptivo y claro
2. Descripción breve pero informativa
3. Verificar que subió el archivo correcto
4. Esperar confirmación de éxito

✅ DESPUÉS DE CARGAR:
1. Anotar en agenda que se cargó
2. Revisar estado en los próximos días
3. Si rechazado, corregir de inmediato
4. Si aprobado, LISTO hasta próximo período
```

**Errores Comunes y Cómo Evitarlos**:
```
ERROR 1: "Cargué archivo del mes equivocado"
├─ Consecuencia: Rechazo seguro
├─ Prevención: Verificar SIEMPRE el período antes de enviar
└─ Solución: Nombrar archivos con fecha: "nomina_febrero_2024.xlsx"

ERROR 2: "No puedo abrir el archivo que subí"
├─ Consecuencia: Rechazo por archivo corrupto
├─ Prevención: Probar abrirlo ANTES de subirlo
└─ Solución: Regenerar archivo, no usar versiones antiguas

ERROR 3: "Olvidé cargo documentos"
├─ Consecuencia: Item atrasado, afecta indicadores
├─ Prevención: Configurar recordatorios en calendario personal
└─ Solución: Cargar inmediatamente, avisar al administrativo

ERROR 4: "Subí información confidencial"
├─ Consecuencia: GRAVE - Violación de privacidad
├─ Prevención: Siempre enmascarar RUTs (XX-X), eliminar datos bancarios
└─ Solución: Avisar URGENTE al administrativo para eliminar
```

#### **MÓDULO 4: Evaluación Práctica (15 min)**

**Ejercicio Final**:
Cada participante debe completar exitosamente:
1. Login al sistema ✅
2. Identificar un item pendiente en su dashboard ✅
3. Cargar un documento de práctica ✅
4. Verificar confirmación de carga ✅
5. Ver el documento en su historial ✅

**Certificación**:
- Aprobación: 5/5 pasos completados
- Usuario habilitado para uso real del sistema

---

### Capacitación para Revisores (1.5 horas)

#### **MÓDULO 1: Rol del Revisor (20 min)**

**Responsabilidades**:
```
✅ Validar calidad de documentos
✅ Asegurar cumplimiento normativo
✅ Proteger datos personales
✅ Aprobar documentos correctos rápidamente
✅ Rechazar con observaciones constructivas
❌ NO es rol del revisor: Generar la información
```

**Criterios de Validación**:
```
CRITERIO 1: Formato Correcto
├─ ¿El tipo de archivo es el solicitado?
├─ ¿El archivo no está corrupto?
└─ ¿Es legible y profesional?

CRITERIO 2: Información Completa
├─ ¿Tiene todos los campos requeridos?
├─ ¿No hay filas o columnas vacías sin justificación?
└─ ¿La cantidad de datos es coherente?

CRITERIO 3: Período Correcto
├─ ¿El documento corresponde al mes/año indicado?
├─ ¿La fecha de referencia es correcta?
└─ ¿No es un documento de otro período?

CRITERIO 4: Protección de Datos
├─ ¿Los RUTs están enmascarados (XX-X)?
├─ ¿No hay datos bancarios completos?
├─ ¿No hay información médica o sensible?
└─ ¿Cumple con Ley 19.628 de protección de datos?

CRITERIO 5: Calidad General
├─ ¿Los datos son realistas y coherentes?
├─ ¿No hay errores ortográficos graves?
└─ ¿Es publicable sin vergüenza institucional?
```

#### **MÓDULO 2: Proceso de Revisión Eficiente (40 min)**

**Técnica de Revisión Rápida** (objetivo: 2-3 min/documento):
```
SEGUNDO 0-15: Revisar metadatos
├─ ¿Usuario correcto?
├─ ¿Item correcto?
├─ ¿Período correcto?
└─ Si todo OK → Continuar. Si no → Rechazar de inmediato

SEGUNDO 15-30: Descargar y abrir archivo
├─ Click descargar
├─ Abrir en programa correspondiente (Excel/PDF/Word)
└─ Si no abre → Rechazar "archivo corrupto"

SEGUNDO 30-120: Revisión de contenido
├─ Verificar estructura (columnas/secciones)
├─ Sampleo de datos (revisar 5-10 filas aleatorias)
├─ Verificar enmascaramiento RUT
├─ Buscar flags rojos (celdas vacías, errores, #REF!)
└─ Decisión: ¿APROBAR o RECHAZAR?

SEGUNDO 120-180: Registrar decisión
├─ Si aprueba: Click [Aprobar] → Listo
├─ Si rechaza: Escribir observación CLARA y ESPECÍFICA
└─ Confirmar decisión
```

**Casos Prácticos**:
```
CASO A: Nómina de Personal
Archivo: nomina_febrero_2024.xlsx
Revisión:
├─ ✅ Formato Excel correcto
├─ ✅ Columnas: RUT, Nombre, Cargo, Remuneración
├─ ✅ 45 filas (coherente con descripción)
├─ ✅ RUTs enmascarados: 12.345.XXX-X
├─ ✅ Montos realistas ($500.000 - $2.500.000)
└─ DECISIÓN: ✅ APROBAR (tiempo: 2 min)

CASO B: Contratos Vigentes
Archivo: contratos_enero_2024.pdf
Revisión:
├─ ⚠️ Formato PDF correcto
├─ ⚠️ Pero item requiere Excel editable
├─ ❌ No cumple requisito de formato
└─ DECISIÓN: ❌ RECHAZAR
    Observación: "El archivo debe ser Excel (.xlsx)
    para permitir análisis de datos. PDF no es
    editable ni cumple con estándares de datos
    abiertos. Por favor exportar a Excel y reenviar."
    
CASO C: Presupuesto Mensual
Archivo: presupuesto_feb_2024.xlsx
Revisión:
├─ ✅ Formato Excel correcto
├─ ✅ Hoja 1: Ingresos (OK)
├─ ✅ Hoja 2: Gastos (OK)
├─ ⚠️ PERO: Período dice "Enero 2024" en celdas
├─ ❌ Inconsistencia de período
└─ DECISIÓN: ❌ RECHAZAR
    Observación: "El archivo está bien estructurado,
    pero las celdas B2:B5 dicen 'Enero 2024' cuando
    deberían decir 'Febrero 2024'. Por favor
    corregir el período de referencia y reenviar."
```

#### **MÓDULO 3: Comunicación Efectiva en Rechazos (30 min)**

**Principios de Comunicación**:
```
✅ SER ESPECÍFICO:
Mal:  "El archivo está mal"
Bien: "Falta columna 'Fecha de Nacimiento' en la nómina"

✅ SER CONSTRUCTIVO:
Mal:  "Esto no sirve, hazlo de nuevo"
Bien: "El formato debe ser Excel, no PDF. Convertir y reenviar."

✅ SER EDUCATIVO:
Mal:  "Rechazado"
Bien: "Los RUTs deben estar enmascarados (ej: 12.345.XXX-X)
      para proteger datos personales según Ley 19.628.
      Aplicar formato y reenviar."

✅ OFRECER AYUDA:
Mal:  "Arréglalo tú"
Bien: "Si tienes dudas sobre cómo generar el Excel, contáctame
      al ext. 245 o transparencia@institucion.gob.cl"
```

**Plantillas de Rechazo**:
```
PLANTILLA 1: Formato Incorrecto
"El archivo cargado está en formato [PDF], sin embargo este
item requiere formato [Excel .xlsx] editable.

Por favor:
1. Abrir el archivo Excel original
2. Guardar como > Excel (.xlsx)
3. Reenviar usando el botón 'Cargar Documento'

Formato correcto: .xlsx (NO .xls, NO .pdf)"

PLANTILLA 2: Información Incompleta
"El documento está incompleto. Faltan los siguientes campos
requeridos:
• [Columna "Fecha de Ingreso"]
• [Columna "Horas Semanales"]

Por favor agregar estas columnas y reenviar.
Ver descripción del item para más detalles."

PLANTILLA 3: Período Incorrecto
"El archivo corresponde a [Enero 2024], pero el período
solicitado es [Febrero 2024].

Por favor verificar:
1. Que el archivo contenga datos de FEBRERO
2. Que el nombre/título refleje el período correcto
3. Reenviar archivo del mes correspondiente"

PLANTILLA 4: Datos Sensibles
"⚠️ URGENTE: El documento contiene datos personales sin
protección adecuada:
• RUTs completos (deben ser XX-X)
• [Otro dato sensible identificado]

Por razones de protección de datos personales, este documento
NO PUEDE ser publicado en su forma actual.

Acción requerida:
1. Enmascarar los 6 dígitos centrales del RUT: 12.345.XXX-X
2. [Otra acción correctiva]
3. Reenviar versión corregida

Cualquier duda, contactar: [email/teléfono]"
```

---

## 🆘 SOLUCIÓN DE PROBLEMAS (TROUBLESHOOTING)

### Problemas Comunes y Soluciones

#### **PROBLEMA 1: No puedo hacer login**

**Síntoma**: Mensaje "Usuario o contraseña incorrectos"

**Diagnóstico**:
```
CHECK 1: ¿Estás usando el email correcto?
├─ Debe ser email corporativo registrado
├─ Verificar si tiene mayúsculas/minúsculas
└─ Probar con/sin @dominio.gob.cl

CHECK 2: ¿La contraseña es correcta?
├─ ¿Tiene mayúsculas y minúsculas?
├─ ¿Tiene números o caracteres especiales?
├─ ¿Estás escribiendo en el teclado correcto? (español/inglés)
└─ Probar copiar/pegar la contraseña

CHECK 3: ¿Tu usuario está activo?
├─ Contactar al administrativo
└─ Verificar que no fue desactivado
```

**Solución**:
```
SOLUCIÓN A: Recuperar contraseña
1. Click en "¿Olvidaste tu contraseña?"
2. Ingresar email
3. Revisar correo electrónico (inbox y spam)
4. Click en enlace de recuperación
5. Crear nueva contraseña

SOLUCIÓN B: Contactar administrativo
1. Email a: admin@institucion.gob.cl
2. Asunto: "Problema de acceso al sistema - [Tu nombre]"
3. Mensaje: "No puedo acceder al sistema de transparencia.
   Mi email registrado es: [tu_email]@institucion.gob.cl"
4. Esperar respuesta (máx. 24 horas hábiles)
```

---

#### **PROBLEMA 2: El archivo no se sube / Upload falla**

**Síntoma**: Barra de progreso se queda en 0% o error al cargar

**Diagnóstico**:
```
CHECK 1: ¿Tamaño del archivo?
├─ Sistema acepta máximo 10 MB
├─ Ver propiedades del archivo
└─ Si es mayor: Comprimir o dividir

CHECK 2: ¿Formato del archivo?
├─ Extensión permitidas: .pdf, .xlsx, .docx, .csv, .jpg, .png
├─ Verificar extensión real (no solo nombre)
└─ Probar "Guardar como" en formato diferente

CHECK 3: ¿Conexión a Internet?
├─ ¿Otros sitios web cargan?
├─ ¿WiFi está conectado?
└─ Probar recargar la página

CHECK 4: ¿Navegador actualizado?
├─ Chrome 90+
├─ Firefox 88+
└─ Edge 90+
```

**Solución**:
```
SOLUCIÓN A: Reducir tamaño del archivo
1. Si es PDF con imágenes:
   ├─ Adobe Acrobat: File > Reduce File Size
   ├─ Online: ilovepdf.com/compress_pdf
   └─ Objetivo: < 10 MB

2. Si es Excel muy grande:
   ├─ Eliminar hojas no necesarias
   ├─ Eliminar imágenes embebidas
   ├─ Guardar como: Excel Binary (.xlsb)
   └─ O dividir en múltiples archivos

3. Si es imagen:
   ├─ Reducir resolución a 150 DPI
   ├─ Comprimir con TinyPNG.com
   └─ O convertir a PDF liviano

SOLUCIÓN B: Cambiar navegador
1. Si estás en Internet Explorer → Cambiar a Chrome
2. Si no funciona en Chrome → Probar Firefox
3. Si no funciona en ninguno → Problema de red/firewall

SOLUCIÓN C: Usar otro computador/red
1. Probar desde otro computador
2. Probar con datos móviles (no WiFi institucional)
3. Si funciona → Reportar problema de red a TI
```

---

#### **PROBLEMA 3: Mi documento fue rechazado pero no entiendo por qué**

**Síntoma**: Estado "Rechazado" con observaciones confusas

**Solución**:
```
PASO 1: Leer observaciones completas
├─ Dashboard → Click [Ver Observaciones]
├─ Leer detenidamente TODO el mensaje
└─ Anotar los puntos específicos mencionados

PASO 2: Comparar con descripción del item
├─ Ver descripción detallada del item
├─ Verificar qué se solicita exactamente
├─ Identificar qué te falta o está incorrecto
└─ Hacer checklist de correcciones

PASO 3: Corregir punto por punto
├─ Si dice "falta columna X" → Agregar columna X
├─ Si dice "formato incorrecto" → Cambiar a formato solicitado
├─ Si dice "período equivocado" → Usar archivo del mes correcto
└─ NO enviar el mismo archivo sin cambios

PASO 4: Si AÚN no entiendes
├─ Contactar al revisor (email proporcionado en observaciones)
├─ O llamar a unidad de transparencia
├─ Explicar: "Leí las observaciones pero necesito ayuda con [X]"
└─ Solicitar reunión corta o videollamada si es necesario

PASO 5: Reenviar versión corregida
├─ Cargar documento corregido
├─ En descripción mencionar: "Versión corregida según observaciones"
└─ Esperar nueva revisión (generalmente más rápida la segunda vez)
```

---

#### **PROBLEMA 4: No veo mis items asignados en el dashboard**

**Síntoma**: Dashboard aparece vacío o con mensaje "No tienes items asignados"

**Diagnóstico**:
```
CHECK 1: ¿Eres usuario nuevo?
├─ Tal vez aún no te han asignado items
└─ Esperar a que administrativo configure asignaciones

CHECK 2: ¿Cambiaste de dirección recientemente?
├─ Las asignaciones anteriores pueden haberse perdido
└─ Solicitar re-asignación

CHECK 3: ¿Los items están desactivados?
├─ Administrativo puede haber desactivado items temporalmente
└─ Consultar con administrativo
```

**Solución**:
```
SOLUCIÓN A: Verificar perfil
1. Dashboard → Nombre usuario (esquina superior derecha)
2. Ver: "Dirección: [¿Cual aparece?]"
3. Si está vacío o incorrecto: Reportar a administrativo

SOLUCIÓN B: Solicitar asignación
Email al administrativo:
---
Asunto: Solicitud de asignación de items - [Tu Nombre]

Hola,

Ingresé al sistema de transparencia pero mi dashboard
aparece sin items asignados.

Datos:
• Nombre: [Tu nombre completo]
• Email: [tu_email]@institucion.gob.cl
• Dirección: [Tu dirección]
• Items que debería tener: [listar si los conoces]

¿Podrías revisar y asignarme los items correspondientes?

Gracias,
[Tu nombre]
---

SOLUCIÓN C: Verificar fechas
├─ Si es inicio de mes: Tal vez items del mes anterior ya cerraron
├─ Items mensuales: Solo aparecen en período activo
└─ Esperar a que se active el nuevo período
```

---

## 💡 MEJORES PRÁCTICAS INSTITUCIONALES

### Para Maximizar el Cumplimiento

#### **1. Estrategia de Plazos Escalonados**

En vez de que todos los items venzan el día 15:
```
ESTRATEGIA TRADICIONAL (NO recomendada):
└─ Todos los items vencen: 15 de cada mes
    ├─ Problema: Pico de trabajo el día 10-15
    ├─ Problema: Revisores saturados
    └─ Problema: Errores por prisa

ESTRATEGIA ESCALONADA (✅ Recomendada):
├─ Items de RRHH: Vencen día 8
├─ Items de Finanzas: Vencen día 10
├─ Items de Educación: Vencen día 12
├─ Items de Salud: Vencen día 14
└─ Items de Obras: Vencen día 16

BENEFICIOS:
✅ Carga de trabajo distribuida
✅ Revisores pueden enfocarse dirección por dirección
✅ Menos errores por prisa
✅ Mayor cumplimiento general
```

---

#### **2. Reuniones de Coordinación Mensuales**

**Agenda sugerida** (30 minutos):
```
1. Revisión de cumplimiento del mes anterior (10 min)
   ├─ % de cumplimiento alcanzado
   ├─ Items que tuvieron problemas
   └─ Reconocimiento a usuarios destacados

2. Anticipación del mes entrante (10 min)
   ├─ Items especiales del mes (ej: enero = anuales)
   ├─ Cambios normativos que afecten items
   └─ Plazos críticos

3. Resolución de problemas recurrentes (5 min)
   ├─ Si item X siempre se atrasa: ¿Por qué?
   ├─ ¿Falta capacitación?
   └─ ¿Plazo es irreal?

4. Preguntas abiertas (5 min)
   └─ Espacio para que usuarios planteen dudas
```

---

#### **3. Sistema de Incentivos (Opcional)**

**Reconocimiento Público**:
```
MENSUAL:
├─ "Dirección del Mes": 100% de cumplimiento
├─ Publicar en intranet o boletín interno
└─ Certificado de reconocimiento del alcalde/director

TRIMESTRAL:
├─ "Usuario Destacado": Mejor historial de cumplimiento
├─ Reconocimiento en reunión de departamento
└─ Considerar para evaluaciones de desempeño

ANUAL:
├─ Premio "Excelencia en Transparencia"
├─ Día libre adicional (si política institucional lo permite)
└─ Capacitación externa pagada (curso/seminario)
```

---

#### **4. Backup de Responsables**

**Problema común**: Usuario de vacaciones = item no se carga

**Solución**: Sistema de binomios
```
ITEM: 7a.2 Personal a Contrata
├─ Responsable PRINCIPAL: María González
└─ Responsable BACKUP: Pedro Soto

REGLA:
Si María está de vacaciones/licencia:
├─ Pedro automáticamente ve el item en su dashboard
├─ Pedro carga el documento
└─ Sistema registra: "Cargado por backup"

BENEFICIOS:
✅ Cero interrupciones por vacaciones
✅ Continuidad del servicio
✅ Cumplimiento garantizado
```

---

#### **5. Documentación de Procesos Internos**

Cada dirección debe tener un **Manual de Procedimientos** propio:
```
EJEMPLO: Manual RRHH para Item 7a.2
═══════════════════════════════════

ITEM: 7a.2 - Personal a Contrata
RESPONSABLE: María González (principal), Pedro Soto (backup)
FRECUENCIA: Mensual
PLAZO: Día 8 de cada mes

PASO A PASO INTERNO:
1. Día 1: Exportar nómina desde sistema X
   • Menú: Remuneraciones > Exportar > Personal Contrata
   • Filtro: Mes anterior
   • Formato: Excel

2. Día 2: Limpiar datos sensibles
   • Enmascarar RUTs: columna A aplicar fórmula =ENMASCARAR()
   • Eliminar columnas: Banco, Cuenta, Domicilio
   • Verificar totales

3. Día 3: Revisión interna
   • Jefe RRHH revisa archivo
   • Firma digital (si aplica)
   • Autoriza carga

4. Día 5: Carga al sistema
   • Login: https://cumplimiento.institucion.gob.cl
   • Dashboard > 7a.2 > [Cargar]
   • Título: "Personal Contrata - [Mes] [Año]"
   • Descripción: "Nómina completa, [X] funcionarios"
   • Subir archivo
   • Confirmar carga exitosa

5. Día 6-8: Seguimiento
   • Verificar estado: ¿Aprobado o Rechazado?
   • Si rechazado: Corregir inmediatamente
   • Si aprobado: Verificar publicación (día 10-12)

CONTACTOS:
• Dudas técnicas: TI ext. 100
• Dudas del sistema: Transparencia ext. 200
• Autoriza carga: Jefe RRHH ext. 150

ARCHIVOS:
• Plantilla Excel: \\servidor\RRHH\Transparencia\Plantilla_7a2.xlsx
• Fórmula enmascarar RUT: \\servidor\RRHH\Scripts\enmascarar.vba
```

---

## 📖 GLOSARIO DE TÉRMINOS

**A**

**Aprobar**: Acción del revisor de aceptar un documento como válido y listo para publicar.

**Audit Trail**: Registro cronológico de todas las acciones realizadas en el sistema (también llamado "pista de auditoría").

**B**

**Backend**: Parte del sistema que corre en el servidor, no visible para el usuario.

**Backup**: Copia de seguridad (de datos o de responsable suplente).

**C**

**Cargador de Información**: Perfil de usuario responsable de subir documentos al sistema.

**CPLT**: Consejo para la Transparencia, organismo fiscalizador.

**CSRF**: Cross-Site Request Forgery, vulnerabilidad de seguridad.

**D**

**Dashboard**: Panel principal donde el usuario ve resumen de sus tareas.

**Dirección**: Unidad administrativa de la institución (departamento, dirección, unidad).

**E**

**Estado**: Situación actual de un documento (pendiente, aprobado, rechazado, publicado).

**F**

**Frontend**: Parte del sistema visible e interactiva para el usuario (interfaz web).

**Frecuencia**: Concepto relacionado con la periodicidad (mensual, trimestral, etc.).

**H**

**Hash**: Algoritmo de encriptación irreversible usado para contraseñas.

**Historial**: Registro de todas las versiones y cambios de un documento o item.

**I**

**Item**: Obligación específica de transparencia que debe publicarse.

**M**

**Metadatos**: Información sobre un documento (título, descripción, fecha, autor).

**Middleware**: Capa de software que valida permisos antes de ejecutar acciones.

**N**

**Numeración**: Código único que identifica un item (ej: 7a.2).

**O**

**Observaciones**: Comentarios del revisor al rechazar un documento.

**P**

**Periodicidad**: Frecuencia con que debe actualizarse un item (mensual, trimestral, etc.).

**Plazo Interno**: Fecha límite establecida internamente para que usuario cargue documento.

**Plazo Portal**: Fecha límite legal o institucional para publicar en portal externo.

**Portal Externo**: Sitio web público donde se publica la información de transparencia.

**Prepared Statement**: Técnica de prevención de SQL Injection.

**Publicador**: Usuario responsable de publicar documentos en portal externo.

**R**

**Rechazar**: Acción del revisor de devolver un documento con observaciones para corrección.

**Revisor**: Usuario responsable de validar documentos antes de publicación.

**S**

**SQL Injection**: Vulnerabilidad de seguridad en bases de datos.

**T**

**Transparencia Activa**: Obligación de publicar información proactivamente sin solicitud.

**Transparencia Pasiva**: Obligación de responder solicitudes de información específicas.

**Trazabilidad**: Capacidad de rastrear todo el ciclo de vida de un documento.

**U**

**Upload**: Proceso de subir un archivo al sistema.

**URL**: Dirección web única de un recurso publicado.

**V**

**Verificador**: Captura de pantalla que demuestra que un documento fue publicado.

**Verificación**: Proceso de confirmar que documento está efectivamente en portal público.

**X**

**XSS**: Cross-Site Scripting, vulnerabilidad de seguridad.

---

## ❓ PREGUNTAS FRECUENTES (FAQ)

### Generales

**P1: ¿Qué pasa si no cumplo con cargar mi documento?**

R: A nivel personal: Tu jefe y el administrativo serán notificados. Puede afectar tu evaluación de desempeño.

A nivel institucional: La municipalidad/servicio podría recibir multa del CPLT de hasta 15 UTM por item incumplido (~$975.000 por item). Además, daño reputacional y posible denuncias ante Contraloría.

---

**P2: ¿Puedo cargar documentos desde mi casa o solo desde la oficina?**

R: Depende de la configuración de tu institución. Consulta con el administrativo si el sistema está disponible solo en red interna o también desde internet externa (con VPN o sin restricciones).

---

**P3: ¿Qué hago si no tengo la información para cargar?**

R: 
1. Consultar con tu jefe inmediato
2. Verificar si otra área tiene la información
3. Avisar al administrativo lo antes posible
4. NO esperar hasta el último día para consultar

---

**P4: ¿Los ciudadanos pueden ver mi nombre como quien cargó el documento?**

R: No. Los ciudadanos solo ven el documento final publicado, no el proceso interno ni los nombres de quienes lo cargaron/revisaron.

---

**P5: ¿Puedo eliminar un documento que ya cargué?**

R: No directamente. Debes solicitar al administrativo que lo elimine. Justificación: mantener trazabilidad de todas las acciones.

---

### Técnicas

**P6: ¿Qué navegadores soporta el sistema?**

R: Chrome 90+, Firefox 88+, Edge 90+, Safari 14+. NO es compatible con Internet Explorer.

---

**P7: ¿El sistema funciona en celular/tablet?**

R: La interfaz es responsiva (se adapta), pero se recomienda computador de escritorio para mejor experiencia, especialmente al cargar documentos.

---

**P8: ¿Qué pasa si el sistema está "caído" el día del vencimiento?**

R: Avisar inmediatamente al administrativo y TI. Si el problema persiste más de 2 horas, el administrativo debe documentar la incidencia para justificar ante el CPLT. Una vez resuelto, priorizar tu carga.

---

**P9: ¿Puedo usar contraseñas guardadas del navegador?**

R: Sí, es seguro. Asegúrate de que tu computador esté protegido con contraseña de Windows/Mac.

---

## 🏆 Conclusión

El **Sistema de Administración de Carga Unificada y Control de Transparencia** es una solución completa que transforma la gestión de transparencia activa de una tarea administrativa manual y propensa a errores en un proceso **automatizado, trazable y eficiente**.

### Valor Diferencial

1. **Cumplimiento Legal**: Garantiza que la institución cumpla con la Ley 20.285
2. **Trazabilidad Total**: Cada acción registrada con usuario, fecha y evidencia
3. **Eficiencia**: Reduce tiempo administrativo significativamente
4. **Transparencia Real**: No solo publica, sino que verifica y demuestra
5. **Prevención**: Alertas tempranas evitan incumplimientos
6. **Auditabilidad**: Preparado para fiscalizaciones del CPLT

### Impacto Social

Al asegurar el cumplimiento riguroso de transparencia activa, el sistema contribuye a:
- 🏛️ **Fortalecimiento democrático** mediante acceso a información pública
- 👥 **Confianza ciudadana** al demostrar compromiso institucional
- ⚖️ **Rendición de cuentas** clara y verificable
- 🎯 **Mejora continua** basada en datos de cumplimiento

### Implementación Exitosa

Para que el sistema funcione óptimamente:
✅ Capacitación inicial obligatoria para todos los usuarios  
✅ Compromiso de la alta dirección  
✅ Asignación clara de responsabilidades  
✅ Monitoreo permanente de indicadores  
✅ Cultura institucional orientada a la transparencia  

---

**Sistema desarrollado para el control efectivo de Transparencia Activa según Ley 20.285**  
*Versión 2.0 - Manual Completo - Con sistema de plazos internos y verificación de publicación*

---

📧 **Soporte Técnico**: soporte@institucion.gob.cl  
📞 **Mesa de Ayuda**: +56 2 XXXX XXXX  
🌐 **Documentación**: https://cumplimiento.institucion.gob.cl/ayuda  

**Última actualización de este manual**: Marzo 2026  
**Versión del documento**: 2.1




