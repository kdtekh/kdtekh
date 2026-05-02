# AGENTS.md - KDTekh Project

## Propósito

Guía para agentes y colaboradores del proyecto educativo KDTekh.

---

## Roles

### Desarrollador

- Mantiene estructura HTML/CSS/JS
- Gestiona pipelines de CI/CD
- Revisa cambios de código

### Gestor de Contenido

- Agrega nuevos cursos
- Actualiza información de cursos
- Valida datos en el CSV

---

## Flujos Principales

### 1. Agregar Nuevo Curso

#### Opción A: Manual (Recomendado para Gestores)

```bash
# Con herramienta Python
python tool.py add "Título" "Duración" "Descripción" "Dificultad" "URL" [Categoría]

# Con herramienta JavaScript (extrae metadatos automáticamente)
node youtube-courses-tool/tool.js add <youtube_url>
```

#### Opción B: Edición Directa de HTML

1. Editar `cursos.html` agregando la tarjeta del curso
2. Asegurar atributos: `data-category`, `data-level`, `data-duration`
3. Agregar badge: `<span class="category-badge"><i class="fas fa-ICONO"></i> Categoría</span>`
4. GitHub Actions actualizará automáticamente `data/cursos.csv`

### 2. Mantenimiento del CSV

**Fuente de la verdad:** `cursos.html` es la fuente principal. El CSV se genera automáticamente.

**Workflow de GitHub Actions:**

- Archivo: `.github/workflows/update-courses.yml`
- Disparadores:
  - Manual: `workflow_dispatch`
  - Automático: Push con cambios en `cursos.html`
- Proceso: Extrae datos del HTML → Actualiza `data/cursos.csv`

### 3. Despliegue

**Plataforma:** Cloudflare Pages

- Despliegue automático desde la rama `main`
- URL: https://kdtekh.pages.dev/cursos

---

## Issues Conocidos

### Bug en .gitignore

El archivo `.gitignore` tiene una configuración incorrecta:

- `data/` ignora el directorio completo
- `!data/cursos.csv` NO puede un-ignorar archivos dentro de un directorio ignorado

**Solución pendiente:** Cambiar `data/` a `data/*` en `.gitignore`

**Workaround actual:** Usar `git add -f data/cursos.csv` para forzar el add

---

## Buenas Prácticas

### Para Gestores de Contenido

- Verificar que las URLs de YouTube sean válidas
- Usar formato consistente de duración (ej: "2h 15m")
- Confirmar que la categoría coincida con los filtros disponibles
- Incluir badge de categoría en cada tarjeta de curso
- Usar los filtros existentes: programación, web, ciberseguridad, datos, ofimática, redes, idiomas, ia, diseño

### Para Desarrolladores

- Probar cambios de HTML localmente antes de pushear
- Asegurar que todas las tarjetas tengan atributos `data-*` (category, level, duration)
- Verificar que el badge de categoría esté presente en cada tarjeta

### Git Workflow

```bash
# Siempre hacer pull antes de trabajar
git pull origin main

# Para cambios mayores, usar ramas
git checkout -b feature/nueva-seccion

# Commits con mensajes descriptivos
git commit -m "feat: agregar cursos de IA"

# Push y PR para revisión
git push origin feature/nueva-seccion
```

---

## Referencia de Herramientas

| Herramienta | Ubicación | Propósito |
|-------------|-----------|-----------|
| Python Tool | `tool.py` | Agregar cursos vía CLI |
| JS Tool | `youtube-courses-tool/tool.js` | Agregar cursos con metadatos automáticos |
| GitHub Actions | `.github/workflows/update-courses.yml` | Sincronización automática CSV desde HTML |

---

## Troubleshooting

### El CSV no se actualiza

- Verificar si `cursos.html` fue modificado
- Revisar que el workflow de GitHub Actions se ejecutó
- Chequear logs en GitHub → Actions

### Un curso no aparece en la página

- Verificar que `data-category` coincida con los filtros (verificar coincidencia exacta)
- Confirmar que `data-level` y `data-duration` estén definidos
- Asegurar que el badge de categoría esté en el HTML

---

## Actualización de Este Documento

**Este documento debe actualizarse cuando:**

- Se añadan nuevas herramientas o flujos
- Cambie la estructura del proyecto
- Se modifiquen los roles o responsabilidades

### Proceso de Actualización (Semiautomático)

1. El agente detecta un cambio significativo
2. Consulta al usuario: "¿Deseas actualizar AGENTS.md?"
3. Con aprobación → Se actualiza el documento
4. Commit: `docs: update AGENTS.md with [cambio]`

**NO usar "piloto automático"** - Las actualizaciones requieren contexto humano y aprobación.