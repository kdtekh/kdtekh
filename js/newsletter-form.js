/**
 * Newsletter Form Handler
 * 
 * Este script maneja el formulario de suscripción al newsletter
 * 
 * @version 2.0
 * @author KD Tekh Team
 */

console.log('Script newsletter-form.js cargado correctamente');

// Función para inicializar el formulario
function initNewsletterForm() {
    console.log('Buscando formulario...');
    
    // Buscar el formulario de múltiples maneras
    let footerNewsletterForm = document.getElementById('footerNewsletterForm');
    
    // Si no se encuentra, intentar con querySelector
    if (!footerNewsletterForm) {
        console.log('No se encontró con getElementById, intentando con querySelector...');
        footerNewsletterForm = document.querySelector('form.newsletter-form');
    }
    
    // Si aún no se encuentra, buscar cualquier formulario con el ID
    if (!footerNewsletterForm) {
        console.log('No se encontró con querySelector, buscando cualquier formulario...');
        const forms = document.getElementsByTagName('form');
        for (let i = 0; i < forms.length; i++) {
            if (forms[i].id === 'footerNewsletterForm') {
                footerNewsletterForm = forms[i];
                break;
            }
        }
    }
    
    console.log('Elemento encontrado:', footerNewsletterForm);
    
    if (!footerNewsletterForm) {
        console.log('No se encontró el formulario de newsletter');
        return;
    }
    
    console.log('Formulario encontrado, configurando eventos...');
    
    // Buscar elementos del formulario
    const footerNewsletterEmail = footerNewsletterForm.querySelector('input[type="email"], [name="email"], #footerNewsletterEmail');
    const submitButton = footerNewsletterForm.querySelector('button[type="submit"], .btn-submit, button');
    let footerNewsletterMessage = footerNewsletterForm.querySelector('.newsletter-message');
    
    // Si no se encuentra el contenedor de mensajes, crearlo
    if (!footerNewsletterMessage) {
        console.log('Creando contenedor de mensajes dinámicamente...');
        footerNewsletterMessage = document.createElement('div');
        footerNewsletterMessage.className = 'newsletter-message';
        footerNewsletterForm.appendChild(footerNewsletterMessage);
    }
    
    console.log('Elementos encontrados:', {
        emailInput: footerNewsletterEmail,
        messageContainer: footerNewsletterMessage,
        submitButton: submitButton
    });
    
    // Verificar que los elementos esenciales existan
    if (!footerNewsletterEmail) {
        console.error('No se pudo encontrar el input de email');
        console.log('Contenido del formulario:', footerNewsletterForm.innerHTML);
        return;
    }
    
    if (!submitButton) {
        console.error('No se pudo encontrar el botón de envío');
        return;
    }
    
    /**
     * Valida una dirección de correo electrónico
     * @param {string} email - Correo electrónico a validar
     * @returns {boolean} true si el correo es válido, false en caso contrario
     */
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Muestra un mensaje de alerta en el contenedor especificado
     * @param {HTMLElement} container - Elemento contenedor del mensaje
     * @param {string} message - Texto del mensaje
     * @param {string} type - Tipo de mensaje (success, error, info)
     */
    function showAlert(container, message, type = 'info') {
        if (!container) {
            console.error('Contenedor de mensajes no encontrado, mostrando en consola:', message);
            return;
        }
        
        // Limpiar mensajes anteriores
        container.innerHTML = '';
        
        // Crear elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.style.marginTop = '10px';
        alertDiv.style.padding = '10px';
        alertDiv.style.borderRadius = '4px';
        alertDiv.style.fontSize = '0.9em';
        
        // Estilos según el tipo de mensaje
        const styles = {
            error: {
                backgroundColor: '#ffebee',
                color: '#c62828',
                borderLeft: '3px solid #f44336'
            },
            success: {
                backgroundColor: '#e8f5e9',
                color: '#2e7d32',
                borderLeft: '3px solid #4caf50'
            },
            info: {
                backgroundColor: '#e3f2fd',
                color: '#1565c0',
                borderLeft: '3px solid #2196f3'
            }
        };
        
        // Aplicar estilos
        Object.assign(alertDiv.style, styles[type] || styles.info);
        
        // Agregar ícono según el tipo
        const icons = {
            error: '❌',
            success: '✅',
            info: 'ℹ️'
        };
        
        alertDiv.innerHTML = `<span style="margin-right: 8px;">${icons[type] || ''}</span>${message}`;
        container.appendChild(alertDiv);
        
        // Eliminar el mensaje después de 5 segundos (excepto mensajes de éxito)
        if (type !== 'success') {
            setTimeout(() => {
                if (alertDiv.parentNode === container) {
                    container.removeChild(alertDiv);
                }
            }, 5000);
        }
    }
    
    /**
     * Obtiene el token CSRF del formulario o del servidor
     * @returns {Promise<string>} Token CSRF
     */
    async function getCsrfToken() {
        // Intentar obtener el token del formulario
        const csrfInput = document.getElementById('newsletterCsrfToken');
        
        // Si no existe el input, crearlo
        if (!csrfInput) {
            const newCsrfInput = document.createElement('input');
            newCsrfInput.type = 'hidden';
            newCsrfInput.name = 'csrf_token';
            newCsrfInput.id = 'newsletterCsrfToken';
            footerNewsletterForm.prepend(newCsrfInput);
        }
        
        // Si ya tenemos un token, devolverlo
        if (csrfInput && csrfInput.value) {
            return csrfInput.value;
        }
        
        try {
            // Si no hay token en el formulario, solicitarlo al servidor
            const timestamp = new Date().getTime();
            const url = `/php/csrf-token.php?form=newsletter_form&_=${timestamp}`;
            
            console.log('Solicitando token CSRF desde:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                cache: 'no-store',
                mode: 'cors'
            });
            
            console.log('Respuesta de CSRF recibida. Estado:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error en la respuesta CSRF:', errorText);
                throw new Error(`Error HTTP: ${response.status} - ${errorText}`);
            }
            
            const data = await response.json();
            console.log('Datos CSRF recibidos:', data);
            
            if (!data || !data.token) {
                throw new Error('Token CSRF no recibido en la respuesta');
            }
            
            // Actualizar el campo oculto
            const input = document.getElementById('newsletterCsrfToken');
            if (input) {
                input.value = data.token;
            } else {
                console.error('No se pudo encontrar el elemento para el token CSRF');
            }
            
            return data.token;
        } catch (error) {
            console.error('Error al obtener el token CSRF:', error);
            
            // Mostrar mensaje de error al usuario
            if (footerNewsletterMessage) {
                showAlert(
                    footerNewsletterMessage, 
                    'Error de conexión. Por favor, recarga la página e inténtalo de nuevo.', 
                    'error'
                );
            }
            
            // Reload the page after a short delay to reset the form state
            setTimeout(() => {
                window.location.reload();
            }, 3000);
            
            throw error; // Re-lanzar el error para que sea manejado por el llamador
        }
    }
    
    /**
     * Maneja el envío del formulario
     * @param {Event} e - Evento de envío del formulario
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        if (!footerNewsletterEmail || !footerNewsletterMessage || !submitButton) {
            console.error('Elementos del formulario no encontrados al enviar');
            return;
        }
        
        const email = footerNewsletterEmail.value ? footerNewsletterEmail.value.trim() : '';
        
        // Validar email
        if (!email) {
            showAlert(footerNewsletterMessage, 'Por favor ingresa tu correo electrónico', 'error');
            return;
        }
        
        if (!validateEmail(email)) {
            showAlert(footerNewsletterMessage, 'Por favor ingresa un correo electrónico válido', 'error');
            return;
        }
        
        // Deshabilitar botón de envío
        const originalButtonContent = submitButton.innerHTML;
        const originalButtonText = submitButton.querySelector('.btn-text') ? 
            submitButton.querySelector('.btn-text').textContent : 'Suscribirse';
            
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        
        // Mostrar spinner y actualizar texto
        if (submitButton.querySelector('.fa-spinner')) {
            submitButton.querySelector('.fa-spinner').classList.add('fa-spin');
            submitButton.querySelector('.fa-spinner').classList.remove('fa-paper-plane');
        } else {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        }
        
        if (submitButton.querySelector('.btn-text')) {
            submitButton.querySelector('.btn-text').textContent = 'Enviando...';
        }
        
        try {
            // Obtener token CSRF
            console.log('Obteniendo token CSRF...');
            const csrfToken = await getCsrfToken();
            
            if (!csrfToken) {
                throw new Error('No se pudo obtener el token de seguridad');
            }
            
            console.log('Token CSRF obtenido. Preparando envío...');
            
            // Enviar datos al servidor
            const formData = new FormData();
            formData.append('email', email);
            formData.append('csrf_token', csrfToken);
            
            // Agregar datos de depuración
            const requestData = {
                email: email,
                hasCsrfToken: !!csrfToken,
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent
            };
            
            console.log('Enviando datos:', requestData);
            
            const response = await fetch('/php/newsletter.php', {
                method: 'POST',
                body: formData,
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                mode: 'cors'
            });
            
            console.log('Respuesta recibida, estado:', response.status);
            
            // Leer la respuesta como texto primero para depuración
            const responseText = await response.text();
            let responseData;
            
            try {
                responseData = JSON.parse(responseText);
            } catch (e) {
                console.error('Error al analizar la respuesta JSON:', e);
                console.error('Respuesta del servidor (texto):', responseText);
                throw new Error('Error inesperado en el servidor. Por favor, inténtalo de nuevo.');
            }
            
            if (!response.ok) {
                console.error('Error en la respuesta:', responseData);
                
                // Manejar errores específicos de CSRF
                if (response.status === 403) {
                    if (responseData.error_code === 'csrf_missing' || 
                        responseData.error_code === 'csrf_invalid') {
                        // Recargar la página para obtener un nuevo token
                        console.log('Token CSRF inválido o faltante, recargando página...');
                        window.location.reload();
                        return;
                    }
                }
                
                const errorMessage = responseData.message || 
                    `Error en el servidor (${response.status})`;
                throw new Error(errorMessage);
            }
            
            if (responseData.success) {
                showAlert(
                    footerNewsletterMessage, 
                    responseData.message || '¡Gracias por suscribirte!', 
                    'success'
                );
                
                // Limpiar el campo de email
                if (footerNewsletterEmail) {
                    footerNewsletterEmail.value = '';
                }
                
                // Deshabilitar temporalmente el formulario
                footerNewsletterForm.reset();
                submitButton.disabled = true;
                
                // Mostrar mensaje de éxito por más tiempo
                setTimeout(() => {
                    if (footerNewsletterMessage) {
                        footerNewsletterMessage.innerHTML = '';
                    }
                }, 10000);
            } else {
                throw new Error(responseData.message || 'Error al procesar la suscripción');
            }
        } catch (error) {
            console.error('Error al enviar el formulario:', error);
            showAlert(
                footerNewsletterMessage, 
                error.message || 'Error al conectar con el servidor. Por favor, inténtalo de nuevo más tarde.', 
                'error'
            );
        } finally {
            // Restaurar botón
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
        }
    }
    
    // Agregar el event listener al formulario
    footerNewsletterForm.addEventListener('submit', handleFormSubmit);
    console.log('Evento submit agregado al formulario exitosamente');
    
    // Mostrar mensaje de éxito
    console.log('Formulario de newsletter inicializado correctamente');
}

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM completamente cargado, inicializando formulario...');
    initNewsletterForm();
});
