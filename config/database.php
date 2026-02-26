<?php
$host = 'localhost';
$db_name = 'cursos_app';
$username = 'root';
$password = '1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<!-- Conexión a DB exitosa para $db_name -->";
} catch (PDOException $e) {
    $error_msg = "Error de conexión a la base de datos: " . $e->getMessage();
    file_put_contents('debug.log', $error_msg . ", Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("<div style='color:red;'>$error_msg</div>");
}
?>