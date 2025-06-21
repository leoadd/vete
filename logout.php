<?php
// logout.php - Cerrar sesión del usuario
require_once 'config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, también hay que borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir al index con mensaje de logout exitoso
header('Location: index.php?message=' . urlencode('Sesión cerrada exitosamente') . '&type=success');
exit;
?>