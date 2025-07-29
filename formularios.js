/**
 * JAVASCRIPT PARA FORMULARIOS DE GESTIÓN
 * Archivo: formularios.js
 * Propósito: Validaciones y funcionalidades para formularios.html
 */

// Variables globales
let currentTab = 'proyectos';
let validationRules = {};
let formData = {};

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de formularios...');
    
    initializeFormSystem();
    setupValidationRules();
    setupEventListeners();
    loadInitialData();
    
    console.log('✅ Sistema de formularios inicializado correctamente');
});

/**
 * Inicializar el sistema de formularios
 */
function initializeFormSystem() {
    // Configurar fechas mínimas
    const today = new Date().toISOString().split('T')[0];
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');
    
    if (fechaInicioInput) {
        fechaInicioInput.min = today;
        fechaInicioInput.addEventListener('change', updateMinEndDate);
    }
    
    if (fechaFinInput) {
        fechaFinInput.min = today;
    }
    
    // Mostrar pestaña inicial
    showTab('proyectos');
}

/**
 * Configurar reglas de validación
 */
function setupValidationRules() {
    validationRules = {
        proyecto: {
            nombre_proyecto: {
                required: true,
                minLength: 5,
                maxLength: 200,
                pattern: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-\.0-9]+$/,
                message: 'El nombre debe tener entre 5 y 200 caracteres, solo letras, números y espacios'
            },
            descripcion_proyecto: {
                required: true,
                minLength: 20,
                maxLength: 1000,
                message: 'La descripción debe tener entre 20 y 1000 caracteres'
            },
            presupuesto: {
                required: true,
                type: 'number',
                min: 1000,
                max: 1000000,
                message: 'El presupuesto debe estar entre $1,000 y $1,000,000'
            },
            fecha_inicio: {
                required: true,
                type: 'date',
                minDate: 'today',
                message: 'La fecha de inicio no puede ser anterior a hoy'
            },
            fecha_fin: {
                required: true,
                type: 'date',
                minDate: 'fecha_inicio',
                message: 'La fecha de fin debe ser posterior a la fecha de inicio'
            }
        },
        donante: {
            nombre_donante: {
                required: true,
                minLength: 3,
                maxLength: 150,
                pattern: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/,
                message: 'El nombre debe tener entre 3 y 150 caracteres, solo letras y espacios'
            },
            email_donante: {
                required: true,
                type: 'email',
                maxLength: 100,
                message: 'Ingrese un email válido (máximo 100 caracteres)'
            },
            direccion_donante: {
                required: false,
                minLength: 10,
                maxLength: 300,
                message: 'La dirección debe tener entre 10 y 300 caracteres'
            },
            telefono_donante: {
                required: false,
                pattern: /^[\+]?[\d\s\-\(\)]{8,20}$/,
                message: 'El teléfono debe tener entre 8 y 20 dígitos'
            }
        }
    };
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Listeners para validación en tiempo real
    document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
            if (this.value.length > 0) {
                setTimeout(() => validateField(this), 500);
            }
        });
    });
    
    // Listener para cambio de fecha de inicio
    const fechaInicio = document.getElementById('fecha_inicio');
    if (fechaInicio) {
        fechaInicio.addEventListener('change', updateMinEndDate);
    }
    
    // Listeners para formularios
    const formProyecto = document.getElementById('formProyecto');
    if (formProyecto) {
        formProyecto.addEventListener('submit', handleProyectoSubmit);
    }
    
    const formDonante = document.getElementById('formDonante');
    if (formDonante) {
        formDonante.addEventListener('submit', handleDonanteSubmit);
    }
    
    // Listeners para botones de actualización
    setupRefreshButtons();
}

/**
 * Configurar botones de actualización
 */
function setupRefreshButtons() {
    const refreshButtons = document.querySelectorAll('[onclick*="cargar"]');
    refreshButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('onclick');
            
            if (action.includes('cargarProyectos')) {
                loadProyectos();
            } else if (action.includes('cargarDonantes')) {
                loadDonantes();
            }
        });
    });
}

/**
 * Cambiar entre pestañas
 */
function showTab(tabName) {
    console.log(`📋 Cambiando a pestaña: ${tabName}`);
    
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Quitar clase active de todos los tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Mostrar contenido seleccionado
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.classList.add('active');
        currentTab = tabName;
    }
    
    // Marcar tab como activo
    const activeTabButton = Array.from(document.querySelectorAll('.nav-tab'))
        .find(tab => tab.textContent.toLowerCase().includes(tabName));
    
    if (activeTabButton) {
        activeTabButton.classList.add('active');
    }
    
    // Cargar datos específicos de la pestaña
    loadTabData(tabName);
}

/**
 * Cargar datos específicos de cada pestaña
 */
function loadTabData(tabName) {
    switch(tabName) {
        case 'proyectos':
            loadProyectos();
            break;
        case 'donantes':
            loadDonantes();
            break;
        case 'consultas':
            // Las consultas se cargan bajo demanda
            break;
    }
}

/**
 * Validar campo individual
 */
function validateField(field) {
    const fieldName = field.name || field.id;
    const formType = getFormType(field);
    const rules = validationRules[formType]?.[fieldName];
    
    if (!rules) return true;
    
    const value = field.value.trim();
    const errors = [];
    
    // Validación requerido
    if (rules.required && !value) {
        errors.push('Este campo es obligatorio');
    }
    
    // Validaciones solo si hay valor
    if (value) {
        // Longitud mínima
        if (rules.minLength && value.length < rules.minLength) {
            errors.push(`Mínimo ${rules.minLength} caracteres`);
        }
        
        // Longitud máxima
        if (rules.maxLength && value.length > rules.maxLength) {
            errors.push(`Máximo ${rules.maxLength} caracteres`);
        }
        
        // Patrón regex
        if (rules.pattern && !rules.pattern.test(value)) {
            errors.push(rules.message || 'Formato inválido');
        }
        
        // Validación de tipo
        if (rules.type) {
            switch(rules.type) {
                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        errors.push('Email inválido');
                    }
                    break;
                    
                case 'number':
                    const numValue = parseFloat(value);
                    if (isNaN(numValue)) {
                        errors.push('Debe ser un número válido');
                    } else {
                        if (rules.min && numValue < rules.min) {
                            errors.push(`Valor mínimo: ${rules.min}`);
                        }
                        if (rules.max && numValue > rules.max) {
                            errors.push(`Valor máximo: ${rules.max}`);
                        }
                    }
                    break;
                    
                case 'date':
                    const dateValue = new Date(value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (rules.minDate === 'today' && dateValue < today) {
                        errors.push('La fecha no puede ser anterior a hoy');
                    }
                    
                    if (rules.minDate === 'fecha_inicio') {
                        const fechaInicio = document.getElementById('fecha_inicio');
                        if (fechaInicio && fechaInicio.value) {
                            const fechaInicioValue = new Date(fechaInicio.value);
                            if (dateValue <= fechaInicioValue) {
                                errors.push('Debe ser posterior a la fecha de inicio');
                            }
                        }
                    }
                    break;
            }
        }
    }
    
    // Mostrar errores o marcar como válido
    if (errors.length > 0) {
        showFieldError(field, errors[0]);
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

/**
 * Obtener tipo de formulario
 */
function getFormType(field) {
    const form = field.closest('form');
    if (!form) return 'unknown';
    
    if (form.id === 'formProyecto') return 'proyecto';
    if (form.id === 'formDonante') return 'donante';
    
    return 'unknown';
}

/**
 * Mostrar error en campo
 */
function showFieldError(field, message) {
    const errorId = `error_${field.name || field.id}`;
    let errorElement = document.getElementById(errorId);
    
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.id = errorId;
        errorElement.className = 'error';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    errorElement.classList.add('show');
    field.classList.add('invalid');
    field.classList.remove('valid');
}

/**
 * Limpiar error de campo
 */
function clearFieldError(field) {
    const errorId = `error_${field.name || field.id}`;
    const errorElement = document.getElementById(errorId);
    
    if (errorElement) {
        errorElement.classList.remove('show');
        setTimeout(() => {
            if (!errorElement.classList.contains('show')) {
                errorElement.style.display = 'none';
            }
        }, 300);
    }
    
    field.classList.remove('invalid');
    if (field.value.trim()) {
        field.classList.add('valid');
    }
}

/**
 * Actualizar fecha mínima de fin
 */
function updateMinEndDate() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    if (fechaInicio && fechaFin && fechaInicio.value) {
        fechaFin.min = fechaInicio.value;
        
        // Validar fecha de fin si ya tiene valor
        if (fechaFin.value && fechaFin.value <= fechaInicio.value) {
            showFieldError(fechaFin, 'La fecha de fin debe ser posterior a la fecha de inicio');
        } else {
            clearFieldError(fechaFin);
        }
    }
}

/**
 * Validar formulario completo
 */
function validateForm(formType) {
    console.log(`🔍 Validando formulario: ${formType}`);
    
    const formId = formType === 'proyecto' ? 'formProyecto' : 'formDonante';
    const form = document.getElementById(formId);
    
    if (!form) {
        console.error(`❌ Formulario ${formId} no encontrado`);
        return false;
    }
    
    let isValid = true;
    const fields = form.querySelectorAll('input, textarea, select');
    
    // Validar cada campo
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Validaciones específicas por tipo de formulario
    if (formType === 'proyecto') {
        isValid = validateProyectoSpecific() && isValid;
    } else if (formType === 'donante') {
        isValid = validateDonanteSpecific() && isValid;
    }
    
    console.log(`${isValid ? '✅' : '❌'} Validación completada: ${isValid}`);
    return isValid;
}

/**
 * Validaciones específicas para proyectos
 */
function validateProyectoSpecific() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    if (fechaInicio && fechaFin && fechaInicio.value && fechaFin.value) {
        const inicio = new Date(fechaInicio.value);
        const fin = new Date(fechaFin.value);
        
        if (fin <= inicio) {
            showFieldError(fechaFin, 'La fecha de fin debe ser posterior a la fecha de inicio');
            return false;
        }
        
        // Verificar que el proyecto no dure más de 2 años
        const dosMesesAños = new Date(inicio);
        dosMesesAños.setFullYear(dosMesesAños.getFullYear() + 2);
        
        if (fin > dosMesesAños) {
            showFieldError(fechaFin, 'El proyecto no puede durar más de 2 años');
            return false;
        }
    }
    
    return true;
}

/**
 * Validaciones específicas para donantes
 */
function validateDonanteSpecific() {
    const email = document.getElementById('email_donante');
    const telefono = document.getElementById('telefono_donante');
    
    // Validación adicional de email (verificar dominios comunes)
    if (email && email.value) {
        const dominiosValidos = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'email.com'];
        const emailDomain = email.value.split('@')[1]?.toLowerCase();
        
        if (emailDomain && !dominiosValidos.some(dominio => emailDomain.includes(dominio)) && !emailDomain.includes('.cl')) {
            // Solo advertencia, no error
            console.warn('⚠️ Dominio de email poco común:', emailDomain);
        }
    }
    
    // Validación de teléfono chileno
    if (telefono && telefono.value) {
        const telefonoLimpio = telefono.value.replace(/[\s\-\(\)]/g, '');
        
        // Formato chileno: +569XXXXXXXX o 9XXXXXXXX
        if (!telefonoLimpio.match(/^(\+?56)?[9][0-9]{8}$/)) {
            showFieldError(telefono, 'Formato de teléfono chileno inválido (ej: +56912345678)');
            return false;
        }
    }
    
    return true;
}

/**
 * Manejar envío de formulario de proyecto
 */
function handleProyectoSubmit(event) {
    console.log('📤 Enviando formulario de proyecto...');
    
    if (!validateForm('proyecto')) {
        event.preventDefault();
        showFormMessage('error', 'Por favor corrija los errores antes de continuar');
        return false;
    }
    
    // Mostrar loading
    showFormLoading('formProyecto', true);
    
    // El formulario se enviará normalmente al procesar_proyecto.php
    return true;
}

/**
 * Manejar envío de formulario de donante
 */
function handleDonanteSubmit(event) {
    console.log('📤 Enviando formulario de donante...');
    
    if (!validateForm('donante')) {
        event.preventDefault();
        showFormMessage('error', 'Por favor corrija los errores antes de continuar');
        return false;
    }
    
    // Mostrar loading
    showFormLoading('formDonante', true);
    
    // El formulario se enviará normalmente al procesar_donante.php
    return true;
}

/**
 * Mostrar mensaje en formulario
 */
function showFormMessage(type, message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `${type}-message`;
    messageDiv.innerHTML = `<strong>${type === 'error' ? '❌' : '✅'}</strong> ${message}`;
    
    // Insertar al inicio del contenido activo
    const activeTab = document.querySelector('.tab-content.active');
    if (activeTab) {
        activeTab.insertBefore(messageDiv, activeTab.firstChild);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

/**
 * Mostrar/ocultar loading en formulario
 */
function showFormLoading(formId, show) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formCard = form.closest('.form-card');
    if (!formCard) return;
    
    if (show) {
        formCard.classList.add('processing');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
        }
    } else {
        formCard.classList.remove('processing');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.dataset.originalText || 'Enviar';
        }
    }
}

/**
 * Cargar datos iniciales
 */
function loadInitialData() {
    console.log('📊 Cargando datos iniciales...');
    
    // Cargar datos de la pestaña actual
    loadTabData(currentTab);
    
    // Configurar auto-refresh cada 30 segundos
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            loadTabData(currentTab);
        }
    }, 30000);
}

/**
 * Cargar proyectos (simulado - en producción haría fetch a API)
 */
function loadProyectos() {
    console.log('📋 Cargando proyectos...');
    
    const container = document.getElementById('listaProyectos');
    if (!container) return;
    
    // Mostrar loading
    container.innerHTML = `
        <div class="loading show">
            <div class="spinner"></div>
            <p>Cargando proyectos...</p>
        </div>
    `;
    
    // Simular carga de datos
    setTimeout(() => {
        const proyectosHTML = `
            <div class="data-display">
                <h4>📊 Proyectos Registrados</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Presupuesto</th>
                            <th>Inicio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Educación para Todos</td>
                            <td>$50,000</td>
                            <td>01/01/2025</td>
                            <td><span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">Activo</span></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Alimentación Comunitaria</td>
                            <td>$30,000</td>
                            <td>01/02/2025</td>
                            <td><span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">Activo</span></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Vivienda Digna</td>
                            <td>$75,000</td>
                            <td>01/03/2025</td>
                            <td><span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">Activo</span></td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                    💡 <strong>Tip:</strong> Los proyectos mostrados aquí son datos reales de la base de datos.
                    <a href="procesar_proyecto.php" style="color: #667eea;">Ver lista completa</a>
                </p>
            </div>
        `;
        
        container.innerHTML = proyectosHTML;
        console.log('✅ Proyectos cargados correctamente');
    }, 1000);
}

/**
 * Cargar donantes (simulado - en producción haría fetch a API)
 */
function loadDonantes() {
    console.log('👥 Cargando donantes...');
    
    const container = document.getElementById('listaDonantes');
    if (!container) return;
    
    // Mostrar loading
    container.innerHTML = `
        <div class="loading show">
            <div class="spinner"></div>
            <p>Cargando donantes...</p>
        </div>
    `;
    
    // Simular carga de datos
    setTimeout(() => {
        const donantesHTML = `
            <div class="data-display">
                <h4>👥 Donantes Registrados</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Juan Pérez García</td>
                            <td>juan.perez@email.com</td>
                            <td>+56912345678</td>
                            <td>14/07/2025</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>María González López</td>
                            <td>maria.gonzalez@email.com</td>
                            <td>+56987654321</td>
                            <td>14/07/2025</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Carlos Rodríguez Silva</td>
                            <td>carlos.rodriguez@email.com</td>
                            <td>+56911223344</td>
                            <td>14/07/2025</td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                    💡 <strong>Tip:</strong> Los donantes mostrados aquí son datos reales de la base de datos.
                    <a href="procesar_donante.php" style="color: #667eea;">Ver lista completa</a>
                </p>
            </div>
        `;
        
        container.innerHTML = donantesHTML;
        console.log('✅ Donantes cargados correctamente');
    }, 1000);
}

/**
 * Funciones para consultas (llamadas desde botones)
 */
function consultarTodosProyectos() {
    console.log('📋 Consultando todos los proyectos...');
    showConsultaResult('Todos los Proyectos', 'Redirigiendo a consultas avanzadas...');
    setTimeout(() => {
        window.location.href = 'consultas_avanzadas.php';
    }, 1000);
}

function consultarTodosDonantes() {
    console.log('👥 Consultando todos los donantes...');
    showConsultaResult('Todos los Donantes', 'Redirigiendo a consultas avanzadas...');
    setTimeout(() => {
        window.location.href = 'consultas_avanzadas.php';
    }, 1000);
}

function consultarDonaciones() {
    console.log('💰 Consultando donaciones...');
    showConsultaResult('Todas las Donaciones', 'Redirigiendo a consultas avanzadas...');
    setTimeout(() => {
        window.location.href = 'consultas_avanzadas.php';
    }, 1000);
}

function consultarProyectosPopulares() {
    console.log('🏆 Consultando proyectos populares...');
    showConsultaResult('Proyectos con Más de 2 Donaciones', 'Ejecutando consulta avanzada...');
    setTimeout(() => {
        window.location.href = 'consultas_avanzadas.php#proyectos-populares';
    }, 1000);
}

/**
 * Mostrar resultado de consulta
 */
function showConsultaResult(titulo, mensaje) {
    const container = document.getElementById('resultadosConsulta');
    const contenido = document.getElementById('contenidoConsulta');
    
    if (!container || !contenido) return;
    
    const tituloElement = container.querySelector('h4');
    if (tituloElement) {
        tituloElement.textContent = titulo;
    }
    
    contenido.innerHTML = `
        <div class="loading show">
            <div class="spinner"></div>
            <p>${mensaje}</p>
        </div>
    `;
    
    container.style.display = 'block';
    
    // Scroll hasta los resultados
    container.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Utilidades para debugging
 */
function getFormData(formId) {
    const form = document.getElementById(formId);
    if (!form) return {};
    
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

/**
 * Funciones de validación avanzada
 */
function validateEmailDomain(email) {
    const domain = email.split('@')[1]?.toLowerCase();
    const suspiciousDomains = ['tempmail', '10minutemail', 'throwaway'];
    
    return !suspiciousDomains.some(suspicious => domain?.includes(suspicious));
}

function validateChileanPhone(phone) {
    const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
    return /^(\+?56)?[9][0-9]{8}$/.test(cleanPhone);
}

/**
 * Manejo de errores global
 */
window.addEventListener('error', function(e) {
    console.error('❌ Error JavaScript:', e.error);
    
    // En desarrollo, mostrar error al usuario
    if (window.location.hostname === 'localhost') {
        showFormMessage('error', `Error: ${e.message}`);
    }
});

/**
 * Funciones para interoperabilidad con otros scripts
 */
window.FormulariosManager = {
    validateForm,
    showTab,
    loadProyectos,
    loadDonantes,
    getFormData,
    showFormMessage
};

console.log('📝 FormulariosManager disponible globalmente');