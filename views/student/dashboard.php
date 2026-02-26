<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// 🔹 Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
    exit;
}

$student_id = $_SESSION['user_id'];

try {
    // 🔹 Total de cursos inscritos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM enrollments WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_courses = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'] ?? 0;

    // 🔹 Total de tareas pendientes (no entregadas)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_tasks 
        FROM tasks 
        WHERE course_id IN (SELECT course_id FROM enrollments WHERE student_id = ?)
        AND id NOT IN (SELECT task_id FROM submissions WHERE student_id = ?)
    ");
    $stmt->execute([$student_id, $student_id]);
    $total_pending_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['total_tasks'] ?? 0;

    // 🔹 Total de tareas entregadas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_submissions FROM submissions WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['total_submissions'] ?? 0;

    // 🔹 Obtener el primer curso inscrito (para los botones)
    $stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id = ? LIMIT 1");
    $stmt->execute([$student_id]);
    $first_course = $stmt->fetch(PDO::FETCH_ASSOC);
    $first_course_id = $first_course ? $first_course['course_id'] : null;

} catch (PDOException $e) {
    $error = "Error al cargar el panel: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Panel de Estudiante</h1>

    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <div class="row">
        <!-- 🔹 Cursos inscritos -->
        <div class="col-md-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">Cursos Inscritos</h5>
                    <p class="card-text display-5"><?php echo htmlspecialchars($total_courses); ?></p>
                    <a href="/views/student/courses.php" class="btn btn-primary">Explorar Cursos</a>
                </div>
            </div>
        </div>

        <!-- 🔹 Tareas pendientes -->
        <div class="col-md-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">Tareas Pendientes</h5>
                    <p class="card-text display-5"><?php echo htmlspecialchars($total_pending_tasks); ?></p>
                    <a href="<?php echo $first_course_id 
                        ? "/views/student/tasks.php?course_id=$first_course_id" 
                        : '/views/student/courses.php?error=Inscríbete en un curso primero'; ?>" 
                        class="btn btn-primary">Ver Tareas</a>
                </div>
            </div>
        </div>

        <!-- 🔹 Entregas realizadas -->
        <div class="col-md-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">Entregas Realizadas</h5>
                    <p class="card-text display-5"><?php echo htmlspecialchars($total_submissions); ?></p>
                    <a href="<?php echo $first_course_id 
                        ? "/views/student/submissions.php?course_id=$first_course_id" 
                        : '/views/student/courses.php?error=Inscríbete en un curso primero'; ?>" 
                        class="btn btn-primary">Ver Entregas</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>