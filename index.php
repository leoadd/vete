<?php
// index.php - Página principal con login y registro
require_once 'config.php';

$error_message = '';
$success_message = '';

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Procesar login
if (isset($_POST['action']) && $_POST['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $email; // Guardar email en sesión
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = "Credenciales incorrectas. Verifique su correo y contraseña.";
            }
        } else {
            $error_message = "Usuario no encontrado. Verifique el correo electrónico ingresado.";
        }
        $stmt->close();
    } else {
        $error_message = "Por favor, complete todos los campos (correo y contraseña).";
    }
}

// Procesar registro
if (isset($_POST['action']) && $_POST['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password) && !empty($name)) {
        // Verificar si el email ya existe
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = "Este correo electrónico ya está registrado. Intente iniciar sesión.";
        } else {
            if (strlen($password) < 6) {
                 $error_message = "La contraseña debe tener al menos 6 caracteres.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (email, password, name, phone) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $email, $hashed_password, $name, $phone);

                if ($stmt_insert->execute()) {
                    $success_message = "¡Cuenta creada exitosamente! Ahora puede iniciar sesión.";
                } else {
                    $error_message = "Error al crear la cuenta. Por favor, inténtelo de nuevo.";
                }
                $stmt_insert->close();
            }
        }
        $stmt_check->close();
    } else {
        $error_message = "Por favor, complete todos los campos obligatorios (nombre, correo y contraseña).";
    }
}

// Mostrar mensaje de logout si existe
if (isset($_GET['message'])) {
    $message_type = $_GET['type'] ?? 'info'; // 'success', 'error', 'info'
    if ($message_type === 'success') {
        $success_message = htmlspecialchars($_GET['message']);
    } else {
        $error_message = htmlspecialchars($_GET['message']); // O un $info_message si se desea diferenciar
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Sistema de Citas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"> <!-- Specific background for index.php -->
    <div class="container">
        <div class="logo">
            <h1>🐾 VetCitas</h1>
            <p>Sistema de reservación de citas</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div id="login-form">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>

            <div class="switch-form">
                <p>¿No tienes cuenta? <a href="#" onclick="showRegister()">Crear cuenta</a></p>
            </div>
        </div>

        <div id="register-form" style="display: none;">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="reg-name">Nombre completo</label>
                    <input type="text" id="reg-name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="reg-email">Correo electrónico</label>
                    <input type="email" id="reg-email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="reg-phone">Teléfono</label>
                    <input type="tel" id="reg-phone" name="phone">
                </div>

                <div class="form-group">
                    <label for="reg-password">Contraseña</label>
                    <input type="password" id="reg-password" name="password" required>
                </div>

                <button type="submit" class="btn">Crear Cuenta</button>
            </form>

            <div class="switch-form">
                <p>¿Ya tienes cuenta? <a href="#" onclick="showLogin()">Iniciar sesión</a></p>
            </div>
        </div>
    </div>

    <script>
        function showRegister() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
        }

        function showLogin() {
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
        }
    </script>
</body>
</html>