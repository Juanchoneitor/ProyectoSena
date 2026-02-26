<?php
session_start();
require_once '../../config/database.php';
require_once '../../controllers/SubmissionController.php';

// 🔒 Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../views/auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// 🔹 Validar que la tarea exista
if ($task_id === 0) {
    header("Location: tasks.php?error=Tarea no válida");
    exit();
}

$task = null;
$error = null;
$success = null;

try {
    // Obtener información de la tarea
    $stmt = $pdo->prepare("SELECT t.*, c.title AS course_title
                            FROM tasks t
                            JOIN courses c ON t.course_id = c.id
                            WHERE t.id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        $error = "Tarea no encontrada.";
    }
    
    // Verificar si ya existe una entrega
    $submissionCheck = $pdo->prepare("SELECT submitted_at FROM submissions WHERE task_id = ? AND student_id = ?");
    $submissionCheck->execute([$task_id, $student_id]);
    $existingSubmission = $submissionCheck->fetch(PDO::FETCH_ASSOC);
    
    // Verificar estado de la tarea según fecha
    $now = new DateTime();
    $dueDate = new DateTime($task['due_date']);
    $taskStatus = null;
    
    if ($existingSubmission) {
        $submittedDate = new DateTime($existingSubmission['submitted_at']);
        if ($submittedDate > $dueDate) {
            $taskStatus = 'late';
        } else {
            $taskStatus = 'submitted';
        }
    } elseif ($now > $dueDate) {
        $taskStatus = 'overdue';
    }

    // 📤 Si se envía el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $task) {
        $text_submission = trim($_POST['text_submission'] ?? '');
        $file = $_FILES['file_submission'] ?? null;
        
        // Validar que al menos haya texto o archivo
        if (empty($text_submission) && (!$file || $file['error'] === UPLOAD_ERR_NO_FILE)) {
            $error = "Debes proporcionar al menos una respuesta escrita o subir un archivo.";
        } else {
            // 🔹 Manejar subida de archivo
            $file_path = null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/submissions/';
                
                // Crear directorio si no existe
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                    $error = "Error al subir el archivo.";
                }
            }
            
            // 🔹 Crear instancia del controlador y guardar la entrega
            if (!isset($error)) {
                $submissionController = new SubmissionController($pdo);
                $result = $submissionController->createSubmission($task_id, $student_id, $file_path, $text_submission);
                
                if ($result === true) {
                    $success = "¡Tarea entregada exitosamente!";
                    // Redirigir después de 2 segundos
                    header("refresh:2;url=submissions.php?course_id=$course_id");
                } else {
                    $error = "Error al guardar la entrega. Puede que ya hayas entregado esta tarea.";
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = "Error al cargar la tarea: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }
    
    .submit-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 10px;
    }
    
    .page-header p {
        color: #718096;
        font-size: 1.1rem;
    }
    
    .task-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 30px;
        border-left: 4px solid #667eea;
    }
    
    .task-header {
        display: flex;
        align-items: start;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .task-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .task-info h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 5px 0;
    }
    
    .task-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #718096;
        font-size: 0.95rem;
    }
    
    .meta-item i {
        color: #667eea;
    }
    
    .task-description {
        padding: 20px;
        background: #f7fafc;
        border-radius: 12px;
        margin-top: 20px;
        line-height: 1.6;
        color: #4a5568;
    }
    
    .submit-form {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 12px;
        font-size: 1rem;
    }
    
    .form-label i {
        color: #667eea;
    }
    
    .form-control {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        font-family: inherit;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }
    
    .file-upload-wrapper {
        position: relative;
    }
    
    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 40px;
        border: 2px dashed #cbd5e0;
        border-radius: 10px;
        background: #f7fafc;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .file-upload-label:hover {
        border-color: #667eea;
        background: #edf2f7;
    }
    
    .file-upload-label i {
        font-size: 2rem;
        color: #667eea;
    }
    
    .file-upload-text {
        text-align: center;
    }
    
    .file-upload-text strong {
        display: block;
        color: #2d3748;
        margin-bottom: 5px;
    }
    
    .file-upload-text small {
        color: #718096;
    }
    
    input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .file-name-display {
        margin-top: 10px;
        padding: 10px;
        background: #edf2f7;
        border-radius: 8px;
        color: #2d3748;
        display: none;
    }
    
    .file-name-display.active {
        display: block;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }
    
    .btn {
        padding: 14px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }
    
    .btn-secondary {
        background: #e2e8f0;
        color: #4a5568;
    }
    
    .btn-secondary:hover {
        background: #cbd5e0;
    }
    
    .alert {
        padding: 16px 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }
    
    .alert i {
        font-size: 1.2rem;
    }
    
    .alert-danger {
        background: #fed7d7;
        color: #c53030;
        border-left: 4px solid #e53e3e;
    }
    
    .alert-warning {
        background: #fefcbf;
        color: #744210;
        border-left: 4px solid #d69e2e;
    }
    
    @media (max-width: 768px) {
        .submit-container {
            padding: 0 15px;
        }
        
        .page-header h1 {
            font-size: 2rem;
        }
        
        .task-card, .submit-form {
            padding: 20px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="submit-container">
    <div class="page-header">
        <h1>Entregar Tarea</h1>
        <p>Completa y envía tu tarea antes de la fecha límite</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?> Redirigiendo...</span>
        </div>
    <?php endif; ?>
    
    <?php if ($taskStatus === 'overdue'): ?>
        <div class="alert alert-danger">
            <i class="fas fa-clock"></i>
            <span><strong>Tarea no entregada:</strong> La fecha límite ha vencido. Esta entrega se marcará como tardía.</span>
        </div>
    <?php elseif ($taskStatus === 'late'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Tarea entregada con retraso:</strong> Entregaste esta tarea después de la fecha límite.</span>
        </div>
    <?php elseif ($taskStatus === 'submitted'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><strong>Tarea entregada a tiempo:</strong> Ya completaste esta tarea dentro del plazo.</span>
        </div>
    <?php endif; ?>

    <?php if ($task): ?>
        <div class="task-card">
            <div class="task-header">
                <div class="task-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="task-info">
                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                    <div class="task-meta">
                        <div class="meta-item">
                            <i class="fas fa-book"></i>
                            <span><?php echo htmlspecialchars($task['course_title']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Vence: <?php echo date('d/m/Y', strtotime($task['due_date'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($task['description'])): ?>
                <div class="task-description">
                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="submit-form">
            <div class="form-section">
                <label class="form-label">
                    <i class="fas fa-pen"></i>
                    Respuesta escrita
                </label>
                <textarea name="text_submission" id="text_submission" class="form-control" placeholder="Escribe tu respuesta aquí..."></textarea>
            </div>

            <div class="form-section">
                <label class="form-label">
                    <i class="fas fa-paperclip"></i>
                    Archivo adjunto
                </label>
                <div class="file-upload-wrapper">
                    <label for="file_submission" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="file-upload-text">
                            <strong>Haz clic para subir un archivo</strong>
                            <small>PDF, DOCX, JPG, PNG, ZIP (Máx. 10MB)</small>
                        </div>
                    </label>
                    <input type="file" name="file_submission" id="file_submission">
                    <div class="file-name-display" id="fileNameDisplay"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Tarea
                </button>
                <a href="tasks.php?course_id=<?php echo htmlspecialchars($course_id); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
// Mostrar nombre del archivo seleccionado
document.getElementById('file_submission').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    const display = document.getElementById('fileNameDisplay');
    
    if (fileName) {
        display.textContent = '📎 ' + fileName;
        display.classList.add('active');
    } else {
        display.classList.remove('active');
    }
});
</script>