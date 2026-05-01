// Configuración global
const CONFIG = {
    itemsPerPage: 10,
    currentPage: 1,
    totalItems: 0
};

// Utilidades
const Utils = {
    formatDate: (dateString) => {
        if (!dateString) return 'Fecha no disponible';
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    },
    
    showLoading: (element) => {
        if (!element) return;
        element.innerHTML = `
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Cargando...
            </div>`;
    },
    
    showError: (message, element) => {
        if (!element) return;
        element.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> ${message}
            </div>`;
    },
    
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Servicios de la API
const ApiService = {
    baseUrl: 'api/newsletter.php',
    
    // Obtener todos los suscriptores
    async getSubscribers(page = 1, limit = 10) {
        try {
            console.log(`Solicitando suscriptores - página ${page}, límite ${limit}`);
            const response = await fetch(`${this.baseUrl}?page=${page}&limit=${limit}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                },
                cache: 'no-store'
            });
            
            console.log('Respuesta recibida, estado:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error en la respuesta:', errorText);
                throw new Error('Error al cargar los suscriptores');
            }
            
            const data = await response.json();
            console.log('Datos recibidos de la API:', data);
            
            // Si la respuesta es exitosa pero no hay datos, devolver un array vacío
            if (data.success && !data.data) {
                console.log('No hay datos en la respuesta, devolviendo array vacío');
                return { 
                    success: true, 
                    data: [],
                    total: 0,
                    totalActive: 0,
                    stats: {
                        yesterday: 0,
                        today: 0,
                        lastWeek: 0
                    }
                };
            }
            
            // Asegurarse de que data.stats existe
            if (!data.stats) {
                console.warn('La respuesta de la API no incluye estadísticas (data.stats)');
                data.stats = { 
                    yesterday: 0,
                    today: 0,
                    lastWeek: 0
                };
            }
            
            // Asegurarse de que los totales existen
            data.total = data.total || 0;
            data.totalActive = data.totalActive || 0;
            
            return data;
        } catch (error) {
            console.error('Error en getSubscribers:', error);
            throw error;
        }
    },
    
    // Eliminar un suscriptor
    async deleteSubscriber(email) {
        try {
            console.log(`[ApiService] Iniciando eliminación del suscriptor: ${email}`);
            
            // Crear el objeto de datos a enviar
            const requestData = { email };
            console.log('[ApiService] Datos de la solicitud:', requestData);
            
            const response = await fetch(this.baseUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Añadir cabecera para identificar peticiones AJAX
                },
                body: JSON.stringify(requestData),
                credentials: 'same-origin',
                cache: 'no-store' // Evitar caché
            });
            
            console.log(`[ApiService] Respuesta del servidor - Estado: ${response.status}`);
            
            // Verificar si la respuesta tiene contenido antes de intentar parsear JSON
            const responseText = await response.text();
            let responseData = {};
            
            try {
                responseData = responseText ? JSON.parse(responseText) : {};
                console.log('[ApiService] Datos de respuesta:', responseData);
            } catch (e) {
                console.error('[ApiService] Error al parsear la respuesta JSON:', e);
                console.error('[ApiService] Texto de respuesta:', responseText);
                throw new Error('Error al procesar la respuesta del servidor');
            }
            
            if (!response.ok) {
                const errorMessage = responseData.message || `Error ${response.status}: ${response.statusText}`;
                console.error(`[ApiService] Error en la respuesta: ${errorMessage}`);
                
                // Si es un error 404, verificar si el suscriptor ya no existe (lo que podría considerarse un éxito)
                if (response.status === 404 && responseData.message && responseData.message.includes('No se encontró el suscriptor')) {
                    console.log('[ApiService] El suscriptor ya no existe, considerando como eliminación exitosa');
                    return { success: true, message: 'El suscriptor ya no existe' };
                }
                
                throw new Error(errorMessage);
            }
            
            console.log('[ApiService] Eliminación exitosa');
            return responseData;
        } catch (error) {
            console.error('Error en deleteSubscriber:', error);
            throw error;
        }
    },
    
    // Exportar suscriptores a CSV
    async exportSubscribers() {
        try {
            const response = await fetch(`${this.baseUrl}?export=csv`, {
                method: 'GET'
            });
            
            if (!response.ok) {
                throw new Error('Error al exportar los suscriptores');
            }
            
            // Crear un enlace para descargar el archivo
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `suscriptores-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            return { success: true };
        } catch (error) {
            console.error('Error en exportSubscribers:', error);
            throw error;
        }
    }
};

// Controlador de la interfaz de usuario
const UIController = {
    elements: {},
    
    // Inicializar los elementos del DOM
    initElements: function() {
        try {
            this.elements = {
                subscribersList: document.getElementById('subscribersTable'), // Tabla completa
                pagination: document.getElementById('pagination'),
                exportBtn: document.getElementById('exportSubscribers'),
                searchInput: document.getElementById('searchSubscribers'),
                refreshBtn: document.getElementById('refreshSubscribers'),
                newsletterForm: document.getElementById('newsletterForm'),
                previewBtn: document.getElementById('previewNewsletter'),
                showingFrom: document.getElementById('showingFrom'),
                showingTo: document.getElementById('showingTo'),
                totalCount: document.getElementById('totalSubscribers')
            };
            console.log('Elementos del DOM inicializados:', this.elements);
            return true;
        } catch (error) {
            console.error('Error al inicializar los elementos del DOM:', error);
            return false;
        }
    },
    
    // Mostrar los suscriptores en la tabla
    renderSubscribers(subscribers, totalSubscribers = null) {
        const tbody = document.getElementById('subscribersTableBody');
        if (!tbody) {
            console.error('No se encontró el elemento con ID subscribersTableBody');
            return;
        }
        
        // Limpiar el contenedor
        tbody.innerHTML = '';
        
        if (!subscribers || subscribers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <i class="far fa-envelope-open fa-2x mb-2 text-muted"></i>
                        <p class="mb-0">No se encontraron suscriptores</p>
                    </td>
                </tr>`;
            
            // Actualizar contadores
            const showingFrom = document.getElementById('showingFrom');
            const showingTo = document.getElementById('showingTo');
            const totalCount = document.getElementById('totalSubscribers');
            
            if (showingFrom) showingFrom.textContent = '0';
            if (showingTo) showingTo.textContent = '0';
            if (totalCount) totalCount.textContent = '0';
            
            return;
        }
        
        // Renderizar los suscriptores
        subscribers.forEach(subscriber => {
            const email = subscriber.email || 'Correo no disponible';
            const name = subscriber.nombre || '';
            const subscribedAt = subscriber.fecha_registro || subscriber.subscribed_at || new Date().toISOString();
            // Usar is_active si está disponible, de lo contrario usar activo, y si no está definido, asumir activo
            const isActive = subscriber.is_active !== undefined ? subscriber.is_active : 
                             (subscriber.activo !== undefined ? subscriber.activo : true);
            const isConfirmed = subscriber.confirmado !== undefined ? subscriber.confirmado : true;
            
            const tr = document.createElement('tr');
            tr.setAttribute('data-email', email);
            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="fw-semibold">${name || email}</div>
                            <small class="text-muted">${email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge ${isActive ? 'bg-success' : 'bg-secondary'} toggle-status" 
                          style="cursor: pointer;" 
                          data-email="${email}" 
                          data-status="${isActive ? 1 : 0}">
                        ${isActive ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>${Utils.formatDate(subscribedAt)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-email="${email}" title="Eliminar suscriptor">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>`;
                
            tbody.appendChild(tr);
        });
        
        // Actualizar contadores de paginación
        if (totalSubscribers !== null) {
            const currentPage = CONFIG.currentPage || 1;
            const itemsPerPage = CONFIG.itemsPerPage || 10;
            const start = ((currentPage - 1) * itemsPerPage) + 1;
            const end = Math.min(start + subscribers.length - 1, totalSubscribers);
            
            const showingFrom = document.getElementById('showingFrom');
            const showingTo = document.getElementById('showingTo');
            const totalCount = document.getElementById('totalSubscribers');
            
            if (showingFrom) showingFrom.textContent = start.toLocaleString('es-ES');
            if (showingTo) showingTo.textContent = end.toLocaleString('es-ES');
            if (totalCount) totalCount.textContent = totalSubscribers.toLocaleString('es-ES');
        }
    },
    
    // Actualizar la paginación
    renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / CONFIG.itemsPerPage);
        const pagination = document.getElementById('pagination');
        if (!pagination) {
            console.error('No se encontró el elemento con ID pagination');
            return;
        }
        
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let paginationHTML = `
            <li class="page-item ${CONFIG.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${CONFIG.currentPage - 1}">Anterior</a>
            </li>`;
        
        for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `
                <li class="page-item ${i === CONFIG.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
        }
        
        paginationHTML += `
            <li class="page-item ${CONFIG.currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${CONFIG.currentPage + 1}">Siguiente</a>
            </li>`;
        
        pagination.innerHTML = paginationHTML;
    },
    
    // Actualizar las estadísticas
    updateStats(stats = {}) {
        console.log('Actualizando estadísticas con datos:', stats);
        
        // Actualizar contador total de suscriptores
        const totalElement = document.getElementById('total-subscribers');
        const totalTrendElement = document.getElementById('total-trend');
        if (totalElement) {
            const total = stats.total || 0;
            totalElement.textContent = total.toLocaleString('es-ES');
            totalTrendElement.innerHTML = '<i class="fas fa-check-circle text-success"></i> Total de suscriptores';
        }
        
        // Actualizar contador de nuevos suscriptores hoy vs ayer
        const newThisMonthElement = document.getElementById('monthly-subscribers');
        const monthTrendElement = document.getElementById('monthly-trend');
        if (newThisMonthElement) {
            const todayCount = stats.stats?.today || 0;
            const yesterdayCount = stats.stats?.yesterday || 0;
            newThisMonthElement.textContent = todayCount.toLocaleString('es-ES');
            
            // Calcular la diferencia porcentual respecto a ayer
            let trendText = '';
            let trendIcon = '';
            let trendClass = '';
            
            if (yesterdayCount === 0) {
                if (todayCount > 0) {
                    trendText = 'Nuevos suscriptores hoy';
                    trendIcon = '<i class="fas fa-arrow-up text-success"></i>';
                    trendClass = 'text-success';
                } else {
                    trendText = 'Sin nuevos suscriptores';
                    trendClass = 'text-muted';
                }
            } else {
                const difference = todayCount - yesterdayCount;
                const percentage = Math.round((difference / yesterdayCount) * 100);
                
                if (difference > 0) {
                    trendIcon = '<i class="fas fa-arrow-up text-success"></i>';
                    trendClass = 'text-success';
                    trendText = `+${difference} (${Math.abs(percentage)}%) respecto a ayer`;
                } else if (difference < 0) {
                    trendIcon = '<i class="fas fa-arrow-down text-danger"></i>';
                    trendClass = 'text-danger';
                    trendText = `${difference} (${Math.abs(percentage)}%) respecto a ayer`;
                } else {
                    trendText = 'Sin cambios respecto a ayer';
                    trendClass = 'text-muted';
                }
            }
            
            monthTrendElement.innerHTML = `${trendIcon ? trendIcon + ' ' : ''}<span class="${trendClass}">${trendText}</span>`;
        }
        
        // Actualizar contador de nuevos suscriptores esta semana
        const newThisWeekElement = document.getElementById('new-this-week');
        const weekTrendElement = document.getElementById('week-trend');
        if (newThisWeekElement) {
            const newThisWeek = stats.stats?.lastWeek || 0;
            newThisWeekElement.textContent = newThisWeek.toLocaleString('es-ES');
            
            // Calcular tendencia semanal comparando con la semana anterior (simplificado)
            const prevWeekCount = Math.max(0, newThisWeek - 1); // Simulación de datos de la semana anterior
            const trend = newThisWeek > prevWeekCount ? 'up' : 'down';
            const percentage = prevWeekCount > 0 
                ? Math.round(((newThisWeek - prevWeekCount) / prevWeekCount) * 100) 
                : 100;
                
            weekTrendElement.innerHTML = trend === 'up'
                ? `<i class="fas fa-arrow-up text-success"></i> ${percentage}% desde la semana pasada`
                : `<i class="fas fa-arrow-down text-danger"></i> ${Math.abs(percentage)}% desde la semana pasada`;
        }
    },
    
    // Mostrar mensaje de éxito
    showSuccess(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">&times;</button>`;
        
        const container = document.querySelector('.main-content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            // Eliminar el mensaje después de 5 segundos
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    },
    
    // Mostrar indicador de carga
    showLoading(id, message = 'Cargando...') {
        // Crear el elemento de carga si no existe
        let loadingElement = document.getElementById(id);
        if (!loadingElement) {
            loadingElement = document.createElement('div');
            loadingElement.id = id;
            loadingElement.className = 'loading-overlay';
            loadingElement.style.position = 'fixed';
            loadingElement.style.top = '0';
            loadingElement.style.left = '0';
            loadingElement.style.width = '100%';
            loadingElement.style.height = '100%';
            loadingElement.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
            loadingElement.style.display = 'flex';
            loadingElement.style.justifyContent = 'center';
            loadingElement.style.alignItems = 'center';
            loadingElement.style.zIndex = '9999';
            
            const spinner = document.createElement('div');
            spinner.className = 'spinner-border text-primary';
            spinner.role = 'status';
            
            const srOnly = document.createElement('span');
            srOnly.className = 'visually-hidden';
            srOnly.textContent = message;
            
            const messageElement = document.createElement('div');
            messageElement.className = 'ms-3';
            messageElement.textContent = message;
            
            const container = document.createElement('div');
            container.style.display = 'flex';
            container.style.alignItems = 'center';
            container.style.padding = '20px';
            container.style.backgroundColor = 'white';
            container.style.borderRadius = '8px';
            container.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
            
            container.appendChild(spinner);
            container.appendChild(messageElement);
            loadingElement.appendChild(container);
            
            document.body.appendChild(loadingElement);
        } else {
            // Si ya existe, actualizar el mensaje
            const messageElement = loadingElement.querySelector('.ms-3');
            if (messageElement) {
                messageElement.textContent = message;
            }
            loadingElement.style.display = 'flex';
        }
        
        // Retornar el ID para referencia futura
        return id;
    },
    
    // Ocultar indicador de carga
    hideLoading(id) {
        const loadingElement = document.getElementById(id);
        if (loadingElement) {
            loadingElement.style.display = 'none';
            // No eliminamos el elemento, solo lo ocultamos para reutilizarlo
        }
    },
    
    // Mostrar mensaje de error
    showError(message) {
        console.error('Mostrando error:', message);
        
        // Crear el elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger';
        alertDiv.style.margin = '15px';
        alertDiv.style.padding = '15px';
        alertDiv.style.borderRadius = '4px';
        alertDiv.style.backgroundColor = '#ffebee';
        alertDiv.style.borderLeft = '4px solid #f44336';
        alertDiv.style.color = '#b71c1c';
        
        // Crear el contenido HTML del mensaje de error
        const errorContent = document.createElement('div');
        errorContent.style.display = 'flex';
        errorContent.style.alignItems = 'center';
        errorContent.style.justifyContent = 'space-between';
        
        // Contenido del mensaje
        const messageContent = document.createElement('div');
        messageContent.style.display = 'flex';
        messageContent.style.alignItems = 'center';
        messageContent.style.gap = '10px';
        
        // Icono de error
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-circle';
        icon.style.fontSize = '1.2em';
        
        // Texto del mensaje
        const messageText = document.createElement('span');
        messageText.textContent = message;
        
        // Botón de cierre
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'close';
        closeButton.style.background = 'none';
        closeButton.style.border = 'none';
        closeButton.style.fontSize = '1.5em';
        closeButton.style.cursor = 'pointer';
        closeButton.style.color = '#b71c1c';
        closeButton.innerHTML = '&times;';
        
        // Construir la estructura del mensaje
        messageContent.appendChild(icon);
        messageContent.appendChild(messageText);
        errorContent.appendChild(messageContent);
        errorContent.appendChild(closeButton);
        alertDiv.appendChild(errorContent);
        
        // Insertar el mensaje al principio del contenido principal
        const container = document.querySelector('.main-content') || document.body;
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            // Configurar el botón de cierre
            closeButton.addEventListener('click', () => {
                alertDiv.style.opacity = '0';
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 300);
            });
            
            // Eliminar el mensaje después de 10 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.remove();
                        }
                    }, 300);
                }
            }, 10000);
        } else {
            // Si no se encuentra el contenedor, usar alert nativo
            alert('Error: ' + message);
        }
    }
};

// Controlador principal
const AppController = {
    // Almacenar los suscriptores para ordenación
    subscribers: [],
    // Inicializar la aplicación
    async init() {
        try {
            console.log('Inicializando aplicación...');
            
            // Inicializar los elementos del DOM
            console.log('Inicializando elementos del DOM...');
            
            // Verificar que los elementos críticos existen
            const criticalElements = [
                'subscribersTableBody',
                'pagination',
                'totalSubscribers',
                'showingFrom',
                'showingTo',
                'searchInput'
            ];
            
            const missingElements = criticalElements.filter(id => !document.getElementById(id));
            
            if (missingElements.length > 0) {
                console.error('Elementos del DOM no encontrados:', missingElements);
                throw new Error('No se pudieron encontrar elementos críticos del DOM');
            }
            
            console.log('Elementos del DOM inicializados correctamente');
            
            // Cargar los suscriptores
            console.log('Cargando suscriptores...');
            await this.loadSubscribers();
            
            // Configurar los manejadores de eventos
            console.log('Configurando manejadores de eventos...');
            this.setupEventListeners();
            
            console.log('Aplicación inicializada correctamente');
        } catch (error) {
            console.error('Error al inicializar la aplicación:', error);
            const errorMessage = error.message || 'Error al cargar los datos. Por favor, recarga la página.';
            
            // Mostrar mensaje de error en la interfaz
            const errorContainer = document.createElement('div');
            errorContainer.className = 'alert alert-danger m-3';
            errorContainer.innerHTML = `
                <h5 class="alert-heading">¡Error!</h5>
                <p>${errorMessage}</p>
                <hr>
                <p class="mb-0">Por favor, recarga la página e inténtalo de nuevo.</p>
            `;
            
            // Insertar el mensaje de error al principio del contenedor principal
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.insertBefore(errorContainer, mainContent.firstChild);
            } else {
                // Si no se encuentra el contenedor principal, mostrar en el body
                document.body.innerHTML = '';
                document.body.appendChild(errorContainer);
            }
        }
    },
    
    // Cargar los suscriptores
    async loadSubscribers(page = 1) {
        // Evitar múltiples solicitudes simultáneas
        if (this.isLoading) return;
        
        console.log(`Cargando suscriptores, página ${page}...`);
        this.isLoading = true;
        
        // Mostrar indicador de carga
        const subscribersList = document.getElementById('subscribersTableBody');
        if (subscribersList) {
            subscribersList.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </td>
                </tr>`;
        }

        try {
            // Obtener los suscriptores con estadísticas en una sola llamada
            console.log(`Solicitando página ${page} con ${CONFIG.itemsPerPage} elementos por página`);
            const data = await ApiService.getSubscribers(page, CONFIG.itemsPerPage);
            console.log('Datos recibidos en loadSubscribers:', data);
            
            if (data && data.success) {
                // Almacenar los suscriptores para ordenación
                this.subscribers = data.data || [];
                
                // Verificar si la página actual está vacía pero no es la primera
                if (this.subscribers.length === 0 && page > 1) {
                    console.log(`La página ${page} está vacía, cargando página anterior...`);
                    // Cargar la página anterior
                    return this.loadSubscribers(page - 1);
                }
                
                // Actualizar el estado de la página actual
                CONFIG.currentPage = page;
                CONFIG.totalItems = data.total || 0;
                
                // Renderizar los suscriptores
                UIController.renderSubscribers(this.subscribers, CONFIG.totalItems);
                
                // Actualizar la paginación
                UIController.renderPagination(CONFIG.totalItems);
                
                // Actualizar las estadísticas con los datos ya recibidos
                UIController.updateStats({
                    total: data.total || 0,
                    today: data.stats?.today || 0,
                    yesterday: data.stats?.yesterday || 0,
                    lastWeek: data.stats?.lastWeek || 0
                });
                
                return this.subscribers;
            } else {
                throw new Error(data?.message || 'Error al cargar los suscriptores');
            }
        } catch (error) {
            console.error('Error al cargar los suscriptores:', error);
            
            // Mostrar mensaje de error en la interfaz
            const errorMessage = error.message || 'Error al cargar los suscriptores. Por favor, inténtalo de nuevo.';
            
            if (subscribersList) {
                subscribersList.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${errorMessage}
                        </td>
                    </tr>`;
            } else {
                UIController.showError(errorMessage);
            }
            
            return [];
        } finally {
            // Restablecer la bandera de carga
            this.isLoading = false;
        }
    },
    
    // Eliminar un suscriptor
    async deleteSubscriber(email) {
        if (!confirm(`¿Estás seguro de que deseas eliminar al suscriptor ${email}?`)) {
            return;
        }
        
        // Mostrar indicador de carga
        const loadingId = `loading-${Date.now()}`;
        UIController.showLoading(loadingId, 'Eliminando suscriptor...');
        
        try {
            console.log(`[AppController] Iniciando eliminación del suscriptor: ${email}`);
            
            // Mostrar indicador de carga en la tabla
            const subscribersList = document.getElementById('subscribersTableBody');
            if (subscribersList) {
                subscribersList.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Eliminando suscriptor...</span>
                            </div>
                        </td>
                    </tr>`;
            }
            
            // Llamar al servicio para eliminar el suscriptor
            const result = await ApiService.deleteSubscriber(email);
            
            if (result && result.success) {
                console.log(`[AppController] Suscriptor ${email} eliminado correctamente`);
                UIController.showSuccess('Suscriptor eliminado correctamente');
                
                // Recargar los suscriptores manteniendo la página actual
                const currentPage = CONFIG.currentPage;
                await this.loadSubscribers(currentPage);
                
                // Actualizar estadísticas
                // Usar los datos de la respuesta de la eliminación si están disponibles
                if (result.stats) {
                    UIController.updateStats({
                        total: result.total || 0,
                        today: result.stats.today || 0,
                        yesterday: result.stats.yesterday || 0,
                        lastWeek: result.stats.lastWeek || 0
                    });
                } else {
                    // Si no hay estadísticas en la respuesta, hacer una nueva petición
                    const statsResponse = await ApiService.getSubscribers(1, 1);
                    if (statsResponse && statsResponse.success) {
                        UIController.updateStats({
                            total: statsResponse.total || 0,
                            today: statsResponse.stats?.today || 0,
                            yesterday: statsResponse.stats?.yesterday || 0,
                            lastWeek: statsResponse.stats?.lastWeek || 0
                        });
                    }
                }
                
                // Si no hay más elementos en la página actual y no estamos en la primera página,
                // cargar la página anterior
                const remainingRows = document.querySelectorAll('#subscribersTableBody tr');
                if (remainingRows.length === 0 && currentPage > 1) {
                    console.log('No quedan elementos en la página actual, cargando página anterior...');
                    await this.loadSubscribers(currentPage - 1);
                }
            } else {
                const errorMessage = result?.message || 'Error desconocido al eliminar el suscriptor';
                console.error(`[AppController] Error al eliminar el suscriptor: ${errorMessage}`);
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('[AppController] Error al eliminar el suscriptor:', error);
            UIController.showError(`Error al eliminar el suscriptor: ${error.message || 'Error desconocido'}`);
            
            // Recargar la lista de todos modos para asegurar que esté actualizada
            try {
                await this.loadSubscribers(CONFIG.currentPage);
            } catch (loadError) {
                console.error('Error al recargar la lista de suscriptores:', loadError);
            }
        } finally {
            // Ocultar indicador de carga
            UIController.hideLoading(loadingId);
        }
    },
    
    // Exportar suscriptores a CSV
    async exportSubscribers() {
        try {
            const exportBtn = UIController.elements.exportBtn;
            if (exportBtn) {
                exportBtn.disabled = true;
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
            }
            
            await ApiService.exportSubscribers();
            UIController.showSuccess('Exportación completada correctamente');
            
        } catch (error) {
            console.error('Error al exportar los suscriptores:', error);
            UIController.showError(error.message || 'Error al exportar los suscriptores');
        } finally {
            const exportBtn = UIController.elements.exportBtn;
            if (exportBtn) {
                exportBtn.disabled = false;
                exportBtn.innerHTML = '<i class="fas fa-file-export"></i> Exportar a CSV';
            }
        }
    },
    
    // Ordenar los suscriptores
    sortSubscribers(field, direction) {
        console.log(`Ordenando por ${field} en orden ${direction}`);
        
        // Obtener los suscriptores actuales
        const subscribers = this.subscribers || [];
        
        // Ordenar los suscriptores
        const sortedSubscribers = [...subscribers].sort((a, b) => {
            let valueA, valueB;
            
            switch(field) {
                case 'email':
                    valueA = a.email ? a.email.toLowerCase() : '';
                    valueB = b.email ? b.email.toLowerCase() : '';
                    break;
                case 'status':
                    valueA = a.is_active !== undefined ? a.is_active : (a.activo !== undefined ? a.activo : true);
                    valueB = b.is_active !== undefined ? b.is_active : (b.activo !== undefined ? b.activo : true);
                    break;
                case 'date':
                    valueA = a.fecha_registro || a.subscribed_at || '';
                    valueB = b.fecha_registro || b.subscribed_at || '';
                    break;
                default:
                    return 0;
            }
            
            // Comparar los valores
            if (valueA < valueB) {
                return direction === 'asc' ? -1 : 1;
            }
            if (valueA > valueB) {
                return direction === 'asc' ? 1 : -1;
            }
            return 0;
        });
        
        // Actualizar la interfaz de usuario con los datos ordenados
        UIController.renderSubscribers(sortedSubscribers);
        
        // Actualizar el estado de ordenación en la interfaz
        document.querySelectorAll('.sortable').forEach(header => {
            if (header.dataset.sort === field) {
                header.classList.add('active');
                header.classList.toggle('asc', direction === 'asc');
                
                // Actualizar el ícono de ordenación
                const icon = header.querySelector('i');
                if (icon) {
                    icon.className = direction === 'asc' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
                }
            } else {
                header.classList.remove('active', 'asc');
                const icon = header.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort ms-1';
                }
            }
        });
    },
    
    // Enviar newsletter
    async sendNewsletter(formData) {
        try {
            const response = await fetch('../../php/send-newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message || 'Newsletter enviado exitosamente');
                // Limpiar el formulario después del envío exitoso
                if (UIController.elements.newsletterForm) {
                    UIController.elements.newsletterForm.reset();
                }
            } else {
                this.showError(result.message || 'Error al enviar el newsletter');
            }
        } catch (error) {
            console.error('Error al enviar el newsletter:', error);
            this.showError('Error de conexión al enviar el newsletter');
        }
    },
    
    // Configurar los manejadores de eventos
    setupEventListeners() {
        // Delegación de eventos para los botones de activo/inactivo
        document.addEventListener('click', async (e) => {
            // Manejar cambio de estado
            const statusBadge = e.target.closest('.toggle-status');
            if (statusBadge) {
                e.preventDefault();
                const email = statusBadge.dataset.email;
                const currentStatus = parseInt(statusBadge.dataset.status);
                const newStatus = currentStatus ? 0 : 1;
                
                if (email) {
                    try {
                        const response = await fetch('api/toggle-subscriber-status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                email: email,
                                status: newStatus
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Actualizar la interfaz
                            statusBadge.dataset.status = newStatus;
                            statusBadge.className = `badge ${newStatus ? 'bg-success' : 'bg-secondary'} toggle-status`;
                            statusBadge.textContent = newStatus ? 'Activo' : 'Inactivo';
                            
                            // Mostrar notificación de éxito
                            UIController.showSuccess(`Estado actualizado correctamente a ${newStatus ? 'Activo' : 'Inactivo'}`);
                            
                            // Recargar la lista de suscriptores para asegurar que todo esté actualizado
                            await this.loadSubscribers(CONFIG.currentPage);
                        } else {
                            throw new Error(result.message || 'Error al actualizar el estado');
                        }
                    } catch (error) {
                        console.error('Error al cambiar el estado:', error);
                        UIController.showError('Error al actualizar el estado del suscriptor: ' + error.message);
                    }
                }
                return;
            }
            
            // Manejar clics en los encabezados de la tabla para ordenar
            const sortHeader = e.target.closest('.sortable');
            if (sortHeader) {
                e.preventDefault();
                const sortField = sortHeader.dataset.sort;
                const isActive = sortHeader.classList.contains('active');
                const isAsc = sortHeader.classList.contains('asc');
                
                // Resetear todos los encabezados
                document.querySelectorAll('.sortable').forEach(header => {
                    header.classList.remove('active', 'asc');
                });
                
                // Establecer el estado del encabezado actual
                sortHeader.classList.add('active');
                if (isActive) {
                    sortHeader.classList.toggle('asc', !isAsc);
                } else {
                    sortHeader.classList.add('asc');
                }
                
                // Ordenar los datos
                const sortDirection = sortHeader.classList.contains('asc') ? 'asc' : 'desc';
                AppController.sortSubscribers(sortField, sortDirection);
                return;
            }
            
            // Botón de actualizar
            const refreshBtn = e.target.closest('#refreshSubscribers');
            if (refreshBtn) {
                e.preventDefault();
                location.reload();
                return;
            }
            
            // Eliminar suscriptor
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation(); // Evitar que el evento se propague
                
                const email = deleteBtn.dataset.email;
                if (!email) {
                    console.error('[ERROR] No se pudo obtener el email del botón de eliminar');
                    UIController.showError('No se pudo obtener el correo electrónico del suscriptor');
                    return;
                }
                
                // Llamar al método del controlador para manejar la eliminación
                await AppController.deleteSubscriber(email);
                return;
            }
        
        }); // Cierre del manejador de eventos principal
        
        // Manejador de eventos para paginación y exportación
        document.addEventListener('click', async (e) => {
            // Paginación
            const pageLink = e.target.closest('.page-link');
            if (pageLink) {
                e.preventDefault();
                const page = parseInt(pageLink.dataset.page);
                if (page && page !== CONFIG.currentPage) {
                    await this.loadSubscribers(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
                return;
            }
            
            // Exportar a CSV
            const exportBtn = e.target.closest('#exportSubscribers');
            if (exportBtn) {
                e.preventDefault();
                await this.exportSubscribers();
                return;
            }
        });
        
        // Búsqueda
        const searchInput = document.getElementById('searchSubscribers');
        if (searchInput) {
            const searchHandler = Util.debounce(async (e) => {
                const searchTerm = e.target.value.trim().toLowerCase();
                
                // Si el campo de búsqueda está vacío, recargar la lista completa
                if (searchTerm === '') {
                    await this.loadSubscribers(1);
                    return;
                }
                
                // Mostrar indicador de carga
                const tbody = document.querySelector('#subscribersTable tbody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin me-2"></i>Buscando suscriptores...
                            </td>
                        </tr>`;
                }
                
                try {
                    // Realizar la búsqueda en el servidor
                    const response = await fetch(`${ApiService.baseUrl}?search=${encodeURIComponent(searchTerm)}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache'
                        },
                        cache: 'no-store'
                    });
                    
                    if (!response.ok) {
                        throw new Error('Error al buscar suscriptores');
                    }
                    
                    const data = await response.json();
                    
                    // Mostrar los resultados de la búsqueda
                    if (data.success && data.data) {
                        UIController.renderSubscribers(data.data, data.data.length);
                    } else {
                        UIController.renderSubscribers([], 0);
                    }
                } catch (error) {
                    console.error('Error en la búsqueda:', error);
                    UIController.showError('Error al realizar la búsqueda. Intente de nuevo.');
                }
            }, 500);
            
            searchInput.addEventListener('input', searchHandler);
            
            // Manejar la tecla Enter para forzar la búsqueda
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchHandler(e);
                }
            });
        }
        
        // Formulario de newsletter
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(newsletterForm);
                const subject = formData.get('subject');
                const content = formData.get('content');
                
                if (!subject || !content) {
                    UIController.showError('Por favor, completa todos los campos');
                    return;
                }
                
                // Mostrar indicador de carga
                const submitBtn = newsletterForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                
                try {
                    await this.sendNewsletter({
                        subject: subject,
                        content: content
                    });
                } finally {
                    // Restaurar el botón
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        }
        
        // Botón de vista previa
        const previewBtn = document.getElementById('previewBtn');
        if (previewBtn) {
            previewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const subject = document.getElementById('subject').value;
                const content = document.getElementById('content').value;
                
                if (!subject || !content) {
                    this.showError('Por favor, completa el asunto y el contenido para ver la previsualización');
                    return;
                }
                
                // Abrir una nueva ventana con la previsualización
                const previewWindow = window.open('', '_blank');
                previewWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Vista Previa: ${subject}</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                line-height: 1.6; 
                                margin: 0;
                                padding: 0;
                                background-color: #f5f5f5;
                            }
                            .container { 
                                max-width: 600px; 
                                margin: 0 auto; 
                                padding: 20px; 
                                background: white;
                                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                            }
                            .header { 
                                background: #0a0e17; 
                                color: white; 
                                padding: 20px; 
                                text-align: center; 
                                margin-bottom: 20px;
                            }
                            .content { 
                                padding: 20px; 
                                color: #333;
                            }
                            .footer { 
                                text-align: center; 
                                color: #666; 
                                padding: 20px; 
                                font-size: 0.9em;
                                border-top: 1px solid #eee;
                                margin-top: 20px;
                            }
                            img {
                                max-width: 100%;
                                height: auto;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>${subject}</h1>
                            </div>
                            <div class="content">
                                ${content}
                            </div>
                            <div class="footer">
                                <p>Este es un mensaje automático. Por favor, no respondas a este correo.</p>
                                <p>&copy; ${new Date().getFullYear()} KDTekh. Todos los derechos reservados.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                `);
                previewWindow.document.close();
            });
        }
        
        // Cerrar alertas
        document.addEventListener('click', (e) => {
            if (e.target.matches('.alert .close') || e.target.closest('.alert .close')) {
                const alert = e.target.closest('.alert');
                if (alert) {
                    alert.remove();
                }
            }
        });
    }
};

// Manejador global de errores para ignorar errores de extensiones
window.addEventListener('error', function(event) {
    // Ignorar errores específicos de extensiones del navegador
    if (event.message && 
        (event.message.includes('port') || 
         event.message.includes('extension') ||
         event.message.includes('chrome-extension'))) {
        event.preventDefault();
        console.warn('Error de extensión ignorado:', event.message);
        return true;
    }
    return false;
}, true);

// Iniciar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Manejar errores no capturados
    window.onerror = function(message, source, lineno, colno, error) {
        // Ignorar errores de extensiones
        if (message && (message.toString().includes('port') || 
                       message.toString().includes('extension'))) {
            console.warn('Error de extensión ignorado:', message);
            return true; // Suprime el manejo de errores por defecto
        }
        // Para otros errores, usar el manejador por defecto
        return false;
    };
    
    // Iniciar la aplicación
    AppController.init();
});
