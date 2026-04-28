const fs = require('fs');
const path = require('path');

// Directorio raíz del proyecto
const rootDir = __dirname;

// Archivos que deben ser excluidos
const excludedFiles = [
    'newsletter-admin.html',
    'includes/header.html'
];

// Función para verificar si un archivo debe ser excluido
function isExcluded(filePath) {
    return excludedFiles.some(excluded => filePath.endsWith(excluded));
}

// Función para actualizar el header en un archivo HTML
function updateHeaderInFile(filePath) {
    try {
        // Leer el contenido del archivo
        let content = fs.readFileSync(filePath, 'utf8');
        
        // Verificar si el archivo ya tiene el header dinámico
        if (content.includes('id="header-container"')) {
            console.log(`El archivo ${filePath} ya tiene el header dinámico.`);
            return;
        }
        
        // Reemplazar el header existente con el contenedor dinámico
        const headerRegex = /<header[\s\S]*?<\/header>/i;
        const newContent = content.replace(headerRegex, '<!-- El header se cargará dinámicamente mediante JavaScript -->\n    <div id="header-container"></div>');
        
        // Asegurarse de que el script load-header.js esté incluido
        const scriptTag = '<script src="/periodico-digital/js/load-header.js"></script>';
        const bodyEndTag = '</body>';
        
        let finalContent = newContent;
        
        // Asegurarse de que el script esté presente antes del cierre de </body>
        if (!newContent.includes('load-header.js') && newContent.includes(bodyEndTag)) {
            finalContent = newContent.replace(
                bodyEndTag, 
                `    ${scriptTag}\n    ${bodyEndTag}`
            );
        }
        
        // Escribir el contenido actualizado de vuelta al archivo
        fs.writeFileSync(filePath, finalContent, 'utf8');
        console.log(`✅ Header actualizado en: ${filePath}`);
        
    } catch (error) {
        console.error(`❌ Error al procesar el archivo ${filePath}:`, error.message);
    }
}

// Función para buscar archivos HTML recursivamente
function processDirectory(directory) {
    const files = fs.readdirSync(directory);
    
    files.forEach(file => {
        const fullPath = path.join(directory, file);
        const stat = fs.statSync(fullPath);
        
        if (stat.isDirectory()) {
            // Excluir directorios de node_modules, .git, etc.
            if (!['node_modules', '.git', 'vendor', 'includes'].includes(file)) {
                processDirectory(fullPath);
            }
        } else if (file.endsWith('.html') && !isExcluded(fullPath)) {
            updateHeaderInFile(fullPath);
        }
    });
}

// Iniciar el proceso desde el directorio raíz
console.log('🚀 Iniciando actualización de headers...');
processDirectory(rootDir);
console.log('✅ Proceso completado.');
