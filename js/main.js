document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');
    const dropdowns = document.querySelectorAll('.dropdown');
    
    // Menú móvil
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', (e) => {
            e.stopPropagation();
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
        
        // Cerrar menú al hacer clic en un enlace
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
                document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
            });
        });
        
        // Manejar dropdowns en móvil
        dropdowns.forEach(dropdown => {
            const link = dropdown.querySelector('.nav-link');
            if (link) {
                link.addEventListener('click', (e) => {
                    if (window.innerWidth <= 992) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropdown.classList.toggle('active');
                    }
                });
            }
        });
        
        // Cerrar menú al hacer clic en un enlace que no sea dropdown
        navLinks.forEach(link => {
            if (!link.closest('.dropdown')) {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
                
                // Cerrar todos los dropdowns
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
    }
    
    // Scroll suave para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100, // Ajuste para el header fijo
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Efecto de scroll para el header
    const header = document.querySelector('.header');
    if (header) {
        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll <= 0) {
                header.classList.remove('scroll-up');
                return;
            }
            
            if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
                // Scroll hacia abajo
                header.classList.remove('scroll-up');
                header.classList.add('scroll-down');
            } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
                // Scroll hacia arriba
                header.classList.remove('scroll-down');
                header.classList.add('scroll-up');
            }
            
            lastScroll = currentScroll;
        });
    }
    
    // Animación de aparición de elementos al hacer scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Configuración inicial de animaciones
    document.querySelectorAll('.animate-on-scroll').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    // Ejecutar animación al cargar y al hacer scroll
    window.addEventListener('load', () => {
        animateOnScroll();
        
        // Inicializar el ticker de noticias
        initNewsTicker();
    });
    
    window.addEventListener('scroll', animateOnScroll);
    
    // Función para el ticker de noticias
    function initNewsTicker() {
        const ticker = document.querySelector('.news-ticker');
        if (!ticker) return;
        
        const items = ticker.querySelectorAll('span');
        if (items.length === 0) return;
        
        let currentItem = 0;
        const itemCount = items.length;
        
        // Mostrar el primer elemento
        items[0].classList.add('active');
        
        // Cambiar noticia cada 5 segundos
        setInterval(() => {
            items[currentItem].classList.remove('active');
            currentItem = (currentItem + 1) % itemCount;
            items[currentItem].classList.add('active');
        }, 5000);
    }
    
    // Actualizar la fecha actual
    function updateCurrentDate() {
        const dateElement = document.querySelector('.date');
        if (dateElement) {
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const now = new Date();
            dateElement.textContent = now.toLocaleDateString('es-ES', options);
        }
    }
    
    // Inicializar la fecha actual
    updateCurrentDate();
});

// Función para cargar contenido dinámico
async function loadContent(section) {
    try {
        const response = await fetch(`sections/${section}.html`);
        if (!response.ok) throw new Error('No se pudo cargar el contenido');
        
        const content = await response.text();
        document.getElementById('dynamic-content').innerHTML = content;
        
        // Volver a inicializar animaciones para el contenido cargado dinámicamente
        document.querySelectorAll('.animate-on-scroll').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        });
        
        // Ejecutar animaciones para el nuevo contenido
        setTimeout(() => {
            const elements = document.querySelectorAll('.animate-on-scroll');
            elements.forEach(element => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            });
        }, 100);
    } catch (error) {
        console.error('Error al cargar el contenido:', error);
    }
}

// Función para manejar la búsqueda
function handleSearch(query) {
    // Implementar lógica de búsqueda aquí
    console.log('Buscando:', query);
}
