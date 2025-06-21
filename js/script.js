// script.js - Funcionalidades JavaScript para VetCitas

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Función principal de inicialización
function initializeApp() {
    // Inicializar validaciones de formularios
    initFormValidations();
    
    // Inicializar funcionalidades del calendario
    initCalendarFeatures();
    
    // Inicializar tooltips y ayudas
    initTooltips();
    
    // Auto-ocultar mensajes de alerta después de 5 segundos
    autoHideAlerts();
    
    // Inicializar funciones de impresión
    initPrintFeatures();
    
    // Inicializar selección de horarios
    initTimeSlotSelection();
    
    // Inicializar confirmaciones
    initConfirmations();
}

// Validaciones de formularios
function initFormValidations() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Validación en tiempo real
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

// Validar formulario completo
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    // Validaciones especiales para formulario de reserva
    if (form.id === 'booking-form') {
        const selectedSlot = document.querySelector('.time-slot.selected');
        if (!selectedSlot) {
            showAlert('Por favor selecciona un horario', 'error');
            isValid = false;
        }
    }
    
    return isValid;
}

// Validar campo individual
function validateField(field) {
    const value = field.value.trim();
    const fieldType = field.type;
    const fieldName = field.name;
    
    // Limpiar errores previos
    clearFieldError(field);
    
    // Validar campo requerido
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'Este campo es obligatorio');
        return false;
    }
    
    // Validaciones específicas por tipo
    switch (fieldType) {
        case 'email':
            if (value && !isValidEmail(value)) {
                showFieldError(field, 'Ingrese un email válido');
                return false;
            }
            break;
            
        case 'password':
            if (value && value.length < 6) {
                showFieldError(field, 'La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            // Validar confirmación de contraseña
            if (fieldName === 'confirm_password') {
                const passwordField = document.querySelector('input[name="password"]');
                if (passwordField && value !== passwordField.value) {
                    showFieldError(field, 'Las contraseñas no coinciden');
                    return false;
                }
            }
            break;
            
        case 'tel':
            if (value && !isValidPhone(value)) {
                showFieldError(field, 'Ingrese un teléfono válido');
                return false;
            }
            break;
    }
    
    // Validaciones específicas por nombre
    switch (fieldName) {
        case 'pet_name':
            if (value && value.length < 2) {
                showFieldError(field, 'El nombre de la mascota debe tener al menos 2 caracteres');
                return false;
            }
            break;
            
        case 'name':
            if (value && value.length < 2) {
                showFieldError(field, 'El nombre debe tener al menos 2 caracteres');
                return false;
            }
            break;
    }
    
    return true;
}

// Mostrar error en campo
function showFieldError(field, message) {
    field.classList.add('error');
    
    // Crear o actualizar mensaje de error
    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    errorElement.style.color = '#dc3545';
    errorElement.style.fontSize = '12px';
    errorElement.style.marginTop = '5px';
}

// Limpiar error en campo
function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// Validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validar teléfono
function isValidPhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
    return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

// Funcionalidades del calendario
function initCalendarFeatures() {
    // Destacar fecha actual
    highlightCurrentDate();
    
    // Mostrar contador de citas disponibles
    updateAvailableSlotsCounts();
    
    // Agregar animaciones a las tarjetas de día
    addCardAnimations();
    
    // Inicializar navegación del calendario
    initCalendarNavigation();
}

// Destacar fecha actual
function highlightCurrentDate() {
    const today = new Date();
    const todayString = today.toISOString().split('T')[0];
    
    const dayCards = document.querySelectorAll('.day-card');
    dayCards.forEach(card => {
        const dateElement = card.querySelector('h3');
        if (dateElement && dateElement.dataset.date === todayString) {
            card.classList.add('today');
            card.style.border = '2px solid #667eea';
            card.style.background = '#f0f4ff';
        }
    });
}

// Actualizar contador de horarios disponibles
function updateAvailableSlotsCounts() {
    const dayCards = document.querySelectorAll('.day-card');
    
    dayCards.forEach(card => {
        const availableSlots = card.querySelectorAll('.time-slot:not(.unavailable)');
        const totalSlots = card.querySelectorAll('.time-slot');
        
        // Crear badge con contador
        if (totalSlots.length > 0) {
            const badge = document.createElement('div');
            badge.className = 'availability-badge';
            badge.innerHTML = `${availableSlots.length}/${totalSlots.length} disponibles`;
            badge.style.cssText = `
                background: ${availableSlots.length > 0 ? '#28a745' : '#dc3545'};
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                text-align: center;
                margin-top: 5px;
            `;
            
            card.appendChild(badge);
        }
    });
}

// Agregar animaciones a las tarjetas
function addCardAnimations() {
    const dayCards = document.querySelectorAll('.day-card');
    
    dayCards.forEach((card, index) => {
        // Animación de entrada escalonada
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
        
        // Hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
    });
}

// Inicializar navegación del calendario
function initCalendarNavigation() {
    const prevBtn = document.getElementById('prev-week');
    const nextBtn = document.getElementById('next-week');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            navigateCalendar(-7);
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            navigateCalendar(7);
        });
    }
}

// Navegar calendario por días
function navigateCalendar(days) {
    const currentDate = new Date();
    currentDate.setDate(currentDate.getDate() + days);
    
    // Aquí podrías hacer una petición AJAX para cargar nuevas fechas
    showAlert(`Navegando ${days > 0 ? 'adelante' : 'atrás'} ${Math.abs(days)} días`, 'info');
}

// Funcionalidades de tooltips
function initTooltips() {
    // Agregar tooltips a elementos con atributo title
    const elementsWithTitle = document.querySelectorAll('[title]');
    
    elementsWithTitle.forEach(element => {
        const title = element.getAttribute('title');
        element.removeAttribute('title'); // Remover título nativo
        
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = title;
        tooltip.style.cssText = `
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        `;
        
        document.body.appendChild(tooltip);
        
        element.addEventListener('mouseenter', function(e) {
            tooltip.style.left = e.pageX + 10 + 'px';
            tooltip.style.top = e.pageY - 30 + 'px';
            tooltip.style.opacity = '1';
        });
        
        element.addEventListener('mouseleave', function() {
            tooltip.style.opacity = '0';
        });
        
        element.addEventListener('mousemove', function(e) {
            tooltip.style.left = e.pageX + 10 + 'px';
            tooltip.style.top = e.pageY - 30 + 'px';
        });
    });
}

// Auto-ocultar alertas
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
}

// Inicializar funciones de impresión
function initPrintFeatures() {
    const printButtons = document.querySelectorAll('.print-btn, [data-print]');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            printReceipt();
        });
    });
}

// Función para imprimir recibo
function printReceipt() {
    // Crear ventana de impresión
    const printWindow = window.open('', '_blank');
    const receiptContent = document.querySelector('.receipt-content');
    
    if (receiptContent) {
        printWindow.document.write(`
            <html>
                <head>
                    <title>Comprobante de Cita - VetCitas</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .receipt-header { text-align: center; margin-bottom: 30px; }
                        .receipt-details { margin-bottom: 20px; }
                        .receipt-details p { margin: 5px 0; }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${receiptContent.innerHTML}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
    } else {
        window.print();
    }
}

// Inicializar selección de horarios
function initTimeSlotSelection() {
    const timeSlots = document.querySelectorAll('.time-slot');
    
    timeSlots.forEach(slot => {
        slot.addEventListener('click', function() {
            if (this.classList.contains('unavailable')) {
                showAlert('Este horario no está disponible', 'warning');
                return;
            }
            
            // Remover selección anterior
            timeSlots.forEach(s => s.classList.remove('selected'));
            
            // Seleccionar slot actual
            this.classList.add('selected');
            
            // Actualizar campos ocultos
            updateSelectedSlot(this);
            
            // Habilitar botón de reserva
            enableBookingButton();
        });
    });
}

// Actualizar slot seleccionado
function updateSelectedSlot(slot) {
    const dateInput = document.getElementById('selected_date');
    const timeInput = document.getElementById('selected_time');
    
    if (dateInput) dateInput.value = slot.dataset.date;
    if (timeInput) timeInput.value = slot.dataset.time;
    
    // Mostrar información seleccionada
    showSelectedSlotInfo(slot);
}

// Mostrar información del slot seleccionado
function showSelectedSlotInfo(slot) {
    const infoContainer = document.getElementById('selected-slot-info');
    
    if (infoContainer) {
        const date = new Date(slot.dataset.date);
        const formattedDate = date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        infoContainer.innerHTML = `
            <div class="selected-info">
                <h4>Horario Seleccionado:</h4>
                <p><strong>Fecha:</strong> ${formattedDate}</p>
                <p><strong>Hora:</strong> ${slot.dataset.time}</p>
            </div>
        `;
        infoContainer.style.display = 'block';
    }
}

// Habilitar botón de reserva
function enableBookingButton() {
    const bookBtn = document.getElementById('book-btn');
    
    if (bookBtn) {
        bookBtn.disabled = false;
        bookBtn.textContent = 'Confirmar Reserva';
        bookBtn.classList.add('active');
    }
}

// Inicializar confirmaciones
function initConfirmations() {
    // Confirmación de logout
    const logoutLinks = document.querySelectorAll('a[href="logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                e.preventDefault();
            }
        });
    });
    
    // Confirmación de cancelación de citas
    const cancelButtons = document.querySelectorAll('.cancel-appointment');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const appointmentId = this.dataset.appointmentId;
            
            if (confirm('¿Estás seguro de que quieres cancelar esta cita?')) {
                cancelAppointment(appointmentId);
            }
        });
    });
}

// Cancelar cita
function cancelAppointment(appointmentId) {
    // Aquí harías una petición AJAX para cancelar
    fetch('cancel_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'appointment_id=' + appointmentId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cita cancelada exitosamente', 'success');
            location.reload();
        } else {
            showAlert('Error al cancelar la cita', 'error');
        }
    })
    .catch(error => {
        showAlert('Error de conexión', 'error');
    });
}

// Función para mostrar alertas personalizadas
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 1000;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    `;
    
    // Colores según tipo
    const colors = {
        success: { bg: '#d4edda', color: '#155724', border: '#c3e6cb' },
        error: { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb' },
        warning: { bg: '#fff3cd', color: '#856404', border: '#ffeaa7' },
        info: { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb' }
    };
    
    const colorScheme = colors[type] || colors.info;
    alert.style.backgroundColor = colorScheme.bg;
    alert.style.color = colorScheme.color;
    alert.style.border = `1px solid ${colorScheme.border}`;
    
    document.body.appendChild(alert);
    
    // Auto-hide después de 5 segundos
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, 5000);
}

// Funciones utilitarias
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Función para cargar más citas (si se implementa paginación)
function loadMoreAppointments() {
    showAlert('Cargando más citas...', 'info');
    // Aquí harías la petición AJAX
}

// Función para filtrar citas por estado
function filterAppointments(status) {
    const appointments = document.querySelectorAll('.appointment-card');
    
    appointments.forEach(appointment => {
        const appointmentStatus = appointment.dataset.status;
        
        if (status === 'all' || appointmentStatus === status) {
            appointment.style.display = 'block';
        } else {
            appointment.style.display = 'none';
        }
    });
}

// Event listeners adicionales
document.addEventListener('click', function(e) {
    // Cerrar dropdowns al hacer click fuera
    if (!e.target.matches('.dropdown-toggle')) {
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});

// Función para actualizar el estado de la página
function updatePageStatus() {
    const statusIndicator = document.getElementById('page-status');
    if (statusIndicator) {
        statusIndicator.textContent = 'Página actualizada: ' + new Date().toLocaleTimeString('es-ES');
    }
}