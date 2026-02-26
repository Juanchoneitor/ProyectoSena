<?php
ob_start(); // Inicia el buffer de salida para evitar problemas con headers
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación y redirección antes de salida
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: ../../views/auth/login.php');
    ob_end_clean();
    exit;
}

// Obtener datos de la entrega desde la URL o parámetros
$submission_id = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);
if (!$submission_id) {
    header('Location: /cursos_app/views/teacher/submissions.php?course_id=1');
    ob_end_clean();
    exit;
}

$submission = [];
try {
    $stmt = $pdo->prepare("SELECT s.*, u.username AS student_name, t.title AS task_title, t.course_id FROM submissions s JOIN users u ON s.student_id = u.id JOIN tasks t ON s.task_id = t.id WHERE s.id = ?");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$submission) {
        $error = "Entrega no encontrada.";
    }
} catch (PDOException $e) {
    $error = "Error al cargar la entrega: " . $e->getMessage();
}

// Procesar formulario de calificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $grade = filter_input(INPUT_POST, 'grade', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
    $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);
    $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error = "Token CSRF inválido.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE submissions SET grade = ?, feedback = ? WHERE id = ?");
            $stmt->execute([$grade, $feedback, $submission_id]);
            header('Location: /cursos_app/views/teacher/submissions.php?course_id=' . ($submission['course_id'] ?? 1) . '&success=Calificación guardada');
            ob_end_clean();
            exit;
        } catch (PDOException $e) {
            $error = "Error al guardar la calificación: " . $e->getMessage();
        }
    }
}

ob_end_flush();
?>

<div class="container mt-5">
    <h1 class="text-center text-success mb-4">Revisión Detallada de Entrega</h1>
    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php } elseif (isset($error)) { ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if ($submission): ?>
        <div class="row">
            <div class="col-md-4 bg-light p-3">
                <h4 class="text-success">Información</h4>
                <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($submission['student_name'] ?? 'Desconocido'); ?></p>
                <p><strong>Tarea:</strong> <?php echo htmlspecialchars($submission['task_title'] ?? 'Sin título'); ?></p>
                <p><strong>Archivo:</strong> <a href="/cursos_app/<?php echo htmlspecialchars($submission['file_path'] ?? '#'); ?>" target="_blank" class="btn btn-sm btn-success" download>Descargar</a></p>
            </div>
            <div class="col-md-8 bg-light p-3">
                <h4 class="text-success">Calificación</h4>
                <p><strong>Calificación Actual:</strong> <?php echo htmlspecialchars($submission['grade'] ?? 'Sin calificar'); ?></p>
                <p><strong>Feedback Actual:</strong> <?php echo htmlspecialchars($submission['feedback'] ?? 'Sin feedback'); ?></p>
                <form method="POST" action="" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission_id); ?>">
                    <div class="mb-3">
                        <label for="grade" class="form-label">Nueva Calificación (0-100)</label>
                        <input type="number" class="form-control" id="grade" name="grade" min="0" max="100" value="<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Nuevo Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Guardar Cambios</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <p class="text-center text-danger">No se encontró la entrega.</p>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>