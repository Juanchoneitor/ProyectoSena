
<?php
ob_start();

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como docente');
    ob_end_clean();
    exit;
}

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) {
    header('Location: ../teacher/courses.php?error=ID de curso no válido');
    ob_end_clean();
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        header('Location: ../teacher/courses.php?error=Curso no encontrado');
        ob_end_clean();
        exit;
    }
} catch (PDOException $e) {
    $error = "Error al cargar curso: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    try {
        $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, category_id = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$title, $description, $category_id, $course_id, $_SESSION['user_id']]);
        header('Location: ../teacher/courses.php?success=Curso actualizado exitosamente');
        ob_end_clean();
        exit;
    } catch (PDOException $e) {
        $error = "Error al actualizar curso: " . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Editar Curso</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>
    <div class="card">
        <div class="card-header">Editar Curso</div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Título</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">Categoría</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <?php
                        $stmt = $pdo->prepare("SELECT id, name FROM categories");
                        $stmt->execute();
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $category) {
                            $selected = ($course['category_id'] == $category['id']) ? 'selected' : '';
                            echo "<option value='{$category['id']}' $selected>{$category['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-bottom: 20px;">Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>

<?php
ob_end_flush();
require_once '../../includes/footer.php';
?>
