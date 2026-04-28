/**
 * Script de validación y envío del formulario de inicio de sesión
 * Maneja la autenticación del usuario y la gestión de sesiones
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const loginButton = document.getElementById('loginButton');
    const notification = document.getElementById('notification');
    const loginError = document.getElementById('loginError');
    
    // Expresiones regulares para validación
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    // Inicializar eventos
    initLoginEvents();
    
    // Verificar si hay credenciales guardadas
    checkRememberedUser();
    
    /**
     * Inicializa los eventos del formulario
     */
    function initLoginEvents() {
        // Validación en tiempo real
        emailInput.addEventListener('input', validateEmail);
        passwordInput.addEventListener('input', validatePassword);
        
        // Mostrar/ocultar contraseña
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', togglePasswordVisibility);
        });
        
        // Envío del formulario
        loginForm.addEventListener('submit', handleLogin);
    }
    
    /**
     * Verifica si hay un usuario recordado
     */
    function checkRememberedUser() {
        const rememberedEmail = localStorage.getItem('rememberedEmail');
        if (rememberedEmail) {
            emailInput.value = rememberedEmail;
            rememberCheckbox.checked = true;
            passwordInput.focus();
        }
    }
    
    /**
     * Valida el campo de correo electrónico
     */
    function validateEmail() {
        const email = emailInput.value.trim();
        const errorElement = document.getElementById('emailError');
        
        if (!email) {
            showError(emailInput, errorElement, 'El correo electrónico es obligatorio');
            return false;
        }
        
        if (!emailRegex.test(email)) {
            showError(emailInput, errorElement, 'Por favor, introduce un correo electrónico válido');
            return false;
        }
        
        showSuccess(emailInput, errorElement);
        return true;
    }
    
    /**
     * Valida el campo de contraseña
     */
    function validatePassword() {
        const password = passwordInput.value;
        const errorElement = document.getElementById('passwordError');
        
        if (!password) {
            showError(passwordInput, errorElement, 'La contraseña es obligatoria');
            return false;
        }
        
        showSuccess(passwordInput, errorElement);
        return true;
    }
    
    /**
     * Muestra un mensaje de error para un campo
     */
    function showError(input, errorElement, message) {
        input.classList.add('error');
        input.classList.remove('success');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }
    
    /**
     * Marca un campo como válido
     */
    function showSuccess(input, errorElement) {
        input.classList.remove('error');
        input.classList.add('success');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }
    
    /**
     * Alterna la visibilidad de la contraseña
     */
    function togglePasswordVisibility(e) {
        const button = e.currentTarget;
        const icon = button.querySelector('i');
        const input = button.previousElementSibling;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    /**
     * Maneja el envío del formulario de inicio de sesión
     */
    async function handleLogin(e) {
        e.preventDefault();
        
        // Validar campos
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        
        if (!isEmailValid || !isPasswordValid) {
            showNotification('Por favor, completa correctamente todos los campos', 'error');
            return;
        }
        
        // Mostrar carga
        loginButton.classList.add('loading');
        loginButton.disabled = true;
        
        try {
            // Datos del formulario
            const formData = {
                email: emailInput.value.trim().toLowerCase(),
                password: passwordInput.value,
                remember: rememberCheckbox.checked
            };
            
            // Enviar datos al servidor
            const response = await fetch('php/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Error en el inicio de sesión');
            }
            
            // Guardar correo si el usuario lo desea
            if (formData.remember) {
                localStorage.setItem('rememberedEmail', formData.email);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
            
            // Mostrar mensaje de éxito
            showNotification('Inicio de sesión exitoso. Redirigiendo...', 'success');
            
            // Redirigir después de 1.5 segundos
            setTimeout(() => {
                window.location.href = data.redirect || 'index.html';
            }, 1500);
            
        } catch (error) {
            console.error('Error:', error);
            showLoginError(error.message || 'Error al iniciar sesión. Verifica tus credenciales.');
        } finally {
            // Ocultar carga
            loginButton.classList.remove('loading');
            loginButton.disabled = false;
        }
    }
    
    /**
     * Muestra un mensaje de error de inicio de sesión
     */
    function showLoginError(message) {
        loginError.textContent = message;
        loginError.style.display = 'block';
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            loginError.style.display = 'none';
        }, 5000);
    }
    
    /**
     * Muestra una notificación
     */
    function showNotification(message, type = 'info') {
        notification.textContent = message;
        notification.className = `notification show ${type}`;
        
        // Ocultar notificación después de 5 segundos
        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }
    
    // Validar formulario al perder el foco
    loginForm.querySelectorAll('input').forEach(input => {
        input.addEventListener('blur', function() {
            switch(this.id) {
                case 'email':
                    validateEmail();
                    break;
                case 'password':
                    validatePassword();
                    break;
            }
        });
    });
});
