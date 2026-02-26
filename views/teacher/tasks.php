<?php
ob_start(); // Inicia el buffer para evitar problemas con headers
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Depuración detallada de sesión
file_put_contents(
    'debug.log',
    'Acceso a teacher tasks.php - user_id: ' . ($_SESSION['user_id'] ?? 'N/A') .
    ', role: ' . ($_SESSION['role'] ?? 'N/A') .
    ', URL: ' . $_SERVER['REQUEST_URI'] . "\n",
    FILE_APPEND
);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    file_put_contents('debug.log', 'Redirigiendo al login por falta de sesión o rol incorrecto' . "\n", FILE_APPEND);
    header('Location: ../../views/auth/login.php');
    ob_end_clean();
    exit;
}

$teacherId = $_SESSION['user_id'];

$success = filter_input(INPUT_GET, 'success');
$error   = filter_input(INPUT_GET, 'error');

// ✅ NUEVO: course_id opcional (viene desde courses.php con "Ver Tareas")
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

// Asegurar que el token CSRF esté siempre presente
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener los cursos del profesor para los selectores
try {
    $stmt_courses = $pdo->prepare("
        SELECT id, title 
        FROM courses 
        WHERE teacher_id = ? 
        ORDER BY title
    ");
    $stmt_courses->execute([$teacherId]);
    $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error   = "Error al cargar cursos: " . $e->getMessage();
    $courses = [];
}

// ✅ NUEVO: si viene course_id, validar que ese curso pertenezca al teacher (seguridad)
if ($courseId) {
    try {
        $stmtCheck = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $stmtCheck->execute([$courseId, $teacherId]);
        $validCourse = $stmtCheck->fetchColumn();

        if (!$validCourse) {
            header('Location: /views/teacher/courses.php?error=No tienes acceso a ese curso');
            ob_end_clean();
            exit;
        }
    } catch (PDOException $e) {
        header('Location: /views/teacher/courses.php?error=Error validando el curso');
        ob_end_clean();
        exit;
    }
}

/**
 * CONSULTAR SOLO LAS TAREAS DEL PROFESOR LOGUEADO
 * + SI VIENE course_id => SOLO LAS TAREAS DE ESE CURSO
 */
try {
    $sql = "
        SELECT 
            t.id AS task_id,
            t.title,
            t.description,
            t.due_date,
            t.course_id,
            c.title AS course_name
        FROM tasks t
        LEFT JOIN courses c ON t.course_id = c.id
        WHERE c.teacher_id = :teacher_id
    ";

    // ✅ NUEVO: filtrar por curso seleccionado si viene course_id
    $params = ['teacher_id' => $teacherId];
    if ($courseId) {
        $sql .= " AND t.course_id = :course_id ";
        $params['course_id'] = $courseId;
    }

    $sql .= " ORDER BY t.due_date DESC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar tareas: " . $e->getMessage();
    $tasks = [];
}

ob_end_flush(); // Envia el buffer al navegador
?>

<div class="container mt-5">
    <h1 class="text-center text-primary">Gestión de Mis Tareas</h1>
    <div class="row">
        <div class="col-md-8">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Mis Tareas y Entregas</h4>
                    <span class="badge bg-light text-primary">
                        <?php echo count($tasks); ?> tareas
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <p class="text-muted">No has creado tareas aún.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($tasks as $task): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong><br>
                                    <small>Descripción: <?php echo htmlspecialchars($task['description']); ?></small><br>
                                    <small>Fecha de entrega: <?php echo htmlspecialchars($task['due_date']); ?></small><br>
                                    <small><strong>Curso:</strong> <?php echo htmlspecialchars($task['course_name'] ?? 'General'); ?></small>
                                    <div class="mt-2">
                                        <button class="btn btn-warning btn-sm edit-task"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTaskModal"
                                            data-id="<?php echo $task['task_id']; ?>"
                                            data-title="<?php echo htmlspecialchars($task['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($task['description']); ?>"
                                            data-due-date="<?php echo htmlspecialchars($task['due_date']); ?>"
                                            data-course-id="<?php echo htmlspecialchars($task['course_id'] ?? ''); ?>">
                                            Editar
                                        </button>

                                        <button class="btn btn-danger btn-sm delete-task"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteTaskModal"
                                            data-id="<?php echo $task['task_id']; ?>"
                                            data-title="<?php echo htmlspecialchars($task['title']); ?>">
                                            Eliminar
                                        </button>
                                    </div>

                                    <!-- Sección de Entregas -->
                                    <?php
                                    $stmt_submissions = $pdo->prepare("
                                        SELECT 
                                            s.id AS submission_id,
                                            s.student_id,
                                            s.file_path,
                                            s.grade,
                                            s.feedback,
                                            s.task_id,
                                            u.username AS student_name 
                                        FROM submissions s 
                                        LEFT JOIN users u ON s.student_id = u.id
                                        WHERE s.task_id = ?
                                    ");
                                    $stmt_submissions->execute([$task['task_id']]);
                                    $submissions = $stmt_submissions->fetchAll(PDO::FETCH_ASSOC);
                                    if (!empty($submissions)): ?>
                                        <div class="mt-3">
                                            <h5>Entregas de Estudiantes</h5>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Estudiante</th>
                                                        <th>Archivo</th>
                                                        <th>Calificación</th>
                                                        <th>Feedback</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($submissions as $submission): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($submission['student_name'] ?? 'ID: ' . $submission['student_id']); ?></td>
                                                            <td>
                                                                <?php if (!empty($submission['file_path'])):
                                                                    $fileBasename = basename($submission['file_path']);
                                                                ?>
                                                                    <a href="/controllers/download.php?file=<?php echo urlencode($fileBasename); ?>"
                                                                        class="btn btn-sm btn-outline-primary"
                                                                        target="_blank">
                                                                        <i class="bi bi-file-earmark-pdf"></i> Ver archivo
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No disponible</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($submission['grade'])): ?>
                                                                    <span class="badge bg-success"><?php echo htmlspecialchars($submission['grade']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">No calificada</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($submission['feedback'] ?? 'Sin feedback'); ?></td>
                                                            <td>
                                                                <button class="btn btn-info btn-sm review-submission"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#reviewSubmissionModal"
                                                                    data-submission-id="<?php echo $submission['submission_id']; ?>"
                                                                    data-task-id="<?php echo $submission['task_id']; ?>"
                                                                    data-student-id="<?php echo $submission['student_id']; ?>"
                                                                    data-student-name="<?php echo htmlspecialchars($submission['student_name'] ?? 'ID: ' . $submission['student_id']); ?>"
                                                                    data-file-path="<?php echo htmlspecialchars($submission['file_path'] ?? ''); ?>"
                                                                    data-grade="<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>"
                                                                    data-feedback="<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>">
                                                                    Revisar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna lateral: crear tarea -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4>Crear Nueva Tarea</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="/controllers/taskController.php?action=create">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="mb-3">
                            <label for="course_id" class="form-label">Curso</label>
                            <select class="form-select" id="course_id" name="course_id">
                                <option value="">Curso General (sin curso específico)</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Título</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="due_date" class="form-label">Fecha de Entrega</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Crear Tarea</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Tarea -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Editar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="/controllers/taskController.php?action=update">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" id="edit_task_id">

                        <div class="mb-3">
                            <label for="edit_course_id" class="form-label">Curso</label>
                            <select class="form-select" id="edit_course_id" name="course_id">
                                <option value="">Curso General (sin curso específico)</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Título</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_description" name="description" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="edit_due_date" class="form-label">Fecha de Entrega</label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ELIMINAR TAREA -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTaskModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la tarea <strong id="delete_task_title"></strong>?</p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Esta acción no se puede deshacer y eliminará todas las entregas asociadas.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="/controllers/taskController.php?action=delete" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" id="delete_task_id">
                        <button type="submit" class="btn btn-danger">Eliminar Tarea</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Revisar Entrega -->
    <div class="modal fade" id="reviewSubmissionModal" tabindex="-1" aria-labelledby="reviewSubmissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewSubmissionModalLabel">Revisar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p><strong>Estudiante:</strong> <span id="review_student_id"></span></p>
                        <div id="file_preview_container">
                            <strong>Archivo:</strong>
                            <div class="mt-2">
                                <a href="#" id="review_file_link" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="bi bi-file-earmark-pdf"></i> Ver archivo en nueva pestaña
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <form method="POST" action="/controllers/taskController.php?action=grade" id="gradeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="submission_id" id="review_submission_id">
                        <input type="hidden" name="task_id" id="review_task_id">
                        <input type="hidden" name="student_id" id="review_student_id_hidden">

                        <div class="mb-3">
                            <label for="review_grade" class="form-label">Calificación (0-100)</label>
                            <input type="number" class="form-control" id="review_grade" name="grade" min="0" max="100" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="review_feedback" class="form-label">Feedback</label>
                            <textarea class="form-control" id="review_feedback" name="feedback" rows="4"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Guardar Calificación</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal para editar tarea
    var editTaskModal = document.getElementById('editTaskModal');
    if (editTaskModal) {
        editTaskModal.addEventListener('show.bs.modal', function(event) {
            var button     = event.relatedTarget;
            var id         = button.getAttribute('data-id');
            var title      = button.getAttribute('data-title');
            var description= button.getAttribute('data-description');
            var dueDate    = button.getAttribute('data-due-date');
            var courseId   = button.getAttribute('data-course-id');

            document.getElementById('edit_task_id').value       = id;
            document.getElementById('edit_title').value         = title;
            document.getElementById('edit_description').value   = description;
            document.getElementById('edit_due_date').value      = dueDate;
            document.getElementById('edit_course_id').value     = courseId || '';
        });
    }

    // Modal para eliminar tarea
    var deleteTaskModal = document.getElementById('deleteTaskModal');
    if (deleteTaskModal) {
        deleteTaskModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id     = button.getAttribute('data-id');
            var title  = button.getAttribute('data-title');

            document.getElementById('delete_task_id').value   = id;
            document.getElementById('delete_task_title').textContent = title;
        });
    }

    // Modal para revisar entrega
    var reviewSubmissionModal = document.getElementById('reviewSubmissionModal');
    if (reviewSubmissionModal) {
        reviewSubmissionModal.addEventListener('show.bs.modal', function(event) {
            var button      = event.relatedTarget;
            var submissionId= button.getAttribute('data-submission-id');
            var taskId      = button.getAttribute('data-task-id');
            var studentId   = button.getAttribute('data-student-id');
            var studentName = button.getAttribute('data-student-name');
            var filePath    = button.getAttribute('data-file-path');
            var grade       = button.getAttribute('data-grade');
            var feedback    = button.getAttribute('data-feedback');

            document.getElementById('review_submission_id').value      = submissionId || '';
            document.getElementById('review_task_id').value            = taskId || '';
            document.getElementById('review_student_id_hidden').value  = studentId || '';
            document.getElementById('review_student_id').textContent   = studentName || 'No disponible';
            document.getElementById('review_grade').value              = grade || '';
            document.getElementById('review_feedback').value           = feedback || '';

            var fileLink = document.getElementById('review_file_link');
            if (filePath && filePath.trim() !== '') {
                var cleanPath = filePath
                    .replace(/^\.\.\//, '')
                    .replace(/^assets\/uploads\//, 'uploads/submissions/')
                    .replace(/^uploads\/uploads\//, 'uploads/')
                    .replace(/^\/+/, '');

                var fileBasename = cleanPath.split('/').pop();
                var correctPath = '/controllers/download.php?file=' + encodeURIComponent(fileBasename);

                fileLink.href = correctPath;
                fileLink.style.display = 'inline-block';
                fileLink.classList.remove('disabled');
            } else {
                fileLink.href = '#';
                fileLink.style.display = 'none';
            }
        });
    }

    // Manejo del formulario de calificación
    var gradeForm = document.getElementById('gradeForm');
    if (gradeForm) {
        gradeForm.addEventListener('submit', function(e) {
            var submissionId = document.getElementById('review_submission_id').value;
            var grade        = document.getElementById('review_grade').value;

            if (!submissionId || submissionId === '') {
                e.preventDefault();
                alert('Error: ID de entrega no válido');
                return false;
            }

            if (!grade || grade < 0 || grade > 100) {
                e.preventDefault();
                alert('La calificación debe estar entre 0 y 100');
                return false;
            }

            return true;
        });
    }
});
</script>
