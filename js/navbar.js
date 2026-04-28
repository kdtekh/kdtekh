// Toggle del menú móvil
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    });

    // Cerrar menú al hacer clic en un enlace
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('no-scroll');
        });
    });
}

// Efecto de scroll en la barra de navegación
const navbar = document.querySelector('.navbar');
const topBar = document.querySelector('.top-bar');
let lastScroll = 0;

// Función para manejar las animaciones al hacer scroll
function handleScrollAnimations() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const windowHeight = window.innerHeight;
        
        // Mostrar elemento cuando está en el viewport
        if (elementTop < windowHeight - 100) {
            element.classList.add('animated');
        }
    });
}

// Inicializar animaciones al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // Aplicar estilos iniciales
    if (navbar) {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
            if (topBar) topBar.style.transform = 'translateY(-100%)';
        }
    }
    
    // Iniciar animaciones
    handleScrollAnimations();
});

// Manejar el scroll de la página
if (navbar) {
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Mostrar/ocultar barra de navegación al hacer scroll
        if (currentScroll <= 0) {
            navbar.classList.remove('scrolled-up');
            navbar.classList.remove('scrolled');
            if (topBar) topBar.style.transform = 'translateY(0)';
            lastScroll = currentScroll;
            return;
        }
        
        if (currentScroll > lastScroll && !navbar.classList.contains('scrolled-down')) {
            // Scroll hacia abajo
            navbar.classList.remove('scrolled-up');
            navbar.classList.add('scrolled-down');
        } else if (currentScroll < lastScroll && navbar.classList.contains('scrolled-down')) {
            // Scroll hacia arriba
            navbar.classList.remove('scrolled-down');
            navbar.classList.add('scrolled-up');
        }
        
        // Añadir clase 'scrolled' cuando se hace scroll
        if (currentScroll > 100) {
            navbar.classList.add('scrolled');
            if (topBar) topBar.style.transform = 'translateY(-100%)';
        } else {
            navbar.classList.remove('scrolled');
            if (topBar) topBar.style.transform = 'translateY(0)';
        }
        
        // Manejar animaciones al hacer scroll
        handleScrollAnimations();
        lastScroll = currentScroll;
    });
}

// Cerrar menú desplegable al hacer clic fuera
document.addEventListener('click', (e) => {
    const isDropdownButton = e.target.matches('[data-dropdown-button]');
    if (!isDropdownButton && e.target.closest('[data-dropdown]') != null) return;
    
    let currentDropdown;
    if (isDropdownButton) {
        currentDropdown = e.target.closest('[data-dropdown]');
        currentDropdown.classList.toggle('active');
    }
    
    document.querySelectorAll('[data-dropdown].active').forEach(dropdown => {
        if (dropdown === currentDropdown) return;
        dropdown.classList.remove('active');
    });
});

// Actualizar enlace activo en la navegación
const currentLocation = window.location.href;
const menuItems = document.querySelectorAll('.nav-menu a');
const menuLength = menuItems.length;

for (let i = 0; i < menuLength; i++) {
    if (menuItems[i].href === currentLocation) {
        menuItems[i].classList.add('active');
    } else {
        menuItems[i].classList.remove('active');
    }
}
