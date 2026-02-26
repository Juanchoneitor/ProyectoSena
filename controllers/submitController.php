```php
<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
        header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
        exit;
    }

    $student_id = $_SESSION['user_id'];
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $text_submission = filter_input(INPUT_POST, 'text_submission', FILTER_SANITIZE_STRING) ?? null;
    $file_submission = $_FILES['file_submission'] ?? null;

    if ($task_id && $course_id) {
        try {
            $stmt = $pdo->prepare("SELECT course_id FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task || $task['course_id'] != $course_id) {
                throw new Exception("Tarea no válida.");
            }

            $file_path = null;
            if ($file_submission && $file_submission['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!in_array($file_submission['type'], $allowed_types)) {
                    throw new Exception("Solo se permiten archivos PDF o Word.");
                }

                $upload_dir = '../../uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_name = uniqid() . '_' . basename($file_submission['name']);
                $file_path = $upload_dir . $file_name;
                if (!move_uploaded_file($file_submission['tmp_name'], $file_path)) {
                    throw new Exception("Error al subir el archivo.");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO submissions (task_id, student_id, file_path, text_submission, submitted_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), text_submission = VALUES(text_submission), submitted_at = VALUES(submitted_at)");
            $stmt->execute([$task_id, $student_id, $file_path, $text_submission]);
            header('Location: ../student/tasks.php?course_id=' . $course_id . '&success=Entrega enviada con éxito');
        } catch (PDOException $e) {
            file_put_contents('debug.log', "Error en submitController.php (PDO): " . $e->getMessage() . ", Task ID: $task_id, Course ID: $course_id, Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            header('Location: ../student/tasks.php?course_id=' . $course_id . '&error=Error al guardar la entrega');
        } catch (Exception $e) {
            header('Location: ../student/tasks.php?course_id=' . $course_id . '&error=' . urlencode($e->getMessage()));
        }
    } else {
        header('Location: ../student/tasks.php?course_id=' . $course_id . '&error=Datos inválidos');
    }
} else {
    header('Location: ../student/tasks.php?course_id=' . $course_id . '&error=Método no permitido');
}
exit;
?>