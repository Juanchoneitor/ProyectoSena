<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Generar CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validar admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../views/auth/login.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Gestión de Usuarios</h1>

    <?php if (isset($_GET['success'])) { ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php } elseif (isset($_GET['error'])) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php } ?>

    <!-- Crear Usuario -->
    <div class="card mb-4">
        <div class="card-header">Crear Nuevo Usuario</div>
        <div class="card-body">
            <form method="POST" action="../../controllers/userController.php">

                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Nombre de Usuario</label>
                    <input type="text" class="form-control" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Rol</label>
                    <select class="form-select" name="role" required>
                        <option value="student">Estudiante</option>
                        <option value="teacher">Docente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </form>
        </div>
    </div>

    <!-- Tabla usuarios -->
    <div class="card">
        <div class="card-header">Lista de Usuarios</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                        <td>

                            <!-- BOTÓN EDITAR -->
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $user['id'] ?>">Editar</button>

                            <!-- BOTÓN ELIMINAR (POST CORRECTO) -->
                            <form method="POST" action="../../controllers/userController.php" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">

                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                    Eliminar
                                </button>
                            </form>

                        </td>
                    </tr>

                    <!-- MODAL EDITAR -->
                    <div class="modal fade" id="editModal<?= $user['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Usuario: <?= htmlspecialchars($user['username']) ?></h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <form method="POST" action="../../controllers/userController.php">

                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Nombre de Usuario</label>
                                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Correo Electrónico</label>
                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nueva Contraseña (opcional)</label>
                                            <input type="password" class="form-control" name="password">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Rol</label>
                                            <select class="form-select" name="role">
                                                <option value="student" <?= $user['role']=='student'?'selected':'' ?>>Estudiante</option>
                                                <option value="teacher" <?= $user['role']=='teacher'?'selected':'' ?>>Docente</option>
                                                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Administrador</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>
</div>

<?php require '../../includes/footer.php'; ?>
