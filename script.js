let proyectos = [
    {
        id: 1,
        nombre: "Educación para Todos",
        descripcion: "Programa de becas y materiales educativos para niños en situación vulnerable.",
        objetivo: "$50,000",
        recaudado: "$32,000"
    },
    {
        id: 2,
        nombre: "Alimentación Comunitaria",
        descripcion: "Comedores comunitarios en barrios de bajos recursos.",
        objetivo: "$30,000",
        recaudado: "$18,500"
    }
];

let eventos = [
    {
        nombre: "Caminata Solidaria",
        fecha: "15 de Julio, 2025",
        descripcion: "Caminata familiar para recaudar fondos",
        lugar: "Parque Central"
    },
    {
        nombre: "Cena Benéfica",
        fecha: "22 de Julio, 2025",
        descripcion: "Cena de gala con artistas locales",
        lugar: "Hotel Plaza"
    }
];

let donaciones = [];
let montoSeleccionado = 0;

const sistemaNotificaciones = {
    contadorActivas: 0,
    maxNotificaciones: 3,

    // Metodo principal para mostrar notificaciones usando templates existentes
    mostrar(tipo, mensaje) {
        if (this.contadorActivas >= this.maxNotificaciones) {
            return; // No mostrar más de 3 notificaciones a la vez
        }

        // Obtener template del HTML existente y clonarlo
        const template = document.getElementById(`notification-template-${tipo}`);
        if (!template) return;

        const notificacion = template.cloneNode(true);
        notificacion.id = `notification-${Date.now()}`; // ID único

        // Actualizar contenido usando querySelector
        notificacion.querySelector('.notification-message').textContent = mensaje;
        notificacion.querySelector('.notification-time').textContent = new Date().toLocaleString('es-ES');

        // Mostrar notificación
        notificacion.classList.add('show');

        // Insertar al inicio del contenedor usando insertBefore
        const contenedor = document.getElementById('notifications-container');
        if (contenedor) {
            const primerTemplate = contenedor.querySelector('[id*="template"]');
            contenedor.insertBefore(notificacion, primerTemplate);
        }

        this.contadorActivas++;

        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            this.cerrar(notificacion);
        }, 5000);

        // Agregar evento click para marcar como vista
        notificacion.addEventListener('click', () => {
            notificacion.style.backgroundColor = '#f8f9fa';
        });
    },

    cerrar(elemento) {
        if (!elemento || !elemento.parentNode) return;
        
        elemento.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => {
            if (elemento.parentNode) {
                elemento.parentNode.removeChild(elemento);
                this.contadorActivas--;
            }
        }, 300);
    }
};

const monitorProgreso = {
    verificarHitos(proyectoId, montoAnterior, montoNuevo) {
        const proyecto = proyectos.find(p => p.id === proyectoId);
        if (!proyecto) return;

        const meta = parseFloat(proyecto.objetivo.replace('$', '').replace(',', ''));
        const porcentajeAnterior = (montoAnterior / meta) * 100;
        const porcentajeNuevo = (montoNuevo / meta) * 100;

        // Verificar hitos importantes
        [25, 50, 75, 100].forEach(hito => {
            if (porcentajeAnterior < hito && porcentajeNuevo >= hito) {
                const tipo = hito === 100 ? 'logro' : 'progreso';
                const mensaje = hito === 100
                    ? `¡Meta alcanzada! El proyecto "${proyecto.nombre}" ha completado el 100% de su objetivo.`
                    : `El proyecto "${proyecto.nombre}" ha alcanzado el ${hito}% de su meta.`;

                sistemaNotificaciones.mostrar(tipo, mensaje);
            }
        });
    }
};

function cerrarNotificacion(elemento) {
    sistemaNotificaciones.cerrar(elemento);
}

window.onload = function() {
    // Verificar si estamos en la página con el sistema de donaciones PHP
    const esPaginaPHP = document.getElementById('donations-tab');
    
    if (esPaginaPHP) {
        // Nueva implementación PHP - inicializar solo elementos disponibles
        inicializarSistemaPHP();
    } else {
        // Implementación JavaScript original
        inicializarSistemaJS();
    }
    
    // Funciones comunes que siempre se ejecutan
    inicializarNotificaciones();
};

function inicializarSistemaPHP() {
    // Cambiar a la pestaña de proyectos por defecto
    const proyectosTab = document.querySelector('.option-container');
    if (proyectosTab) {
        cambiarPestaña('projects', proyectosTab);
    }
    
    // Cargar proyectos solo si el contenedor existe
    const projectsContainer = document.getElementById('projects-container');
    if (projectsContainer) {
        cargarProyectos();
    }
    
    // Cargar eventos solo si el contenedor existe
    const eventsContainer = document.getElementById('events-container');
    if (eventsContainer) {
        cargarEventos();
    }
    
    // Inicializar contador de carrito si existe
    actualizarContadorCarrito();
    
    console.log('Sistema PHP inicializado correctamente');
}

function inicializarSistemaJS() {
    // Sistema JavaScript original
    const proyectosTab = document.querySelector('.option-container');
    if (proyectosTab) {
        cambiarPestaña('projects', proyectosTab);
    }
    
    cargarProyectos();
    cargarEventos();
    cargarOpcionesProyectos();
    
    console.log('Sistema JavaScript inicializado correctamente');
}

function inicializarNotificaciones() {
    // Notificaciones de ejemplo después de cargar (solo si hay contenedor)
    const notificacionesContainer = document.getElementById('notifications-container');
    if (!notificacionesContainer) return;
    
    setTimeout(() => {
        sistemaNotificaciones.mostrar('campaña', 'Nueva campaña "Navidad Solidaria 2025" ha comenzado. Recolección de juguetes para niños.');

        setTimeout(() => {
            sistemaNotificaciones.mostrar('progreso', "El proyecto 'Educación para Todos' ha recibido 3 donaciones en la última hora.");
        }, 2000);
    }, 2000);
}

function cambiarPestaña(pestaña, elemento) {
    const pestañas = document.querySelectorAll('.tab-content');
    pestañas.forEach(tab => {
        tab.style.display = 'none';
    });

    document.querySelectorAll('.option-container').forEach(container => {
        container.classList.remove('active');
    });

    if (elemento) {
        elemento.classList.add('active');
    }

    switch(pestaña) {
        case 'projects':
            mostrarPestaña('projects-tabs');
            break;
        case 'donations':
            mostrarPestaña('donations-tab');
            actualizarListaDonaciones();
            break;
        case 'carrito':
            mostrarPestaña('carrito-tab');
            break;
        case 'events':
            mostrarPestaña('events-tab');
            break;
        case 'admin':
            mostrarPestaña('admin-tab');
            break;
    }
}

function mostrarPestaña(pestañaId) {
    const pestaña = document.getElementById(pestañaId);
    if (pestaña) {
        pestaña.style.display = 'block';
    }
}

function cargarProyectos() {
    const container = document.getElementById('projects-container');
    if (!container) return;
    
    container.innerHTML = '';

    proyectos.forEach(proyecto => {
        const porcentaje = (parseFloat(proyecto.recaudado.replace('$', '').replace(',', '')) /
            parseFloat(proyecto.objetivo.replace('$', '').replace(',', ''))) * 100;

        const proyectoDiv = document.createElement('div');
        proyectoDiv.className = 'project-card';
        proyectoDiv.innerHTML = `
            <h3>${proyecto.nombre}</h3>
            <p>${proyecto.descripcion}</p>
            <div class="progress-bar">
                <div class="progress" style="width: ${porcentaje}%"></div>
            </div>
            <p><strong>Objetivo:</strong> ${proyecto.objetivo}</p>
            <p><strong>Recaudado:</strong> ${proyecto.recaudado} (${Math.round(porcentaje)}%)</p>
        `;
        container.appendChild(proyectoDiv);
    });
}

function cargarEventos() {
    const container = document.getElementById('events-container');
    if (!container) return;
    
    container.innerHTML = '';

    eventos.forEach(evento => {
        const eventoDiv = document.createElement('div');
        eventoDiv.className = 'event-card';
        eventoDiv.innerHTML = `
            <h3>${evento.nombre}</h3>
            <p><strong>Fecha:</strong> ${evento.fecha}</p>
            <p><strong>Lugar:</strong> ${evento.lugar}</p>
            <p>${evento.descripcion}</p>
        `;
        container.appendChild(eventoDiv);
    });
}

function cargarOpcionesProyectos() {
    // Esta función es para la implementación JavaScript original
    const select = document.getElementById('donation-project');
    if (!select) {
        console.log('Elemento donation-project no encontrado - usando implementación PHP');
        return;
    }
    
    select.innerHTML = '<option value="">Selecciona un proyecto</option>';

    proyectos.forEach(proyecto => {
        const option = document.createElement('option');
        option.value = proyecto.id;
        option.textContent = proyecto.nombre;
        select.appendChild(option);
    });
}

function seleccionarMonto(monto, elemento) {
    montoSeleccionado = monto;

    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (elemento) {
        elemento.classList.add('selected');
    }
    
    const customAmount = document.getElementById('custom-amount');
    if (customAmount) {
        customAmount.value = '';
    }
}

function limpiarSeleccionMonto() {
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    montoSeleccionado = 0;
}

function procesarDonacion() {
    // Esta función es para la implementación JavaScript original
    const montoPersonalizado = document.getElementById('custom-amount')?.value;
    const proyectoSeleccionado = document.getElementById('donation-project')?.value;
    const nombreDonante = document.getElementById('donor-name')?.value;
    const emailDonante = document.getElementById('donor-email')?.value;

    if (!nombreDonante || !emailDonante) {
        alert('Por favor completa tu nombre y email.');
        return;
    }

    const montoFinal = montoPersonalizado ? parseFloat(montoPersonalizado) : montoSeleccionado;

    if (!montoFinal || montoFinal <= 0) {
        alert('Por favor selecciona un monto válido.');
        return;
    }

    // Crear donación
    const donacion = {
        id: donaciones.length + 1,
        monto: montoFinal,
        proyecto: proyectoSeleccionado ? proyectos.find(p => p.id == proyectoSeleccionado).nombre : 'Donación general',
        donante: nombreDonante,
        email: emailDonante,
        fecha: new Date().toLocaleDateString('es-ES')
    };

    donaciones.push(donacion);

    sistemaNotificaciones.mostrar('donacion',
        `${nombreDonante} ha realizado una donación de $${montoFinal}${proyectoSeleccionado ? ` al proyecto "${donacion.proyecto}"` : ''}`
    );

    // Actualizar progreso si hay proyecto seleccionado
    if (proyectoSeleccionado) {
        const proyecto = proyectos.find(p => p.id == proyectoSeleccionado);
        const recaudadoAnterior = parseFloat(proyecto.recaudado.replace('$', '').replace(',', ''));
        const nuevoRecaudado = recaudadoAnterior + montoFinal;

        proyecto.recaudado = `$${nuevoRecaudado.toLocaleString()}`;

        monitorProgreso.verificarHitos(proyecto.id, recaudadoAnterior, nuevoRecaudado);

        cargarProyectos();
    }

    // Limpiar formulario
    limpiarFormulario();

    alert(`¡Gracias ${nombreDonante}! Tu donación de $${montoFinal} ha sido procesada exitosamente.`);
    actualizarListaDonaciones();
}

function limpiarFormulario() {
    const customAmount = document.getElementById('custom-amount');
    const donationProject = document.getElementById('donation-project');
    const donorName = document.getElementById('donor-name');
    const donorEmail = document.getElementById('donor-email');
    
    if (customAmount) customAmount.value = '';
    if (donationProject) donationProject.value = '';
    if (donorName) donorName.value = '';
    if (donorEmail) donorEmail.value = '';
    
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    montoSeleccionado = 0;
}

function actualizarListaDonaciones() {
    const container = document.getElementById('donations-list');
    if (!container) return;

    if (donaciones.length === 0) {
        container.innerHTML = '<h3>Aún no hay donaciones registradas</h3>';
        return;
    }

    container.innerHTML = '<h3>Donaciones Recientes</h3>';

    donaciones.slice(-5).reverse().forEach(donacion => {
        const donacionDiv = document.createElement('div');
        donacionDiv.className = 'donation-item';
        donacionDiv.innerHTML = `
            <p><strong>${donacion.donante}</strong> donó <strong>$${donacion.monto}</strong></p>
            <p>Proyecto: ${donacion.proyecto}</p>
            <p>Fecha: ${donacion.fecha}</p>
        `;
        container.appendChild(donacionDiv);
    });
}

function actualizarContadorCarrito() {
    // Función para actualizar contador de carrito en implementación PHP
    const contadorCarrito = document.querySelector('.carrito-count');
    const totalCarrito = document.querySelector('.carrito-total');
    
    // Esta función puede expandirse para hacer llamadas AJAX
    // para obtener datos actualizados del carrito PHP
    console.log('Contador de carrito actualizado');
}

function search() {
    const searchInput = document.getElementById('events');
    if (!searchInput) {
        console.log('Campo de búsqueda no encontrado');
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase();

    if (!searchTerm) {
        alert('Por favor ingresa un término de búsqueda');
        return;
    }

    const eventosEncontrados = eventos.filter(evento =>
        evento.nombre.toLowerCase().includes(searchTerm) ||
        evento.descripcion.toLowerCase().includes(searchTerm) ||
        evento.lugar.toLowerCase().includes(searchTerm)
    );

    const eventosTab = document.querySelectorAll('.option-container')[2];
    if (eventosTab) {
        cambiarPestaña('events', eventosTab);
    }

    const container = document.getElementById('events-container');
    if (!container) return;
    
    container.innerHTML = '';

    if (eventosEncontrados.length === 0) {
        container.innerHTML = `<p>No se encontraron eventos que coincidan con "${searchTerm}"</p>`;
    } else {
        container.innerHTML = `<h3>Resultados de búsqueda para "${searchTerm}":</h3>`;
        eventosEncontrados.forEach(evento => {
            const eventoDiv = document.createElement('div');
            eventoDiv.className = 'event-card';
            eventoDiv.innerHTML = `
                <h3>${evento.nombre}</h3>
                <p><strong>Fecha:</strong> ${evento.fecha}</p>
                <p><strong>Lugar:</strong> ${evento.lugar}</p>
                <p>${evento.descripcion}</p>
            `;
            container.appendChild(eventoDiv);
        });
    }
}

// Funciones adicionales para la implementación PHP
function confirmarEliminarDonacion(donacionId) {
    return confirm('¿Está seguro de que desea eliminar esta donación del carrito?');
}

function confirmarFinalizarCompra() {
    return confirm('¿Confirmar el procesamiento de todas las donaciones en el carrito?');
}

// Función para manejar errores JavaScript
window.addEventListener('error', function(e) {
    console.error('Error JavaScript detectado:', e.error);
    console.log('Elemento que causó el error:', e.target);
    
    // No mostrar errores al usuario en producción
    // alert('Se detectó un error. La funcionalidad puede estar limitada.');
});

// Inicialización adicional cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM completamente cargado');
    
    // Verificar si hay elementos de notificación y inicializarlos
    const notificationContainer = document.getElementById('notifications-container');
    if (notificationContainer) {
        console.log('Sistema de notificaciones disponible');
    }
    
    // Verificar si hay carrito PHP y actualizar contador
    const carritoIndicador = document.getElementById('carrito-indicador');
    if (carritoIndicador) {
        console.log('Carrito PHP detectado');
        actualizarContadorCarrito();
    }
});