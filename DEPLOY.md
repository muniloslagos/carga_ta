# Instrucciones de Despliegue con Git

## 📦 Preparación Inicial

### 1. Inicializar repositorio Git (si no está inicializado)

```bash
cd c:\xampp\htdocs\numeracion
git init
```

### 2. Configurar Git (primera vez)

```bash
git config user.name "Tu Nombre"
git config user.email "tu@email.com"
```

## 🚀 Deploy a Repositorio Remoto

### Opción A: GitHub

#### 1. Crear repositorio en GitHub
- Ve a https://github.com/new
- Nombre: `sistema-numeracion-municipal`
- Descripción: `Sistema de gestión de turnos y numeración - Municipalidad de Los Lagos`
- Privado/Público según preferencia

#### 2. Conectar y subir código

```bash
# Agregar todos los archivos
git add .

# Crear primer commit
git commit -m "Initial commit: Sistema de Numeración v1.0"

# Conectar con repositorio remoto
git remote add origin https://github.com/TU-USUARIO/sistema-numeracion-municipal.git

# Subir código
git branch -M main
git push -u origin main
```

### Opción B: GitLab

```bash
# Crear repositorio en GitLab primero
git add .
git commit -m "Initial commit: Sistema de Numeración v1.0"
git remote add origin https://gitlab.com/TU-USUARIO/sistema-numeracion-municipal.git
git branch -M main
git push -u origin main
```

### Opción C: Bitbucket

```bash
# Crear repositorio en Bitbucket primero
git add .
git commit -m "Initial commit: Sistema de Numeración v1.0"
git remote add origin https://bitbucket.org/TU-USUARIO/sistema-numeracion-municipal.git
git branch -M main
git push -u origin main
```

## 📋 Comandos Git Básicos

### Ver estado de archivos
```bash
git status
```

### Agregar cambios
```bash
# Agregar archivos específicos
git add archivo.php

# Agregar todos los cambios
git add .
```

### Crear commit
```bash
git commit -m "Descripción de los cambios"
```

### Subir cambios al servidor
```bash
git push
```

### Descargar cambios del servidor
```bash
git pull
```

### Ver historial de commits
```bash
git log
```

### Ver cambios realizados
```bash
git diff
```

## 🔄 Flujo de Trabajo Recomendado

### Para cambios diarios:

```bash
# 1. Ver qué archivos cambiaron
git status

# 2. Revisar los cambios
git diff

# 3. Agregar archivos modificados
git add .

# 4. Crear commit con descripción clara
git commit -m "Descripción: qué se cambió y por qué"

# 5. Subir al repositorio
git push
```

### Ejemplos de commits descriptivos:

```bash
git commit -m "feat: Agregar configuración manual de numeración"
git commit -m "fix: Corregir beep de audio en pantalla pública"
git commit -m "refactor: Mejorar restricción de módulos por usuario"
git commit -m "docs: Actualizar README con instrucciones de instalación"
```

## 🌿 Manejo de Ramas (Branches)

### Crear una rama para desarrollo
```bash
git checkout -b desarrollo
```

### Cambiar entre ramas
```bash
git checkout main          # Ir a rama principal
git checkout desarrollo    # Ir a rama de desarrollo
```

### Fusionar cambios de desarrollo a main
```bash
git checkout main
git merge desarrollo
git push
```

## 🔐 Clonación en Otro Servidor

### Para instalar en producción:

```bash
# 1. Clonar el repositorio
cd /ruta/del/servidor
git clone https://github.com/TU-USUARIO/sistema-numeracion-municipal.git numeracion

# 2. Entrar al directorio
cd numeracion

# 3. Configurar base de datos
# Editar config/config.php con las credenciales correctas

# 4. Importar base de datos
mysql -u root -p < sql/database.sql

# 5. Configurar permisos (Linux)
chmod -R 755 .
chown -R www-data:www-data .

# 6. Listo para usar
```

## 📊 Etiquetas de Versión (Tags)

### Crear una versión estable

```bash
# Etiquetar versión
git tag -a v1.0 -m "Versión 1.0 - Sistema completo con letras"

# Subir etiqueta
git push origin v1.0

# Ver todas las etiquetas
git tag
```

## 🛑 Ignorar Archivos Sensibles

El archivo `.gitignore` ya está configurado para excluir:
- Archivos de configuración de base de datos
- Logs y archivos temporales
- Archivos de sistema operativo
- Backups de base de datos

## ⚠️ IMPORTANTE - Seguridad

### Antes de hacer push, verificar que NO se incluyan:

- Contraseñas de base de datos
- Tokens de API
- Archivos de configuración local
- Datos sensibles de usuarios

### Verificar con:
```bash
git status          # Ver qué se va a subir
git diff            # Ver contenido de los cambios
```

## 🆘 Solución de Problemas

### Si aparece error de autenticación:

**Para GitHub:**
1. Crear Personal Access Token en: Settings → Developer settings → Personal access tokens
2. Usar el token en lugar de la contraseña

### Si hay conflictos al hacer pull:

```bash
# Ver archivos en conflicto
git status

# Editar manualmente los archivos marcados con conflicto
# Buscar líneas con <<<<<<< HEAD y >>>>>>>

# Después de resolver:
git add .
git commit -m "Resolver conflictos de merge"
git push
```

### Deshacer último commit (sin perder cambios):

```bash
git reset --soft HEAD~1
```

### Deshacer cambios en un archivo:

```bash
git checkout -- archivo.php
```

## 📞 Soporte

Para más información sobre Git:
- Documentación oficial: https://git-scm.com/doc
- Tutorial interactivo: https://learngitbranching.js.org/
