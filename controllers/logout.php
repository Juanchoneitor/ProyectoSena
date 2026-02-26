
<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();
header('Location: ../views/auth/login.php?success=Sesión cerrada exitosamente');
exit;
?>
