document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.querySelector('.newsletter-form');
    if (!newsletterForm) return;
    
    const newsletterInput = newsletterForm.querySelector('input[type="email"]');
    const messageDiv = newsletterForm.querySelector('.alert-message');
    
    // Mostrar mensaje con animación
    function showMessage(message, type) {
        messageDiv.textContent = message;
        // Primero asegurarse de que está oculto
        messageDiv.className = 'alert-message';
        
        // Forzar un reflow
        void messageDiv.offsetWidth;
        
        // Agregar la clase para mostrar con animación
        messageDiv.classList.add(type, 'show');
        
        // Si es un mensaje de éxito, limpiar después de 5 segundos
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.classList.remove('show', 'success');
                // Esperar a que termine la animación para limpiar el texto
                setTimeout(() => {
                    if (!messageDiv.classList.contains('show')) {
                        messageDiv.textContent = '';
                    }
                }, 300);
            }, 5000);
        } else {
            // Para mensajes de error, mantenerlos hasta que se intente de nuevo
            // pero asegurarse de que no se acumulen los eventos
            clearTimeout(window.errorMessageTimeout);
            window.errorMessageTimeout = setTimeout(() => {
                messageDiv.classList.remove('show', 'error');
                setTimeout(() => {
                    if (!messageDiv.classList.contains('show')) {
                        messageDiv.textContent = '';
                    }
                }, 300);
            }, 10000); // 10 segundos para mensajes de error
        }
    }
    
    // Validar formato de email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Manejar el envío del formulario
    newsletterForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Limpiar mensajes anteriores
        if (messageDiv.classList.contains('show')) {
            messageDiv.classList.remove('show', 'success', 'error');
            // Esperar a que termine la animación de salida
            await new Promise(resolve => setTimeout(resolve, 300));
        }
        
        const email = newsletterInput.value.trim();
        
        // Validar campo vacío
        if (!email) {
            showMessage('Por favor, ingresa tu correo electrónico', 'error');
            newsletterInput.focus();
            return;
        }
        
        // Validar formato de email
        if (!isValidEmail(email)) {
            showMessage('Por favor, ingresa un correo electrónico válido', 'error');
            newsletterInput.focus();
            return;
        }
        
        // Mostrar indicador de carga
        const submitBtn = newsletterForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        
        try {
            const formData = new FormData();
            formData.append('email', email);
            
            const response = await fetch('/php/newsletter.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(data.message || '¡Gracias por suscribirte!', 'success');
                newsletterForm.reset();
            } else {
                showMessage(data.message || 'Error al procesar la solicitud. Por favor, inténtalo de nuevo.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Error al conectar con el servidor. Por favor, inténtalo de nuevo más tarde.', 'error');
        } finally {
            // Restaurar el botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
});
