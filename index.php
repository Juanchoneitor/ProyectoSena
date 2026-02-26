<?php
ob_start(); // Evita problemas con headers

require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: views/auth/login.php');
    ob_end_clean();
    exit;
}

$role = $_SESSION['role'] ?? 'guest';
?>

<div class="container mt-5">
    <h1 class="text-center">Bienvenido a la Plataforma de Cursos</h1>
    <p class="text-center">Rol actual: <?php echo htmlspecialchars($role); ?></p>
    <?php if ($role !== 'guest' && basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
        <p class="text-center"><a href="/cursos_app/views/<?php echo $role; ?>/dashboard.php" class="btn btn-primary">Ir a tu Dashboard</a></p>
    <?php endif; ?>
</div>

<?php
ob_end_flush();
require_once 'includes/footer.php';
?>