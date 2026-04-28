/**
 * Script de validación y envío del formulario de registro
 * Maneja la validación en tiempo real, el envío de datos al servidor
 * y la retroalimentación visual al usuario
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const form = document.getElementById('registerForm');
    const fullNameInput = document.getElementById('fullName');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const termsCheckbox = document.getElementById('terms');
    const registerButton = document.getElementById('registerButton');
    const notification = document.getElementById('notification');
    
    // Expresiones regulares para validación
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,100}$/;
    
    // Estado del formulario
    let isFormValid = false;
    
    // Inicializar eventos
    initFormEvents();
    
    /**
     * Inicializa los eventos del formulario
     */
    function initFormEvents() {
        // Validación en tiempo real
        fullNameInput.addEventListener('input', validateName);
        emailInput.addEventListener('input', validateEmail);
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        termsCheckbox.addEventListener('change', validateTerms);
        
        // Mostrar/ocultar contraseña
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', togglePasswordVisibility);
        });
        
        // Envío del formulario
        form.addEventListener('submit', handleSubmit);
    }
    
    /**
     * Valida el campo de nombre completo
     */
    function validateName() {
        const name = fullNameInput.value.trim();
        const errorElement = document.getElementById('nameError');
        
        if (!name) {
            showError(fullNameInput, errorElement, 'El nombre es obligatorio');
            return false;
        }
        
        if (!nameRegex.test(name)) {
            showError(fullNameInput, errorElement, 'El nombre solo puede contener letras y espacios (2-100 caracteres)');
            return false;
        }
        
        showSuccess(fullNameInput, errorElement);
        return true;
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
        
        // Verificar si el correo ya está registrado (simulado)
        // En una implementación real, esto se haría con una petición al servidor
        
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
            updatePasswordRequirements(false, false, false);
            return false;
        }
        
        // Verificar requisitos de la contraseña
        const hasMinLength = password.length >= 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        
        updatePasswordRequirements(hasMinLength, hasUpperCase, hasNumber);
        
        if (!hasMinLength || !hasUpperCase || !hasNumber) {
            showError(passwordInput, errorElement, 'La contraseña no cumple con los requisitos');
            return false;
        }
        
        // Si la contraseña es válida, validar también la confirmación
        if (confirmPasswordInput.value) {
            validateConfirmPassword();
        }
        
        showSuccess(passwordInput, errorElement);
        return true;
    }
    
    /**
     * Actualiza la interfaz de requisitos de contraseña
     */
    function updatePasswordRequirements(hasLength, hasUpper, hasNumber) {
        const lengthElement = document.getElementById('length');
        const upperElement = document.getElementById('uppercase');
        const numberElement = document.getElementById('number');
        
        lengthElement.classList.toggle('valid', hasLength);
        upperElement.classList.toggle('valid', hasUpper);
        numberElement.classList.toggle('valid', hasNumber);
    }
    
    /**
     * Valida el campo de confirmación de contraseña
     */
    function validateConfirmPassword() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const errorElement = document.getElementById('confirmPasswordError');
        
        if (!confirmPassword) {
            showError(confirmPasswordInput, errorElement, 'Por favor, confirma tu contraseña');
            return false;
        }
        
        if (password !== confirmPassword) {
            showError(confirmPasswordInput, errorElement, 'Las contraseñas no coinciden');
            return false;
        }
        
        showSuccess(confirmPasswordInput, errorElement);
        return true;
    }
    
    /**
     * Valida el checkbox de términos y condiciones
     */
    function validateTerms() {
        const errorElement = document.getElementById('termsError');
        
        if (!termsCheckbox.checked) {
            showError(termsCheckbox, errorElement, 'Debes aceptar los términos y condiciones');
            return false;
        }
        
        showSuccess(termsCheckbox, errorElement);
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
        isFormValid = false;
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
     * Maneja el envío del formulario
     */
    async function handleSubmit(e) {
        e.preventDefault();
        
        // Validar todos los campos
        const isNameValid = validateName();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();
        const isTermsValid = validateTerms();
        
        // Verificar si todos los campos son válidos
        isFormValid = isNameValid && isEmailValid && isPasswordValid && 
                     isConfirmPasswordValid && isTermsValid;
        
        if (!isFormValid) {
            showNotification('Por favor, completa correctamente todos los campos', 'error');
            return;
        }
        
        // Mostrar carga
        registerButton.classList.add('loading');
        registerButton.disabled = true;
        
        try {
            // Datos del formulario
            const formData = {
                nombre: fullNameInput.value.trim(),
                email: emailInput.value.trim().toLowerCase(),
                password: passwordInput.value,
                terms: termsCheckbox.checked
            };
            
            // Enviar datos al servidor
            const response = await fetch('php/registro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Error en el registro');
            }
            
            // Mostrar mensaje de éxito
            showNotification('¡Registro exitoso! Redirigiendo...', 'success');
            
            // Redirigir después de 2 segundos
            setTimeout(() => {
                window.location.href = 'registro-exitoso.html';
            }, 2000);
            
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message || 'Error al procesar el registro. Por favor, inténtalo de nuevo.', 'error');
        } finally {
            // Ocultar carga
            registerButton.classList.remove('loading');
            registerButton.disabled = false;
        }
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
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('blur', function() {
            switch(this.id) {
                case 'fullName':
                    validateName();
                    break;
                case 'email':
                    validateEmail();
                    break;
                case 'password':
                    validatePassword();
                    break;
                case 'confirmPassword':
                    validateConfirmPassword();
                    break;
            }
            validateForm();
        });
    });

    // Efecto de partículas flotantes
    function createParticles() {
        // Crear partículas
        const particle1 = document.createElement('div');
        particle1.className = 'particle';
        document.body.appendChild(particle1);

        const particle2 = document.createElement('div');
        particle2.className = 'particle';
        document.body.appendChild(particle2);
    }

    // Inicializar partículas cuando el DOM esté completamente cargado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createParticles);
    } else {
        createParticles();
    }
});
