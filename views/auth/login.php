<?php
session_start();

// Incluir configuración de base de datos PRIMERO (no envía salida)
require_once dirname(__DIR__, 2) . '/config/database.php';

// Variable para errores
$error = null;

// PROCESAR EL FORMULARIO ANTES DE CUALQUIER HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            throw new Exception("Por favor, completa todos los campos.");
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Definir redirects
            $redirects = [
                'admin' => '/views/admin/dashboard.php',
                'teacher' => '/views/teacher/dashboard.php',
                'student' => '/views/student/dashboard.php'
            ];

            $role = $user['role'];
            $redirect = $redirects[$role] ?? $redirects['student'];

            // REDIRECT ANTES DE CUALQUIER SALIDA
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = "Credenciales incorrectas.";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// AHORA SÍ incluir header/navbar (DESPUÉS de procesar el formulario)
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 login-card">
            <div class="card-body p-4">
                <h3 class="text-center mb-4 fw-bold text-primary">Iniciar Sesión</h3>

                <?php if (isset($error)) { ?>
                    <div class="alert alert-danger text-center py-2 rounded-3">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php } ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0 rounded-end" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0 rounded-end" id="password" name="password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Iniciar Sesión</button>
                </form>

                <p class="mt-3 text-center mb-0">
                    ¿No tienes cuenta?
                    <a href="../auth/register.php" class="text-decoration-none text-primary fw-semibold">Regístrate</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background-color: #f9fafb;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .login-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .login-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
    }
    .btn-primary {
        background-color: #2563eb;
        border: none;
        transition: all 0.2s ease-in-out;
    }
    .btn-primary:hover {
        background-color: #1d4ed8;
        transform: scale(1.02);
    }
    .input-group-text {
        border-color: #e5e7eb;
    }
</style>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>