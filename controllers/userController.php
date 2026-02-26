<?php
session_start();
require_once '../config/database.php';

// Crear token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {

    // VALIDAR CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Acción no válida (CSRF)";
        header("Location: ../views/register.php");
        exit;
    }

    // VALIDAR ACCIÓN
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_usuario') {
        
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($nombre === '' || $email === '' || $password === '') {
            $_SESSION['error'] = "Todos los campos son obligatorios";
            header("Location: ../views/register.php");
            exit;
        }

        try {
            global $conn;

            // Verificar que no exista el email
            $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $_SESSION['error'] = "Este correo ya está registrado";
                header("Location: ../views/register.php");
                exit;
            }

            // Insertar usuario
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $insert = $conn->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $insert->execute([$nombre, $email, $hashed]);

            $_SESSION['success'] = "Usuario creado correctamente";
            header("Location: ../views/login.php");
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Error interno: " . $e->getMessage();
            header("Location: ../views/register.php");
            exit;
        }

    } else {
        // Si la acción NO coincide
        $_SESSION['error'] = "Acción no válida";
        header("Location: ../views/register.php");
        exit;
    }
}

?>
