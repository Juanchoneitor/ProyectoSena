<?php
ob_start(); 

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación correctamente ANTES de imprimir HTML
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como docente');
    exit;
}

// Generar CSRF token si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cargar cursos
$teacher_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT id, title, description FROM courses WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar cursos: " . $e->getMessage();
    $courses = [];
}
?>

<div class="container mt-5 p-4 bg-light rounded shadow">
    <h1 class="text-center mb-4 text-primary">Mis Cursos</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Lista de Cursos</div>
                <div class="card-body">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No hay cursos disponibles.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($course['title']) ?></td>
                                        <td><?= htmlspecialchars($course['description']) ?></td>
                                        <td>
                                            <a href="/views/teacher/tasks.php?course_id=<?= $course['id'] ?>" 
                                               class="btn btn-sm btn-info">
                                                Ver Tareas
                                            </a>

                                            <a href="/views/teacher/edit_course.php?course_id=<?= $course['id'] ?>" 
                                               class="btn btn-sm btn-warning ms-2">
                                                Editar
                                            </a>

                                            <!-- Botón para Abrir Modal de Eliminación -->
                                            <button type="button"
                                                class="btn btn-sm btn-danger ms-2 delete-course"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCourseModal"
                                                data-id="<?= $course['id'] ?>"
                                                data-title="<?= htmlspecialchars($course['title']) ?>">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <!-- Crear curso -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">Crear Nuevo Curso</div>
                <div class="card-body">
                    <form method="POST" action="/controllers/courseController.php?action=create">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">

                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-control" name="category_id" required>
                                <?php
                                try {
                                    $stmt_cat = $pdo->query("SELECT id, name FROM categories");
                                    foreach ($stmt_cat->fetchAll(PDO::FETCH_ASSOC) as $category) {
                                        echo "<option value='" . $category['id'] . "'>" . htmlspecialchars($category['name']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option>Error al cargar categorías</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Crear Curso</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p>¿Estás seguro que deseas eliminar el curso <strong id="delete_course_title"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Esta acción no se puede deshacer.</p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

                <form method="POST" action="/controllers/courseController.php?action=delete">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id" id="delete_course_id">
                    <button type="submit" class="btn btn-danger">Eliminar Curso</button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    let deleteCourseModal = document.getElementById('deleteCourseModal');

    deleteCourseModal.addEventListener('show.bs.modal', function (event) {
        let button = event.relatedTarget;
        let id = button.getAttribute('data-id');
        let title = button.getAttribute('data-title');

        document.getElementById('delete_course_id').value = id;
        document.getElementById('delete_course_title').textContent = title;
    });

});
</script>