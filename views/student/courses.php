<?php
session_start(); // ← NECESARIO
ob_start();

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    ob_end_clean();
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
    exit;
}

// Inicializar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$student_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name as category_name, u.username as teacher_name 
        FROM courses c 
        JOIN categories cat ON c.category_id = cat.id 
        JOIN users u ON c.teacher_id = u.id 
        WHERE c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)
    ");
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar los cursos: " . $e->getMessage();
}

// Depuración
file_put_contents('debug.log',
    "Cargando courses.php - SessionID: ".session_id().
    ", CSRF: " . ($_SESSION['csrf_token'] ?? 'No definido') .
    ", UserID: " . ($_SESSION['user_id'] ?? 'N/A') .
    ", Rol: " . ($_SESSION['role'] ?? 'N/A') .
    ", Fecha: " . date('Y-m-d H:i:s') . "\n",
    FILE_APPEND
);
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Explorar Cursos Disponibles</h1>

    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php } elseif (isset($_GET['error'])) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php } elseif (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if (empty($courses)) { ?>
        <div class="alert alert-info">No hay cursos disponibles para inscribirse.</div>
    <?php } else { ?>
        <div class="row">
            <?php foreach ($courses as $course) { ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($course['description'] ?: 'Sin descripción'); ?></p>
                            <p class="card-text"><small class="text-muted">Docente: <?php echo htmlspecialchars($course['teacher_name']); ?></small></p>
                            <p class="card-text"><small class="text-muted">Categoría: <?php echo htmlspecialchars($course['category_name']); ?></small></p>

                            <form method="POST" action="../../controllers/courseController.php">
                                <input type="hidden" name="action" value="enroll">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                                <button type="submit" class="btn btn-primary">Inscribirse</button>
                            </form>

                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="text-center mt-4">
        <a href="myCourses.php" class="btn btn-secondary">Ver Mis Cursos</a>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
