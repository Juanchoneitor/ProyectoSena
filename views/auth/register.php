<?php
// Iniciar output buffering ANTES de cualquier cosa
ob_start();

session_start();

// Generar CSRF token si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar que los archivos existen antes de incluirlos
$config_path = '../../config/database.php';

if (!file_exists($config_path)) {
    die("Error: No se puede encontrar el archivo de configuración de base de datos en: " . $config_path);
}

require_once $config_path;

// PROCESAR EL FORMULARIO ANTES DE INCLUIR EL HEADER
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de seguridad inválido.";
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Validar campos
        if (empty($username) || empty($email) || empty($password) || empty($role)) {
            $error = "Por favor, completa todos los campos.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                
                // Limpiar el buffer y redirigir
                ob_end_clean();
                header('Location: ./login.php');
                exit;
            } catch (PDOException $e) {
                $error = "Error al registrarse: " . $e->getMessage();
            }
        }
    }
}

// INCLUIR HEADER DESPUÉS de procesar el formulario
$header_path = '../../includes/header.php';
if (!file_exists($header_path)) {
    die("Error: No se puede encontrar el archivo header en: " . $header_path);
}
require_once $header_path;
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h3>Registrarse</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)) { ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php } ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student">Estudiante</option>
                                <option value="teacher">Docente</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                    </form>
                    <p class="mt-3 text-center">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$footer_path = '../../includes/footer.php';
if (file_exists($footer_path)) {
    require_once $footer_path;
} else {
    echo '</body></html>'; // Cierre básico si no existe footer
}

// Finalizar output buffering al final
ob_end_flush();
?>