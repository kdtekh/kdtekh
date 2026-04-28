// Cargar el header en todas las páginas excepto en newsletter-admin.html
function loadHeader() {
    // Verificar si no estamos en la página de administración de newsletter
    if (!window.location.pathname.includes('newsletter-admin.html')) {
        // Crear un contenedor para el header
        const headerContainer = document.createElement('div');
        headerContainer.id = 'header-container';
        
        // Insertar el header al principio del body
        document.body.insertBefore(headerContainer, document.body.firstChild);
        
        // Cargar el contenido del header usando fetch
        fetch('includes/header.html')
            .then(response => response.text())
            .then(html => {
                // Insertar el HTML del header
                headerContainer.innerHTML = html;
                
                // Inicializar el menú móvil
                initMobileMenu();
                
                // Marcar como activo el enlace correspondiente a la página actual
                updateActiveLink();
            })
            .catch(error => {
                console.error('Error al cargar el header:', error);
            });
    }
}

// Función para inicializar el menú móvil
function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const overlay = document.querySelector('.overlay');
    
    if (hamburger && navMenu) {
        // Toggle del menú móvil
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
            
            // Mostrar/ocultar overlay
            if (overlay) {
                overlay.style.display = navMenu.classList.contains('active') ? 'block' : 'none';
            }
        });
        
        // Cerrar menú al hacer clic en un enlace
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
                
                // Ocultar overlay
                if (overlay) {
                    overlay.style.display = 'none';
                }
            });
        });
        
        // Cerrar menú al hacer clic en el overlay
        if (overlay) {
            overlay.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
    }
}

// Función para marcar como activo el enlace correspondiente a la página actual
function updateActiveLink() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-menu > a');
    
    navLinks.forEach(link => {
        // Remover clase 'active' de todos los enlaces
        link.classList.remove('active');
        
        // Verificar si el href del enlace coincide con la ruta actual
        const linkPath = link.getAttribute('href');
        if (linkPath && (currentPath.endsWith(linkPath) || 
                         (linkPath === '/periodico-digital/' && (currentPath.endsWith('index.html') || currentPath.endsWith('/'))))) {
            link.classList.add('active');
        }
    });
}

// Cargar el header cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', loadHeader);
