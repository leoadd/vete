<?php
// dashboard.php - Panel principal para reservar citas
require_once 'config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Obtener citas del usuario
$user_appointments = [];
$stmt = $conn->prepare("SELECT a.*, DATE_FORMAT(a.appointment_date, '%d/%m/%Y') as formatted_date, TIME_FORMAT(a.appointment_time, '%H:%i') as formatted_time FROM appointments a WHERE a.user_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_appointments[] = $row;
}

// Obtener horarios disponibles para los próximos 7 días
$available_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $formatted_date = date('d/m/Y', strtotime($date));
    $day_name = date('l', strtotime($date));
    
    // Traducir días al español
    $days_spanish = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes', 
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $day_name_spanish = $days_spanish[$day_name];
    
    // Obtener horarios disponibles para esta fecha
    $stmt = $conn->prepare("
        SELECT s.time, TIME_FORMAT(s.time, '%H:%i') as formatted_time, s.is_available,
        CASE WHEN a.id IS NOT NULL THEN 0 ELSE s.is_available END as available
        FROM available_slots s 
        LEFT JOIN appointments a ON s.date = a.appointment_date AND s.time = a.appointment_time
        WHERE s.date = ? 
        ORDER BY s.time
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $times_result = $stmt->get_result();
    
    $times = [];
    while ($time_row = $times_result->fetch_assoc()) {
        $times[] = $time_row;
    }
    
    $available_dates[] = [
        'date' => $date,
        'formatted_date' => $formatted_date,
        'day_name' => $day_name_spanish,
        'times' => $times
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Citas - VetCitas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body"> <!-- Added class for dashboard specific body styling -->
    
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h2>🐾 VetCitas</h2>
                <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
            </div>
            <div>
                <a href="settings.php" class="btn btn-secondary" style="width: auto; padding: 10px 20px; margin-right: 10px;">Ajustes</a>
                <a href="logout.php" class="btn btn-secondary" style="width: auto; padding: 10px 20px;">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success" style="margin-bottom: 20px;"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error" style="margin-bottom: 20px;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        
        <!-- Mis Citas -->
        <?php if (!empty($user_appointments)): ?>
        <div class="booking-form">
            <h3>📅 Mis Citas Programadas</h3>
            <div style="margin-top: 20px;">
                <?php foreach ($user_appointments as $appointment): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #667eea;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo $appointment['formatted_date']; ?> a las <?php echo $appointment['formatted_time']; ?></strong><br>
                            <small>Mascota: <?php echo htmlspecialchars($appointment['pet_name']); ?> | Servicio: <?php echo htmlspecialchars($appointment['service']); ?></small>
                        </div>
                        <div>
                            <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; margin-right: 10px;">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($appointment)); ?>)">Editar</button>
                            <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $appointment['id']; ?>)">Eliminar</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal para Editar Cita -->
        <div id="edit-appointment-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-button" onclick="closeEditModal()">&times;</span>
                <h3>✏️ Editar Cita</h3>
                <form id="edit-appointment-form" method="POST" action="manage_appointment.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="edit-appointment-id" name="appointment_id">

                    <div class="form-group">
                        <label for="edit-pet-name">Nombre de la mascota</label>
                        <input type="text" id="edit-pet-name" name="pet_name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit-service">Tipo de servicio</label>
                        <select id="edit-service" name="service" required>
                            <option value="Consulta general">Consulta general</option>
                            <option value="Vacunación">Vacunación</option>
                            <option value="Desparasitación">Desparasitación</option>
                            <option value="Cirugía menor">Cirugía menor</option>
                            <option value="Emergencia">Emergencia</option>
                            <option value="Control de peso">Control de peso</option>
                            <option value="Limpieza dental">Limpieza dental</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit-appointment-date">Fecha de la cita</label>
                        <input type="date" id="edit-appointment-date" name="appointment_date" required>
                    </div>

                    <div class="form-group">
                        <label for="edit-appointment-time">Hora de la cita</label>
                        <input type="time" id="edit-appointment-time" name="appointment_time" required>
                    </div>

                    <button type="submit" class="btn">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Reservar Nueva Cita -->
        <div class="booking-form">
            <h3>🗓️ Reservar Nueva Cita</h3>
            <p>Selecciona una fecha y hora disponible:</p>
            
            <div class="calendar-grid">
                <?php foreach ($available_dates as $date_info): ?>
                <div class="day-card">
                    <h3><?php echo $date_info['day_name']; ?></h3>
                    <p><?php echo $date_info['formatted_date']; ?></p>
                    
                    <div class="time-slots">
                        <?php foreach ($date_info['times'] as $time): ?>
                        <div class="time-slot <?php echo $time['available'] ? '' : 'unavailable'; ?>" 
                             <?php if ($time['available']): ?>
                             onclick="selectTime('<?php echo $date_info['date']; ?>', '<?php echo $time['time']; ?>', '<?php echo $time['formatted_time']; ?>', '<?php echo $date_info['formatted_date']; ?>')"
                             <?php endif; ?>>
                            <?php echo $time['formatted_time']; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Formulario de Reserva -->
        <div id="booking-form" class="booking-form" style="display: none;">
            <h3>📝 Completar Reserva</h3>
            <div id="selected-time-info" style="background: #e3f2fd; padding: 15px; border-radius: 10px; margin-bottom: 20px;"></div>
            
            <form action="book_appointment.php" method="POST">
                <input type="hidden" id="selected-date" name="appointment_date">
                <input type="hidden" id="selected-time" name="appointment_time">
                
                <div class="form-group">
                    <label for="pet-name">Nombre de la mascota</label>
                    <input type="text" id="pet-name" name="pet_name" required>
                </div>

                <div class="form-group">
                    <label for="service">Tipo de servicio</label>
                    <select id="service" name="service" required>
                        <option value="">Seleccionar servicio</option>
                        <option value="Consulta general">Consulta general</option>
                        <option value="Vacunación">Vacunación</option>
                        <option value="Desparasitación">Desparasitación</option>
                        <option value="Cirugía menor">Cirugía menor</option>
                        <option value="Emergencia">Emergencia</option>
                        <option value="Control de peso">Control de peso</option>
                        <option value="Limpieza dental">Limpieza dental</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn" style="flex: 1;">Confirmar Cita</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="cancelSelection()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectTime(date, time, formattedTime, formattedDate) {
            // Deseleccionar otros horarios
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Seleccionar el horario clickeado
            event.target.classList.add('selected');
            
            // Llenar el formulario
            document.getElementById('selected-date').value = date;
            document.getElementById('selected-time').value = time;
            document.getElementById('selected-time-info').innerHTML = 
                `<strong>Fecha seleccionada:</strong> ${formattedDate}<br><strong>Hora:</strong> ${formattedTime}`;
            
            // Mostrar formulario de reserva
            document.getElementById('booking-form').style.display = 'block';
            
            // Scroll suave al formulario
            document.getElementById('booking-form').scrollIntoView({behavior: 'smooth'});
        }

        function cancelSelection() {
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            document.getElementById('booking-form').style.display = 'none';
        }

        function openEditModal(appointment) {
            document.getElementById('edit-appointment-id').value = appointment.id;
            document.getElementById('edit-pet-name').value = appointment.pet_name;
            document.getElementById('edit-service').value = appointment.service;
            // Format date to YYYY-MM-DD for input type="date"
            const dateParts = appointment.formatted_date.split('/');
            document.getElementById('edit-appointment-date').value = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            document.getElementById('edit-appointment-time').value = appointment.appointment_time.substring(0,5); // HH:MM
            document.getElementById('edit-appointment-modal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('edit-appointment-modal').style.display = 'none';
        }

        function confirmDelete(appointmentId) {
            if (confirm("¿Estás seguro de que deseas eliminar esta cita?")) {
                // Redirect to a PHP script to handle deletion
                window.location.href = `manage_appointment.php?action=delete&id=${appointmentId}`;
            }
        }

        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('edit-appointment-modal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>