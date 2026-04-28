const fs = require('fs');
const path = require('path');

// Definir el nuevo footer
const newFooter = `
    <footer class="footer" itemscope itemtype="https://schema.org/WPFooter">
        <div class="container">
            <div class="footer-content" itemscope itemtype="https://schema.org/Organization">
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <p itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                        <span itemprop="streetAddress">Calle Ejemplo, 123</span><br>
                        <span itemprop="postalCode">46001</span> <span itemprop="addressLocality">Valencia</span><br>
                        <span itemprop="addressRegion">Valencia</span>, <span itemprop="addressCountry">España</span>
                    </p>
                    <p>
                        <i class="fas fa-phone"></i> <a href="tel:+34900123456" itemprop="telephone">900 123 456</a><br>
                        <i class="fas fa-envelope"></i> <a href="mailto:info@kdtekh.com" itemprop="email">info@kdtekh.com</a>
                    </p>
                </div>
                <div class="footer-section">
                    <h4>Horario</h4>
                    <p>Lunes a Viernes: 9:00 - 18:00<br>
                    Sábado y Domingo: Cerrado</p>
                    <div class="social-links">
                        <a href="https://www.linkedin.com/company/kdtekh" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="https://twitter.com/kdtekh" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.facebook.com/kdtekhofficial" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span itemprop="copyrightYear">2024</span> <span itemprop="name">KDTech</span>. Todos los derechos reservados.</p>
                <nav aria-label="Enlaces legales">
                    <a href="/aviso-legal.html">Aviso Legal</a> | 
                    <a href="/politica-privacidad.html">Política de Privacidad</a> | 
                    <a href="/cookies.html">Política de Cookies</a>
                </nav>
            </div>
        </div>
    </footer>
    <style>
        /* Estilos para el footer mejorado */
        .footer {
            background-color: #1a202c;
            color: #e2e8f0;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
        }
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .footer-section {
            flex: 1;
            min-width: 250px;
        }
        .footer h4 {
            color: #ffffff;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }
        .footer p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .social-links a {
            color: #cbd5e0;
            font-size: 1.5rem;
            transition: color 0.3s ease;
        }
        .social-links a:hover {
            color: #ffffff;
        }
        .footer-bottom {
            border-top: 1px solid #2d3748;
            padding-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #a0aec0;
        }
        .footer-bottom nav {
            margin-top: 1rem;
        }
        .footer-bottom a {
            color: #cbd5e0;
            margin: 0 0.5rem;
            text-decoration: none;
        }
        .footer-bottom a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
            }
        }
    </style>`;

// Función para actualizar los footers
async function updateFooters() {
    try {
        // Archivos a actualizar (excluyendo index.html)
        const filesToUpdate = [
            'aviso-legal.html',
            'blog.html',
            'categorias/basedatos.html',
            'categorias/ciberseguridad.html',
            'categorias/diseno.html',
            'categorias/ofimatica.html',
            'categorias/programacion.html',
            'categorias/redes.html',
            'contacto.html',
            'cookies.html',
            'cursos/ciencia-datos.html',
            'cursos/diseno-web.html',
            'cursos/ia-ml.html',
            'cursos/ingles.html',
            'cursos/marketing-digital.html',
            'cursos/programacion.html',
            'cursos.html',
            'metodo.html',
            'politica-privacidad.html',
            'sobre-nosotros.html'
        ];

        let updatedCount = 0;

        for (const file of filesToUpdate) {
            try {
                const filePath = path.join(__dirname, file);
                
                // Verificar si el archivo existe
                if (!fs.existsSync(filePath)) {
                    console.log(`Archivo no encontrado: ${file}`);
                    continue;
                }

                // Leer el contenido del archivo
                let content = fs.readFileSync(filePath, 'utf8');
                
                // Buscar y reemplazar el footer existente
                // Primero intentamos con el patrón más común
                const footerRegex = /<footer[\s\S]*?<\/footer>\s*<style>[\s\S]*?<\/style>/i;
                const newContent = content.replace(footerRegex, newFooter);
                
                // Si no hubo cambios, intentamos con un patrón más simple
                if (newContent === content) {
                    const simpleFooterRegex = /<footer[\s\S]*?<\/footer>/i;
                    content = content.replace(simpleFooterRegex, newFooter);
                } else {
                    content = newContent;
                }
                
                // Escribir el archivo actualizado
                fs.writeFileSync(filePath, content, 'utf8');
                console.log(`✅ Actualizado: ${file}`);
                updatedCount++;
                
            } catch (error) {
                console.error(`❌ Error al procesar ${file}:`, error.message);
            }
        }

        console.log(`\n✅ Proceso completado. Se actualizaron ${updatedCount} archivos.`);
        
    } catch (error) {
        console.error('Error general:', error);
    }
}

// Ejecutar la función
updateFooters();
