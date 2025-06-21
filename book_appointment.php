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
    $error_message = 'Todos los campos son obligatorios';
} else {
    // Verificar que la fecha sea válida (no en el pasado)
    if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $error_message = 'No se pueden reservar citas en fechas pasadas';
    } else {
        // Verificar que el horario aún esté disponible
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE appointment_date = ? AND appointment_time = ?
        ");
        $stmt->bind_param("ss", $appointment_date, $appointment_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error_message = 'Este horario ya no está disponible';
        } else {
            // Verificar que el horario existe en available_slots
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM available_slots 
                WHERE date = ? AND time = ? AND is_available = 1
            ");
            $stmt->bind_param("ss", $appointment_date, $appointment_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                $error_message = 'Horario no válido';
            } else {
                // Insertar la cita
                $stmt = $conn->prepare("
                    INSERT INTO appointments (user_id, pet_name, service, appointment_date, appointment_time, status) 
                    VALUES (?, ?, ?, ?, ?, 'confirmed')
                ");
                $stmt->bind_param("issss", $user_id, $pet_name, $service, $appointment_date, $appointment_time);
                
                if ($stmt->execute()) {
                    $appointment_id = $conn->insert_id;
                    $success = true;
                } else {
                    $error_message = 'Error al procesar la reserva. Intente nuevamente.';
                }
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

// Si fue exitoso, obtener datos para el recibo
$stmt = $conn->prepare("
    SELECT a.*, u.name, u.email, u.phone,
    DATE_FORMAT(a.appointment_date, '%d/%m/%Y') as formatted_date,
    TIME_FORMAT(a.appointment_time, '%H:%i') as formatted_time,
    DATE_FORMAT(a.created_at, '%d/%m/%Y %H:%i') as formatted_created
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
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
                <span><?php echo htmlspecialchars($appointment['name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Email:</span>
                <span><?php echo htmlspecialchars($appointment['email']); ?></span>
            </div>
            
            <?php if ($appointment['phone']): ?>
            <div class="detail-row">
                <span>Teléfono:</span>
                <span><?php echo htmlspecialchars($appointment['phone']); ?></span>
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
                <span style="color: #28a745;">CONFIRMADA</span>
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