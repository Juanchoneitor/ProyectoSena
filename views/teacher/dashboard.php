<?php

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: ../../views/auth/login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Obtener estadísticas 
try {
    // Contar cursos del docente
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $total_courses = $stmt->fetch()['total_courses'];
} catch (PDOException $e) {
    $total_courses = 0;
}

try {
    // Contar tareas del docente
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_tasks FROM tasks WHERE course_id IN (SELECT id FROM courses WHERE teacher_id = ?)");
    $stmt->execute([$teacher_id]);
    $total_tasks = $stmt->fetch()['total_tasks'];
} catch (PDOException $e) {
    $total_tasks = 0;
}

try {
    // Contar entregas de las tareas del docente
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_submissions FROM submissions WHERE task_id IN (SELECT id FROM tasks WHERE course_id IN (SELECT id FROM courses WHERE teacher_id = ?))");
    $stmt->execute([$teacher_id]);
    $total_submissions = $stmt->fetch()['total_submissions'];
} catch (PDOException $e) {
    $total_submissions = 0;
}
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Panel de Docente</h1>
    <div class="row">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Cursos</h5>
                    <p class="card-text display-4"><?php echo $total_courses; ?></p>
                    <a href="courses.php" class="btn btn-primary">Gestionar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Tareas</h5>
                    <p class="card-text display-4"><?php echo $total_tasks; ?></p>
                    <a href="tasks.php" class="btn btn-primary">Gestionar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Entregas</h5>
                    <p class="card-text display-4"><?php echo $total_submissions; ?></p>
                    <a href="submissions.php" class="btn btn-primary">Gestionar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>