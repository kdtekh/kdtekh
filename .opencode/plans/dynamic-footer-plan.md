# Plan: Dinamizar Footer + Extraer Scripts Inline

## Problemática Actual
- 15 páginas HTML tienen footers inline duplicados
- 3 páginas tienen scripts/styles de cookie consent inline
- 1 página (index.html) tiene script de navegación de testimonios inline
- `js/config.js` tiene `renderFooter()` simplificado que no coincide con los footers actuales

## Paso 1: Actualizar `js/config.js`
- Actualizar `SITE_CONFIG` con datos correctos (email, teléfono, redes sociales reales)
- Reemplazar `renderFooter()` para generar footer idéntico al de `index.html`:
  - Sección 1: Logo + descripción + contacto
  - Sección 2: Enlaces Rápidos (Inicio, Cursos, Blog, Sobre Nosotros, Contacto)
  - Sección 3: Síguenos (5 redes sociales con URLs reales)
  - Sección 4: Newsletter (form con csrf_token, ids correctos)
  - Footer-bottom: © + Aviso Legal + Política de Privacidad
- Mantener `loadFooter()` y `DOMContentLoaded` auto-init

## Paso 2: Crear `css/cookie-consent.css`
Extraer estilos de `index.html` `<style>...cookie-consent...</style>`:
- `.cookie-consent`, `.cookie-consent.visible`
- `.cookie-consent p`, `.cookie-consent .btn`
- Media query para mobile

## Paso 3: Crear `js/cookie-consent.js`
Extraer lógica de `index.html`:
- Crear HTML del banner dinámicamente si no existe
- Gestión de localStorage `cookie_consent`
- Botones aceptar/rechazar

## Paso 4: Crear `js/testimonial-nav.js`
Extraer navegación de `index.html`:
- Solo ejecuta si `#testimonios` existe
- Calcula cardWidth + gap
- Botones prev/next
- Visibilidad condicional de botones

## Paso 5: Minificar
- `css/cookie-consent.min.css`
- `js/cookie-consent.min.js`
- `js/testimonial-nav.min.js`
- Regenerar `js/config.min.js`

## Paso 6: Actualizar HTMLs

### 6A: Reemplazar footers inline (15 páginas)
Reemplazar `<footer class="footer">...</footer>` con:
```html
<div id="footer-container"></div>
<script src="/js/config.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadFooter('footer-container');
});
</script>
```

Páginas:
- aviso-legal.html
- blog.html
- contacto.html
- cursos.html
- empresas.html
- index.html
- metodo.html
- politica-privacidad.html
- sobre-nosotros.html
- categorias/basedatos.html
- categorias/ciberseguridad.html
- categorias/diseno.html
- categorias/ofimatica.html
- categorias/programacion.html
- categorias/redes.html

### 6B: Extraer cookie consent inline (3 páginas)
Reemplazar inline `<style>...` + `<script>...` + HTML banner con:
```html
<link rel="stylesheet" href="/css/cookie-consent.min.css">
<script src="/js/cookie-consent.min.js" defer></script>
```

Páginas: index.html, cookies.html, politica-cookies.html

### 6C: Extraer testimonial nav inline (1 página)
Reemplazar script inline con:
```html
<script src="/js/testimonial-nav.min.js" defer></script>
```

Página: index.html

## Paso 7: Verificación
- Validar todos los enlaces HTML
- Validar referencias JS/CSS
- Verificar que no queden scripts inline duplicados
- Verificar que config.min.js tenga loadFooter

## Archivos a Crear
1. css/cookie-consent.css
2. css/cookie-consent.min.css
3. js/cookie-consent.js
4. js/cookie-consent.min.js
5. js/testimonial-nav.js
6. js/testimonial-nav.min.js

## Archivos a Modificar
1. js/config.js → actualizar renderFooter() + SITE_CONFIG
2. js/config.min.js → regenerar
3. 15 páginas HTML → reemplazar footers inline
4. 3 páginas HTML → extraer cookie consent
5. index.html → extraer testimonial nav

## Total de cambios
- 6 archivos nuevos
- ~19 archivos modificados
- 0 archivos eliminados
