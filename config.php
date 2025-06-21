<?php
// config.php - Configuración de conexión a la base de datos

// Configuración para InfinityFree
$host = 'sql300.infinityfree.com'; // Cambia por tu host de InfinityFree
$username = 'if0_39283702'; // Tu usuario de base de datos
$password = 'BEcR9K2koSc1'; // Tu contraseña de base de datos
$database = 'if0_39283702_vete'; // Nombre de tu base de datos

// Crear conexión
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8");

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
