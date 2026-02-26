<?php
ob_start();
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
    ob_end_clean();
    exit;
}

$student_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, u.username as teacher_name 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        JOIN users u ON c.teacher_id = u.id 
        WHERE e.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar tus cursos: " . $e->getMessage();
}

// Depuración
file_put_contents('debug.log', "Cargando myCourses.php - Student ID: $student_id, Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Mis Cursos</h1>

    <?php if (isset($_GET['error'])) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php } elseif (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if (empty($courses)) { ?>
        <div class="alert alert-info">No estás inscrito en ningún curso.</div>
    <?php } else { ?>
        <div class="row">
            <?php foreach ($courses as $course) { ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($course['description'] ?: 'Sin descripción'); ?></p>
                            <p class="card-text"><small class="text-muted">Docente: <?php echo htmlspecialchars($course['teacher_name']); ?></small></p>
                            
                            <a href="viewCourse.php?id=<?php echo htmlspecialchars($course['id']); ?>" class="btn btn-primary mb-2">Ver Detalles</a>
                            <a href="tasks.php?course_id=<?php echo htmlspecialchars($course['id']); ?>" class="btn btn-outline-success mb-2">📌 Ver Tareas</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="text-center mt-4">
        <a href="courses.php" class="btn btn-secondary">Explorar Nuevos Cursos</a>
        <a href="tasks.php" class="btn btn-success">📋 Ver Todas Mis Tareas</a>
    </div>
</div>

<?php ob_end_flush(); require_once '../../includes/footer.php'; ?>
