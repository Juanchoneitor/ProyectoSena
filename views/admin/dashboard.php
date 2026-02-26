<?php

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../views/auth/login.php');
    exit;
}

// Obtener estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
$total_courses = $stmt->fetch()['total_courses'];

$stmt = $pdo->query("SELECT COUNT(*) as total_enrollments FROM enrollments");
$total_enrollments = $stmt->fetch()['total_enrollments'];
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Panel de Administrador</h1>
    <div class="row">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Usuarios</h5>
                    <p class="card-text"><?php echo $total_users; ?></p>
                    <a href="users.php" class="btn btn-primary">Gestionar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Cursos</h5>
                    <p class="card-text"><?php echo $total_courses; ?></p>
                    <a href="courses.php" class="btn btn-primary">Gestionar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Inscripciones</h5>
                    <p class="card-text"><?php echo $total_enrollments; ?></p>
                    <a href="users.php" class="btn btn-primary">Ver Detalles</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>