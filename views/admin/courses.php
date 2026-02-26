<?php
ob_start();

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../views/auth/login.php');
    exit;
}

// Obtener categorías
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Obtener docentes
$teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher'")->fetchAll();

// Obtener lista de cursos
$courses = $pdo->query("SELECT c.*, u.username as teacher_name, cat.name as category_name 
                        FROM courses c 
                        JOIN users u ON c.teacher_id = u.id 
                        JOIN categories cat ON c.category_id = cat.id")->fetchAll();
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Gestión de Cursos</h1>
    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
    <?php } elseif (isset($_GET['error'])) { ?>
        <div class="alert alert-danger"><?php echo $_GET['error']; ?></div>
    <?php } ?>
    <!-- Formulario para crear curso -->
    <div class="card mb-4">
        <div class="card-header">Crear Nuevo Curso</div>
        <div class="card-body">
            <form method="POST" action="../controllers/courseController.php?action=create">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Título</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">Categoría</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category) { ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="teacher_id" class="form-label">Docente</label>
                    <select class="form-select" id="teacher_id" name="teacher_id" required>
                        <?php foreach ($teachers as $teacher) { ?>
                            <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['username']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"action="create">Crear Curso</button>
            </form>
        </div>
    </div>
    <!-- Lista de cursos -->
    <div class="card">
        <div class="card-header">Lista de Cursos</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Docente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course) { ?>
                        <tr>
                            <td><?php echo $course['title']; ?></td>
                            <td><?php echo $course['category_name']; ?></td>
                            <td><?php echo $course['teacher_name']; ?></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-warning">Editar</a>
                                <a href="../controllers/courseController.php?action=delete&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-danger">Eliminar</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>