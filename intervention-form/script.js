document.addEventListener('DOMContentLoaded', function() {
    // Set current date as default
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    document.getElementById('openingDate').value = formattedDate;

    // Set current time as default start time
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('startTime').value = `${hours}:${minutes}`;

    // Get all necessary elements
    const tasksContainer = document.getElementById('tasksContainer');
    const materialUsedContainer = document.getElementById('materialUsedContainer');
    const materialRemovedContainer = document.getElementById('materialRemovedContainer');
    
    // Add first rows by default - Solo una línea por defecto
    addTaskRow();
    
    // Manejador para el botón de impresión
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', showPrinterDialog);
    } else {
        console.error('No se encontró el botón de impresión');
    }

    // Manejador para el diálogo de selección de impresora
    function showPrinterDialog() {
        const dialog = document.getElementById('printerDialog');
        if (!dialog) {
            console.error('No se encontró el diálogo de impresión');
            return;
        }
        
        dialog.style.display = 'flex';
        
        // Manejador para el botón de cancelar
        const cancelBtn = document.getElementById('cancelPrint');
        if (cancelBtn) {
            // Eliminar manejadores anteriores para evitar duplicados
            cancelBtn.replaceWith(cancelBtn.cloneNode(true));
            document.getElementById('cancelPrint').addEventListener('click', function() {
                dialog.style.display = 'none';
            });
        }
        
        // Cerrar al hacer clic fuera del contenido
        dialog.addEventListener('click', function(e) {
            if (e.target === dialog) {
                dialog.style.display = 'none';
            }
        });
        
        // Manejadores para las opciones de impresión
        const printerOptions = document.querySelectorAll('.printer-option');
        printerOptions.forEach(option => {
            // Eliminar manejadores anteriores para evitar duplicados
            option.replaceWith(option.cloneNode(true));
            const newOption = document.querySelector(`[data-print-type="${option.dataset.printType}"]`);
            newOption.addEventListener('click', function() {
                const printType = this.getAttribute('data-print-type');
                dialog.style.display = 'none';
                handlePrint(printType);
            });
        });
    }

    document.getElementById('addMaterial').addEventListener('click', addMaterialRow);
    document.getElementById('addRemovedMaterial').addEventListener('click', addRemovedMaterialRow);
    
    // Handle remove buttons with event delegation
    document.addEventListener('click', function(e) {
        // Handle task removal
        if (e.target.classList.contains('remove-task')) {
            const taskItems = tasksContainer.querySelectorAll('.task-item');
            if (taskItems.length > 1) {
                e.target.closest('.task-item').remove();
            } else {
                alert('Debe haber al menos una tarea.');
            }
        } 
        // Handle material removal
        else if (e.target.classList.contains('remove-material')) {
            const container = e.target.closest('.material-container');
            const items = container.querySelectorAll('.material-item');
            if (items.length > 1) {
                e.target.closest('.material-item').remove();
            } else {
                alert('Debe haber al menos un ítem de material.');
            }
        }
    });
    
    // Calculate total time when end time changes
    const endTimeInput = document.getElementById('endTime');
    endTimeInput.addEventListener('change', calculateTotalTime);
    
    // Calculate total time when travel time changes
    const travelTimeInput = document.getElementById('travelTime');
    travelTimeInput.addEventListener('input', calculateTotalTime);
    
    // Print function
    function handlePrint(printType = 'a4') {
        // Aplicar estilos según el tipo de impresión
        document.body.classList.remove('print-thermal', 'print-a4');
        
        // Guardar estilos originales
        const originalStyles = {
            width: document.documentElement.style.width,
            maxWidth: document.documentElement.style.maxWidth,
            margin: document.documentElement.style.margin,
            padding: document.documentElement.style.padding
        };
        
        // Crear un estilo temporal para la impresión
        const style = document.createElement('style');
        style.id = 'print-styles';
        
        if (printType === 'thermal') {
            // Configuración para impresora térmica 80mm
            document.body.classList.add('print-thermal');
            
            style.textContent = `
                @page {
                    size: 80mm auto;
                    margin: 0;
                    padding: 0;
                }
                body, html {
                    width: 76mm !important;
                    max-width: 76mm !important;
                    margin: 0 auto !important;
                    padding: 1mm 2mm !important;
                    font-size: 9px !important;
                    line-height: 1.2 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .form-container {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-height: auto !important;
                    padding: 1mm !important;
                    margin: 0 !important;
                    box-shadow: none !important;
                    border: none !important;
                }
                .form-header {
                    flex-direction: column !important;
                    align-items: center !important;
                    text-align: center !important;
                    padding-bottom: 1mm !important;
                    margin-bottom: 1mm !important;
                }
                .company-info h1 {
                    font-size: 10px !important;
                    margin: 1mm 0 !important;
                    line-height: 1.1 !important;
                }
                .company-details {
                    font-size: 8px !important;
                    margin: 0.5mm 0 !important;
                    line-height: 1.1 !important;
                }
                input[type="text"],
                input[type="date"],
                input[type="time"],
                input[type="number"],
                select,
                textarea {
                    width: 100% !important;
                    font-size: 9px !important;
                    padding: 1mm !important;
                    margin: 0.5mm 0 !important;
                    border: 1px solid #000 !important;
                    background: #fff !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                    height: auto !important;
                    box-sizing: border-box !important;
                }
                .task-item,
                .material-item {
                    display: block !important;
                    margin-bottom: 1mm !important;
                    border: 1px solid #000 !important;
                    padding: 1mm !important;
                }
                .signatures {
                    display: flex !important;
                    justify-content: space-between !important;
                    margin-top: 3mm !important;
                }
                .signature-box {
                    width: 45% !important;
                    text-align: center !important;
                }
                .signature-line {
                    border-top: 1px solid #000 !important;
                    margin: 10px 0 2px !important;
                    height: 1px !important;
                }
                .form-actions,
                .btn-add,
                .remove-task,
                .remove-material,
                .no-print,
                #printerDialog {
                    display: none !important;
                }
            `;
        } else {
            // Configuración para impresión A4 (una sola hoja)
            document.body.classList.add('print-a4');
            style.textContent = `
                @page {
                    size: A4; /* 210mm x 297mm */
                    margin: 5mm; /* margen algo menor para ganar área útil */
                }
                html, body {
                    width: 210mm;
                    margin: 0 auto;
                    padding: 0;
                    background: #fff;
                    color: #000;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    font-family: Arial, sans-serif;
                    font-size: 8.9pt; /* ligero ajuste de tamaño */
                    line-height: 1.11;
                }
                .form-container {
                    width: 100%;
                    max-width: 198mm; /* 210 - (2 * 6mm) */
                    min-height: auto;
                    margin: 0 auto;
                    padding: 0;
                    box-shadow: none;
                    border: none;
                }
                .form-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 1.6mm;
                    padding-bottom: 1.2mm;
                    border-bottom: 1px solid #000;
                }
                .logo-container {
                    width: 32mm;
                    text-align: left;
                }
                .logo-container img { max-width: 100%; height: auto; }
                .company-info { text-align: center; flex-grow: 1; padding: 0 6mm; }
                .company-info h1 { font-size: 10pt; margin: 0 0 1mm 0; text-transform: uppercase; font-weight: bold; }
                .company-details { font-size: 7pt; margin: 0; line-height: 1.2; }
                .intervention-number { width: 26mm; text-align: right; }
                .intervention-number label { display: inline; margin-right: 2mm; }
                .intervention-number input { width: 13mm; text-align: center; padding: 0.8mm; border: 1px solid #000; }

                .form-section { margin-bottom: 1.2mm; page-break-inside: avoid; }
                .form-section h3 { font-size: 7.8pt; margin: 0 0 0.8mm 0; padding-bottom: 0.3mm; border-bottom: 0.3px solid #000; text-transform: uppercase; font-weight: bold; }

                .client-info { display: flex; flex-wrap: wrap; margin: 0 -2mm 2mm -2mm; }
                .client-info .form-group { flex: 1 0 50%; padding: 0 2mm; margin-bottom: 2mm; box-sizing: border-box; }

                .form-row { display: flex; flex-wrap: wrap; margin: 0 -2mm 2mm -2mm; }
                .form-row .form-group { flex: 1 0 30%; padding: 0 2mm; min-width: 0; box-sizing: border-box; }

                label { display: block; margin-bottom: 0.35mm; font-weight: bold; font-size: 7.2pt; }
                input[type="text"], input[type="date"], input[type="time"], input[type="number"], select, textarea {
                    width: 100%; padding: 0.35mm 0.85mm; border: 0.5px solid #000; background: #fff; font-size: 8.2pt; box-sizing: border-box; height: 4.6mm; line-height: 1.03;
                }
                textarea { min-height: 8.5mm; resize: none; padding: 0.35mm; line-height: 1.07; font-size: 7.7pt !important; }

                .tasks-container { margin-bottom: 1.6mm; width: 100%; }
                .task-item { display: flex; align-items: center; margin-bottom: 0.7mm; width: 100%; gap: 4px; }
                .task-description { flex: 1; min-width: 0; }
                .task-item textarea { width: 100%; min-height: 11mm; padding: 2px; margin: 0; font-size: 8.2pt !important; line-height: 1.15; border: 1px solid #000; resize: none; }
                .task-hours { display: flex; align-items: center; white-space: nowrap; margin-left: 4px; }
                .task-hours label { font-size: 7.8pt; margin-right: 2px; }
                .task-hours input { width: 26px !important; height: 17px !important; padding: 0 2px !important; margin: 0 !important; text-align: center; font-size: 7.8pt !important; border: 1px solid #000 !important; }

                .material-container { margin-bottom: 1.6mm; }
                .material-item { display: flex; margin-bottom: 0.6mm; }
                .material-item input { margin-right: 1mm; }
                .serial-input { width: 25mm !important; }
                .desc-input { flex-grow: 1; }
                .units-input { width: 10mm !important; text-align: center; }
                .notes-input { width: 25mm !important; }

                .signatures { display: flex; justify-content: space-between; margin-top: 1.6mm; padding-top: 1.2mm; border-top: 0.5px solid #000; }
                .signature-box { width: 40%; text-align: center; }
                .signature-line { border-top: 0.5px solid #000; margin: 6mm 0 0.5mm; width: 100%; }
                .signature-box p { margin: 0; font-size: 7.2pt; font-weight: bold; }

                /* Ocultar controles al imprimir */
                .form-actions, .btn-add, .remove-task, .remove-material, .no-print, #printerDialog { display: none !important; }

                /* Evitar cortes solo en elementos críticos */
                .form-section, .task-item, .signatures { break-inside: avoid; }
            `;
        }
        
        // Aplicar los estilos
        document.head.appendChild(style);
        
        // Función para limpiar después de imprimir
        const afterPrint = () => {
            // Limpiar estilos
            if (style && style.parentNode) {
                document.head.removeChild(style);
            }
            
            // Restaurar estilos originales
            document.documentElement.style.width = originalStyles.width || '';
            document.documentElement.style.maxWidth = originalStyles.maxWidth || '';
            document.documentElement.style.margin = originalStyles.margin || '';
            document.documentElement.style.padding = originalStyles.padding || '';
            
            // Eliminar el evento después de usarlo
            window.removeEventListener('afterprint', afterPrint);
        };
        
        // Agregar evento para limpiar después de imprimir
        window.addEventListener('afterprint', afterPrint);
        
        // Retrasar ligeramente la impresión para asegurar que los estilos se apliquen
        setTimeout(() => {
            window.print();
        }, 100);
    }
    
    // Function to reset styles after printing
    function resetPrintStyles(originalStyles) {
        document.documentElement.style.width = originalStyles.width;
        document.documentElement.style.maxWidth = originalStyles.maxWidth;
        document.documentElement.style.margin = originalStyles.margin;
        document.documentElement.style.padding = originalStyles.padding;
    }
    
    // Clear form functionality
    const clearBtn = document.getElementById('clearBtn');
    clearBtn.addEventListener('click', clearForm);
    
    // Save form functionality (local storage example)
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.addEventListener('click', saveForm);
    
    // Load saved data if exists
    loadSavedForm();
});

// Función para agregar una nueva fila de tarea
function addTaskRow() {
    const taskRow = document.createElement('div');
    taskRow.className = 'task-item';
    
    // Crear la estructura HTML de la tarea
    const taskHTML = `
        <div class="task-content">
            <div class="task-description">
                <textarea name="taskDescription" rows="1" placeholder="Descripción de la tarea"></textarea>
            </div>
            <div class="task-hours">
                <span>Horas:</span>
                <input type="number" name="taskHours" min="0" step="0.5" value="0.0">
            </div>
        </div>
        <button type="button" class="remove-task" title="Eliminar tarea">×</button>
    `;
    
    // Insertar el HTML en el elemento
    taskRow.innerHTML = taskHTML;
    
    // Obtener el contenedor de tareas y agregar la nueva tarea
    const tasksContainer = document.getElementById('tasksContainer');
    if (tasksContainer) {
        tasksContainer.appendChild(taskRow);
        
        // Agregar evento al botón de eliminar
        const removeBtn = taskRow.querySelector('.remove-task');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                const taskItems = document.querySelectorAll('.task-item');
                if (taskItems.length > 1) {
                    taskRow.remove();
                } else {
                    alert('Debe haber al menos una tarea.');
                }
            });
        }
        
        // Auto-ajustar el textarea
        const textarea = taskRow.querySelector('textarea');
        if (textarea) {
            // Función para auto-ajustar el textarea
            const autoResizeTextarea = function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            };
            
            // Aplicar el evento de auto-ajuste
            textarea.addEventListener('input', autoResizeTextarea);
            
            // Disparar el evento input para ajustar la altura inicial
            setTimeout(() => {
                textarea.dispatchEvent(new Event('input'));
            }, 0);
        }
        
        // Enfocar el textarea de la nueva tarea
        setTimeout(() => {
            const textarea = taskRow.querySelector('textarea');
            if (textarea) textarea.focus();
        }, 10);
    }
    
    return taskRow;
}

// Asegurarse de que exista al menos una tarea al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Si no hay tareas, agregar una al cargar
    const tasksContainer = document.getElementById('tasksContainer');
    if (tasksContainer && tasksContainer.children.length === 0) {
        addTaskRow();
    }
    
    // Agregar evento al botón de añadir tarea
    const addTaskBtn = document.getElementById('addTask');
    if (addTaskBtn) {
        addTaskBtn.addEventListener('click', addTaskRow);
    }
});

function autoResizeTextarea(e) {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
}

function calculateTotalTime() {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    const travelTime = parseFloat(document.getElementById('travelTime').value) || 0;
    
    if (startTime && endTime) {
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);
        
        let totalMinutes = (endHours * 60 + endMinutes) - (startHours * 60 + startMinutes);
        
        // If end time is on the next day
        if (totalMinutes < 0) {
            totalMinutes += 24 * 60;
        }
        
        // Add travel time (converted from minutes to hours)
        const totalHours = (totalMinutes + travelTime) / 60;
        
        // Round to nearest 0.5
        const roundedHours = Math.round(totalHours * 2) / 2;
        
        document.getElementById('totalTime').value = roundedHours.toFixed(1);
    }
}

function clearForm() {
    if (confirm('¿Estás seguro de que quieres borrar todos los datos del formulario?')) {
        document.getElementById('interventionForm').reset();
        
        // Reset date and time
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        document.getElementById('openingDate').value = formattedDate;
        
        // Reset time inputs
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('startTime').value = `${hours}:${minutes}`;
        document.getElementById('endTime').value = '';
        document.getElementById('totalTime').textContent = '00:00';
        
        // Reset tasks - Solo una línea por defecto
        const tasksContainer = document.getElementById('tasksContainer');
        tasksContainer.innerHTML = '';
        addTaskRow();
        
        // Reset material sections - Vaciar sin añadir filas por defecto
        const materialUsedContainer = document.getElementById('materialUsedContainer');
        materialUsedContainer.innerHTML = '';
        
        const materialRemovedContainer = document.getElementById('materialRemovedContainer');
        materialRemovedContainer.innerHTML = '';
    }
}

function saveForm() {
    const formData = {
        interventionNum: document.getElementById('interventionNum').value,
        openingDate: document.getElementById('openingDate').value,
        clientName: document.getElementById('clientName').value,
        address: document.getElementById('address').value,
        city: document.getElementById('city').value,
        problemDescription: document.getElementById('problemDescription').value,
        tasks: [],
        serviceType: document.getElementById('serviceType').value,
        startTime: document.getElementById('startTime').value,
        endTime: document.getElementById('endTime').value,
        travelTime: document.getElementById('travelTime').value,
        totalTime: document.getElementById('totalTime').value,
        observations: document.getElementById('observations').value
    };
    
    // Save tasks
    const taskItems = document.querySelectorAll('.task-item');
    taskItems.forEach(item => {
        const description = item.querySelector('textarea').value;
        const hours = item.querySelector('input[type="number"]').value;
        if (description || hours) {
            formData.tasks.push({
                description,
                hours
            });
        }
    });
    
    // Create XML
    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<interventionForm>\n';
    xml += objectToXml(formData, 'data').replace(/></g, '>\n<');
    xml += '\n</interventionForm>';
    
    // Create and download the XML file
    const blob = new Blob([xml], { type: 'application/xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const fileName = `intervencion_${formData.interventionNum}_${formData.clientName || 'cliente'}.xml`;
    
    alert('Formulario guardado correctamente.');
}

function loadSavedForm() {
    const savedData = localStorage.getItem('savedInterventionForm');
    if (savedData) {
        if (confirm('¿Desea cargar los datos guardados anteriormente?')) {
            const formData = JSON.parse(savedData);
            
            // Fill basic fields
            document.getElementById('interventionNum').value = formData.interventionNum || '';
            document.getElementById('openingDate').value = formData.openingDate || '';
            document.getElementById('clientName').value = formData.clientName || '';
            document.getElementById('address').value = formData.address || '';
            document.getElementById('city').value = formData.city || '';
            document.getElementById('problemDescription').value = formData.problemDescription || '';
            document.getElementById('serviceType').value = formData.serviceType || '';
            document.getElementById('startTime').value = formData.startTime || '';
            document.getElementById('endTime').value = formData.endTime || '';
            document.getElementById('travelTime').value = formData.travelTime || '';
            document.getElementById('totalTime').value = formData.totalTime || '';
            document.getElementById('observations').value = formData.observations || '';
            
            // Clear existing tasks
            const tasksContainer = document.getElementById('tasksContainer');
            tasksContainer.innerHTML = '';
            
            // Add saved tasks
            if (formData.tasks && formData.tasks.length > 0) {
                formData.tasks.forEach(task => {
                    const taskRow = addTaskRow();
                    taskRow.querySelector('textarea').value = task.description || '';
                    taskRow.querySelector('input[type="number"]').value = task.hours || '';
                });
            } else {
                // Add at least one empty task
                addTaskRow();
            }
            
            alert('Datos cargados correctamente.');
        }
    }
}

// Auto-resize textareas when content changes
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', autoResizeTextarea);
});

function addMaterialRow() {
const materialItem = document.createElement('div');
materialItem.className = 'material-item';
materialItem.innerHTML = `
    <input type="text" name="serial" placeholder="Nº de serie" class="serial-input">
    <input type="text" name="description" placeholder="Descripción" class="desc-input">
    <input type="number" name="units" min="0" placeholder="Uds" class="units-input">
    <button type="button" class="remove-material">×</button>
`;
document.getElementById('materialUsedContainer').appendChild(materialItem);
}

function addTaskRow() {
    const taskItem = document.createElement('div');
    taskItem.className = 'task-item';
    taskItem.innerHTML = `
        <textarea name="taskDescription" rows="1" placeholder="Descripción de la tarea"></textarea>
        <input type="number" name="taskHours" min="0" step="0.5" placeholder="Horas" class="hours-input">
        <button type="button" class="remove-task">×</button>
    `;
    document.getElementById('tasksContainer').appendChild(taskItem);
    
    // Auto-resize the new textarea
    const textarea = taskItem.querySelector('textarea');
    textarea.addEventListener('input', autoResizeTextarea);
}

function addMaterialRow() {
    const materialItem = document.createElement('div');
    materialItem.className = 'material-item';
    materialItem.innerHTML = `
        <input type="text" name="serial" placeholder="Nº de serie" class="serial-input">
        <input type="text" name="description" placeholder="Descripción" class="desc-input">
        <input type="number" name="units" min="0" placeholder="Uds" class="units-input">
        <button type="button" class="remove-material">×</button>
    `;
    document.getElementById('materialUsedContainer').appendChild(materialItem);
}

function addRemovedMaterialRow() {
    const materialItem = document.createElement('div');
    materialItem.className = 'material-item';
    materialItem.innerHTML = `
        <input type="text" name="removedSerial" placeholder="Nº de serie" class="serial-input">
        <input type="text" name="removedDesc" placeholder="Descripción" class="desc-input">
        <input type="number" name="removedUnits" min="0" placeholder="Uds" class="units-input">
        <input type="text" name="removedNotes" placeholder="Observaciones" class="notes-input">
        <button type="button" class="remove-material">×</button>
    `;
    document.getElementById('materialRemovedContainer').appendChild(materialItem);
}
