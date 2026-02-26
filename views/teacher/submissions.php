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

$teacher_id = $_SESSION['user_id'];

// course_id ES OPCIONAL: si viene, filtramos; si no, mostramos entregas de todos los cursos del profe
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

// Filtro para mostrar solo entregas no calificadas (opcional)
$show_ungraded = filter_input(
    INPUT_GET,
    'show_ungraded',
    FILTER_VALIDATE_BOOLEAN,
    ['options' => ['default' => false]]
);

// Lógica para cargar entregas
try {
    $query = "
        SELECT 
            s.*, 
            u.username AS student_name, 
            t.title    AS task_title,
            c.title    AS course_title
        FROM submissions s
        JOIN users   u ON s.student_id = u.id
        JOIN tasks   t ON s.task_id   = t.id
        JOIN courses c ON t.course_id = c.id
        WHERE c.teacher_id = ?
    ";

    $params = [$teacher_id];

    // Si viene course_id, filtramos por ese curso
    if ($course_id) {
        $query   .= " AND c.id = ?";
        $params[] = $course_id;
    }

    // Si quiere ver solo no calificados
    if ($show_ungraded) {
        $query .= " AND (s.grade IS NULL OR s.grade = 0)";
    }

    // 👉 AQUÍ ESTABA EL ERROR: la columna correcta es submitted_at, no created_at
    $query .= " ORDER BY s.submitted_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar entregas: " . $e->getMessage();
}
?>

<div class="container mt-5 p-4 bg-secondary bg-opacity-10 rounded shadow">
    <h1 class="text-center mb-4 text-secondary">
        Entregas <?php echo $course_id ? 'del Curso' : 'de Mis Cursos'; ?>
    </h1>

    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php } elseif (isset($_GET['error']) || isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error'] ?? $error); ?></div>
    <?php } ?>

    <!-- Filtro para entregas no calificadas -->
    <div class="mb-3">
        <a href="?<?php echo $course_id ? 'course_id=' . $course_id . '&' : ''; ?>show_ungraded=1"
           class="btn btn-warning <?php echo $show_ungraded ? 'active' : ''; ?>">
            Mostrar solo no calificados
        </a>

        <a href="?<?php echo $course_id ? 'course_id=' . $course_id : ''; ?>"
           class="btn btn-secondary <?php echo !$show_ungraded ? 'active' : ''; ?>">
            Mostrar todas
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-secondary text-white">
            Lista de Entregas
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Tarea</th>
                        <th>Archivo</th>
                        <th>Fecha envío</th>
                        <th>Calificación</th>
                        <th>Feedback</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)) { ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay entregas disponibles.</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($submissions as $submission) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['student_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($submission['course_title'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($submission['task_title'] ?? ''); ?></td>
                                <td>
                                    <?php if (!empty($submission['file_path'])): ?>
                                        <a href="/<?php echo htmlspecialchars($submission['file_path']); ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-link"
                                           download>
                                            Descargar
                                        </a>
                                    <?php elseif (!empty($submission['text_submission'])): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#textSubmissionModal<?php echo $submission['id']; ?>">
                                            Ver texto
                                        </button>

                                        <!-- Modal para ver texto de la entrega -->
                                        <div class="modal fade" id="textSubmissionModal<?php echo $submission['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Entrega de <?php echo htmlspecialchars($submission['student_name'] ?? ''); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><?php echo nl2br(htmlspecialchars($submission['text_submission'])); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Sin archivo / texto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($submission['submitted_at'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($submission['grade'] ?? 'Sin calificar'); ?></td>
                                <td><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></td>
                                
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
ob_end_flush();
require_once '../../includes/footer.php';
?>
