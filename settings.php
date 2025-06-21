<?php
require_once 'config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Obtener datos actuales del usuario
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Manejar el caso improbable de que el usuario no se encuentre
    session_destroy();
    header('Location: index.php');
    exit;
}

// Procesar actualización de datos personales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_details') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));

    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Nombre y correo electrónico válido son obligatorios.";
    } else {
        // Verificar si el nuevo email ya existe para otro usuario
        if ($email !== $user['email']) {
            $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check_email->bind_param("si", $email, $user_id);
            $stmt_check_email->execute();
            if ($stmt_check_email->get_result()->num_rows > 0) {
                $error_message = "El correo electrónico ingresado ya está en uso por otra cuenta.";
            }
            $stmt_check_email->close();
        }

        if (empty($error_message)) {
            $stmt_update = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt_update->bind_param("sssi", $name, $email, $phone, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['user_name'] = $name; // Actualizar nombre en sesión
                $_SESSION['user_email'] = $email; // Actualizar email en sesión
                $success_message = "Datos actualizados correctamente.";
                // Recargar datos del usuario
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
            } else {
                $error_message = "Error al actualizar los datos.";
            }
            $stmt_update->close();
        }
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Todos los campos de contraseña son obligatorios.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "La nueva contraseña y la confirmación no coinciden.";
    } elseif (strlen($new_password) < 6) { // Ejemplo de validación de longitud mínima
        $error_message = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
        // Obtener hash de contraseña actual de la BD
        $stmt_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_pass->bind_param("i", $user_id);
        $stmt_pass->execute();
        $result_pass = $stmt_pass->get_result();
        $user_data = $result_pass->fetch_assoc();
        $stmt_pass->close();

        if ($user_data && password_verify($current_password, $user_data['password'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
            if ($stmt_update_pass->execute()) {
                $success_message = "Contraseña actualizada correctamente.";
            } else {
                $error_message = "Error al actualizar la contraseña.";
            }
            $stmt_update_pass->close();
        } else {
            $error_message = "La contraseña actual es incorrecta.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes de Cuenta - VetCitas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h2><a href="dashboard.php" style="text-decoration: none; color: inherit;">🐾 VetCitas</a></h2>
                <p>Ajustes de tu cuenta</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary" style="width: auto; padding: 10px 20px; margin-right:10px;">Volver al Panel</a>
                <a href="logout.php" class="btn btn-secondary" style="width: auto; padding: 10px 20px;">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <?php if ($error_message): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Formulario de Datos Personales -->
        <div class="booking-form" style="margin-bottom: 30px;">
            <h3>👤 Mis Datos Personales</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_details">
                <div class="form-group">
                    <label for="name">Nombre completo</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <button type="submit" class="btn">Guardar Cambios</button>
            </form>
        </div>

        <!-- Formulario de Cambio de Contraseña -->
        <div class="booking-form">
            <h3>🔒 Cambiar Contraseña</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                <div class="form-group">
                    <label for="current_password">Contraseña Actual</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Actualizar Contraseña</button>
            </form>
        </div>
    </div>

</body>
</html>
