<?php
ob_start();
$host     = getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('DB_PORT')     ?: '3306';
$db_name  = getenv('DB_NAME')     ?: 'cursos_app';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Error de conexión a la base de datos: " . $e->getMessage();
    error_log($error_msg);
    die("<div style='color:red;'>Error de conexión. Revisa la configuración de la base de datos.</div>");
}

// Guardar sesiones en DB para que persistan en producción
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', sys_get_temp_dir());
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.cookie_samesite', 'Lax');
}
?>