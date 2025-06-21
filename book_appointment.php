<?php
// book_appointment.php - Procesar reservas de citas
require_once 'config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success = false;
$appointment_id = null;

// Validar datos del formulario
$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';
$pet_name = trim($_POST['pet_name'] ?? '');
$service = $_POST['service'] ?? '';
$user_id = $_SESSION['user_id'];

// Validaciones
if (empty($appointment_date) || empty($appointment_time) || empty($pet_name) || empty($service)) {
    $error_message = 'Todos los campos son obligatorios. Por favor, complete la información de la cita.';
} else {
    // Verificar que la fecha sea válida (no en el pasado, y con un margen, ej. no hoy para mañana)
    $current_date_obj = new DateTime();
    $appointment_date_obj = DateTime::createFromFormat('Y-m-d', $appointment_date);

    if (!$appointment_date_obj) {
        $error_message = 'Formato de fecha inválido.';
    } elseif ($appointment_date_obj < $current_date_obj->setTime(0,0,0)) {
        $error_message = 'No se pueden reservar citas en fechas pasadas.';
    } else {
        // Verificar que el horario aún esté disponible y sea válido
        $stmt_check_slot = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM available_slots
            WHERE date = ? AND time = ? AND is_available = 1
        ");
        $stmt_check_slot->bind_param("ss", $appointment_date, $appointment_time);
        $stmt_check_slot->execute();
        $slot_exists = $stmt_check_slot->get_result()->fetch_assoc()['count'] > 0;
        $stmt_check_slot->close();

        if (!$slot_exists) {
            $error_message = 'El horario seleccionado no es válido o ya no está disponible.';
        } else {
            // Verificar si ya existe una cita para ese horario exacto
            $stmt_check_appointment = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM appointments
                WHERE appointment_date = ? AND appointment_time = ?
            ");
            $stmt_check_appointment->bind_param("ss", $appointment_date, $appointment_time);
            $stmt_check_appointment->execute();
            $appointment_taken = $stmt_check_appointment->get_result()->fetch_assoc()['count'] > 0;
            $stmt_check_appointment->close();

            if ($appointment_taken) {
                $error_message = 'Este horario acaba de ser reservado por otra persona. Por favor, elija otro.';
            } else {
                // Insertar la cita
                $stmt_insert = $conn->prepare("
                    INSERT INTO appointments (user_id, pet_name, service, appointment_date, appointment_time, status) 
                    VALUES (?, ?, ?, ?, ?, 'Confirmada')
                "); // Cambiado 'confirmed' a 'Confirmada'
                $stmt_insert->bind_param("issss", $user_id, $pet_name, $service, $appointment_date, $appointment_time);
                
                if ($stmt_insert->execute()) {
                    $appointment_id = $conn->insert_id;
                    $success = true;
                    $_SESSION['success_message'] = "¡Cita reservada exitosamente!"; // Mensaje de éxito para dashboard
                } else {
                    $error_message = 'Error al procesar la reserva. Por favor, inténtelo de nuevo más tarde.';
                }
                $stmt_insert->close();
            }
        }
    }
}

// Si hubo error, regresar al dashboard con mensaje
if (!$success) {
    $_SESSION['error_message'] = $error_message;
    header('Location: dashboard.php');
    exit;
}

// Si fue exitoso, redirigir a la página de confirmación (este mismo script mostrará el HTML)
// Solo asegurar que $appointment_id está seteado.

// Obtener datos para el recibo (solo si $success es true y $appointment_id está disponible)
$appointment = null; // Inicializar $appointment
if ($success && $appointment_id) {
    $stmt_get_appointment = $conn->prepare("
        SELECT a.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
        DATE_FORMAT(a.appointment_date, '%d/%m/%Y') as formatted_date,
        TIME_FORMAT(a.appointment_time, '%H:%i') as formatted_time,
        DATE_FORMAT(a.created_at, '%d/%m/%Y %H:%i') as formatted_created
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ? AND a.user_id = ?
    "); // Asegurar que la cita pertenece al usuario en sesión
    $stmt_get_appointment->bind_param("ii", $appointment_id, $user_id);
    $stmt_get_appointment->execute();
    $appointment_result = $stmt_get_appointment->get_result();
    if ($appointment_result->num_rows > 0) {
        $appointment = $appointment_result->fetch_assoc();
    } else {
        // Si no se encuentra la cita para el usuario, es un error o intento de acceso indebido.
        $_SESSION['error_message'] = "Error: No se pudo encontrar la cita confirmada.";
        header('Location: dashboard.php');
        exit;
    }
    $stmt_get_appointment->close();
} elseif (!$success) {
    // Si success es false, ya se habrá redirigido con un mensaje de error.
    // Esta parte es por si acaso algo falla en la lógica de redirección.
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cita Confirmada - VetCitas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: #f5f7fa; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    
    <div class="receipt">
        <h2>✅ ¡Cita Confirmada!</h2>
        
        <div class="receipt-details">
            <div class="detail-row">
                <span>Número de cita:</span>
                <span><strong>#<?php echo str_pad($appointment['id'], 4, '0', STR_PAD_LEFT); ?></strong></span>
            </div>
            
            <div class="detail-row">
                <span>Cliente:</span>
                <span><?php echo htmlspecialchars($appointment['user_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Email:</span>
                <span><?php echo htmlspecialchars($appointment['user_email']); ?></span>
            </div>
            
            <?php if ($appointment['user_phone']): ?>
            <div class="detail-row">
                <span>Teléfono:</span>
                <span><?php echo htmlspecialchars($appointment['user_phone']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span>Mascota:</span>
                <span><?php echo htmlspecialchars($appointment['pet_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Servicio:</span>
                <span><?php echo htmlspecialchars($appointment['service']); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Fecha:</span>
                <span><?php echo $appointment['formatted_date']; ?></span>
            </div>
            
            <div class="detail-row">
                <span>Hora:</span>
                <span><?php echo $appointment['formatted_time']; ?></span>
            </div>
            
            <div class="detail-row">
                <span>Estado:</span>
                <span style="color: #28a745; font-weight: bold;"><?php echo strtoupper(htmlspecialchars($appointment['status'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Reservado el:</span>
                <span><?php echo $appointment['formatted_created']; ?></span>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <p style="color: #666; margin-bottom: 20px;">
                <strong>¡Importante!</strong><br>
                Llegue 10 minutos antes de su cita.<br>
                Traiga cartilla de vacunación de su mascota.
            </p>
            
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="window.print()" class="btn" style="width: auto; padding: 10px 20px;">
                    🖨️ Imprimir Recibo
                </button>
                
                <a href="print_receipt.php?id=<?php echo $appointment['id']; ?>" target="_blank" class="btn btn-secondary" style="width: auto; padding: 10px 20px; text-decoration: none; display: inline-block;">
                    📄 Ver Recibo PDF
                </a>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="dashboard.php" class="btn btn-secondary" style="width: auto; padding: 10px 20px; text-decoration: none; display: inline-block;">
                    ← Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body {
                background: white !important;
            }
            
            .receipt {
                box-shadow: none !important;
                border: 2px solid #ddd;
            }
            
            button, a {
                display: none !important;
            }
        }
    </style>

</body>
</html>