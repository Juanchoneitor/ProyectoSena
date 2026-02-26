<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación del estudiante
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
    ob_end_clean();
    exit;
}

$student_id = $_SESSION['user_id'];
$submissions = [];

try {
    $sql = "SELECT s.id, s.submitted_at, s.grade, s.feedback, s.file_path, s.text_submission,
                   t.title AS task_title, c.title AS course_title
            FROM submissions s
            INNER JOIN tasks t ON s.task_id = t.id
            INNER JOIN courses c ON t.course_id = c.id
            WHERE s.student_id = ?
            ORDER BY s.submitted_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar las entregas: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h1 class="text-center mb-4 fw-bold text-primary">📘 Mis Entregas Realizadas</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif (empty($submissions)): ?>
        <div class="alert alert-info text-center">Aún no has realizado ninguna entrega.</div>
    <?php else: ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-hover align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Tarea</th>
                        <th>Curso</th>
                        <th>Fecha de Entrega</th>
                        <th>Archivo</th>
                        <th>Calificación</th>
                        <th>Retroalimentación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['task_title']); ?></td>
                            <td><?php echo htmlspecialchars($sub['course_title']); ?></td>
                            <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($sub['submitted_at'])); ?></td>
                            <td class="text-center">
                                <?php if (!empty($sub['file_path']) && file_exists($sub['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" download class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download"></i> Descargar
                                    </a>
                                <?php elseif (!empty($sub['text_submission'])): ?>
                                    <span class="badge bg-info">Entrega de texto</span>
                                <?php else: ?>
                                    <span class="text-muted">Sin archivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($sub['grade'] !== null): ?>
                                    <span class="badge bg-success fs-6"><?php echo htmlspecialchars($sub['grade']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($sub['feedback']) ? htmlspecialchars($sub['feedback']) : '<span class="text-muted">Sin comentarios</span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary me-2">⬅️ Volver al Panel</a>
        <a href="tasks.php" class="btn btn-primary">📋 Ver Tareas Pendientes</a>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ob_end_flush(); ?>