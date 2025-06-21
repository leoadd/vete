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
if ($_POST['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $_SESSION['user_email'] = $email;
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = "Credenciales incorrectas";
            }
        } else {
            $error_message = "Usuario no encontrado";
        }
    } else {
        $error_message = "Por favor complete todos los campos";
    }
}

// Procesar registro
if ($_POST['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $name = htmlspecialchars($_POST['name']);
    $phone = htmlspecialchars($_POST['phone']);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password) && !empty($name)) {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Este email ya está registrado";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, name, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $hashed_password, $name, $phone);
            
            if ($stmt->execute()) {
                $success_message = "Cuenta creada exitosamente. Ahora puedes iniciar sesión.";
            } else {
                $error_message = "Error al crear la cuenta";
            }
        }
    } else {
        $error_message = "Por favor complete todos los campos obligatorios";
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
<body>
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