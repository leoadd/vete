<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Delete Appointment
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $appointment_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($appointment_id) {
        // Verify the appointment belongs to the current user before deleting
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $appointment_id, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Cita eliminada exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar la cita.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID de cita inválido.";
    }
    header('Location: dashboard.php');
    exit;
}

// Handle Update Appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $appointment_id = filter_var($_POST['appointment_id'], FILTER_SANITIZE_NUMBER_INT);
    $pet_name = htmlspecialchars($_POST['pet_name']);
    $service = htmlspecialchars($_POST['service']);
    $appointment_date = htmlspecialchars($_POST['appointment_date']);
    $appointment_time = htmlspecialchars($_POST['appointment_time']);

    if ($appointment_id && !empty($pet_name) && !empty($service) && !empty($appointment_date) && !empty($appointment_time)) {
        // Validate date and time format if necessary (e.g., using DateTime::createFromFormat)

        // Verify the appointment belongs to the current user before updating
        $stmt_check = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $appointment_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $stmt_update = $conn->prepare("UPDATE appointments SET pet_name = ?, service = ?, appointment_date = ?, appointment_time = ? WHERE id = ? AND user_id = ?");
            $stmt_update->bind_param("ssssii", $pet_name, $service, $appointment_date, $appointment_time, $appointment_id, $user_id);

            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Cita actualizada exitosamente.";
            } else {
                $_SESSION['error_message'] = "Error al actualizar la cita: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $_SESSION['error_message'] = "No tienes permiso para actualizar esta cita o la cita no existe.";
        }
        $stmt_check->close();
    } else {
        $_SESSION['error_message'] = "Por favor complete todos los campos para actualizar.";
    }
    header('Location: dashboard.php');
    exit;
}

// Redirect to dashboard if no specific action is recognized
header('Location: dashboard.php');
exit;
?>
