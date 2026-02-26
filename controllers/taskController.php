<?php
ob_start(); // Inicia el buffer para evitar salida prematura

require_once '../config/database.php';

// Depuración detallada
file_put_contents('debug.log', 'Iniciado: ' . date('Y-m-d H:i:s') . "\nPOST: " . print_r($_POST, true) . "\nGET: " . print_r($_GET, true) . "\nSESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generar token si no existe
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        file_put_contents('debug.log', 'Nuevo CSRF generado: ' . $_SESSION['csrf_token'] . "\n", FILE_APPEND);
    }

    // Temporalmente desactivar validación CSRF para depuración
    file_put_contents('debug.log', 'CSRF Validación desactivada temporalmente. Session=' . $_SESSION['csrf_token'] . ', Post=' . ($_POST['csrf_token'] ?? 'N/A') . "\n", FILE_APPEND);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: null;
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);

    if ($title && $description && $due_date) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (course_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $title, $description, $due_date]);
            $success = "Tarea creada con éxito";
            header('Location: ../views/teacher/tasks.php?success=' . urlencode($success));
        } catch (PDOException $e) {
            $error = "Error al crear tarea: " . urlencode($e->getMessage());
            header('Location: ../views/teacher/tasks.php?error=' . $error);
        }
    } else {
        $error = "Datos inválidos";
        header('Location: ../views/teacher/tasks.php?error=' . $error);
    }
    ob_end_clean();
    exit;
} elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: null;
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);

    if ($id && $title && $description && $due_date) {
        try {
            $stmt = $pdo->prepare("UPDATE tasks SET course_id = ?, title = ?, description = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$course_id, $title, $description, $due_date, $id]);
            $success = "Tarea actualizada con éxito";
            header('Location: ../views/teacher/tasks.php?success=' . urlencode($success));
        } catch (PDOException $e) {
            $error = "Error al actualizar tarea: " . urlencode($e->getMessage());
            header('Location: ../views/teacher/tasks.php?error=' . $error);
        }
    } else {
        $error = "Datos inválidos";
        header('Location: ../views/teacher/tasks.php?error=' . $error);
    }
    ob_end_clean();
    exit;
} elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CORRECCIÓN: Cambiar INPUT_GET por INPUT_POST
    $task_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    file_put_contents('debug.log', 'Delete Action - task_id recibido: ' . ($task_id ?? 'NULL') . "\n", FILE_APPEND);
    
    if ($task_id) {
        try {
            // Primero eliminar las entregas asociadas
            $stmt = $pdo->prepare("SELECT file_path FROM submissions WHERE task_id = ?");
            $stmt->execute([$task_id]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Eliminar archivos físicos de las entregas
            foreach ($submissions as $submission) {
                if (!empty($submission['file_path'])) {
                    $file_physical = __DIR__ . '/../' . $submission['file_path'];
                    if (file_exists($file_physical)) {
                        unlink($file_physical);
                        file_put_contents('debug.log', 'Archivo eliminado: ' . $file_physical . "\n", FILE_APPEND);
                    }
                }
            }
            
            // Eliminar entregas de la BD
            $stmt = $pdo->prepare("DELETE FROM submissions WHERE task_id = ?");
            $stmt->execute([$task_id]);
            
            // Eliminar la tarea
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            
            $success = "Tarea y entregas eliminadas con éxito";
            file_put_contents('debug.log', 'Tarea eliminada exitosamente: ID ' . $task_id . "\n", FILE_APPEND);
            header('Location: ../views/teacher/tasks.php?success=' . urlencode($success));
        } catch (PDOException $e) {
            $error = "Error al eliminar tarea: " . urlencode($e->getMessage());
            file_put_contents('debug.log', 'Error en delete: ' . $e->getMessage() . "\n", FILE_APPEND);
            header('Location: ../views/teacher/tasks.php?error=' . $error);
        }
    } else {
        $error = "ID inválido o no recibido";
        file_put_contents('debug.log', 'Error: ID inválido en delete\n', FILE_APPEND);
        header('Location: ../views/teacher/tasks.php?error=' . urlencode($error));
    }
    ob_end_clean();
    exit;
} elseif ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $student_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    $allowed = ['pdf', 'doc', 'docx'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $allowed)) {
        $error = "Formato de archivo no permitido (solo PDF, DOC, DOCX)";
        header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . urlencode($error));
        ob_end_clean();
        exit;
    }

    // CORRECCIÓN: Usar ruta física absoluta para guardar, pero ruta relativa para la BD
    $upload_dir_physical = __DIR__ . '/../uploads/submissions/';
    if (!file_exists($upload_dir_physical)) {
        mkdir($upload_dir_physical, 0777, true);
    }
    
    $new_filename = time() . '_' . basename($file['name']);
    $file_path_physical = $upload_dir_physical . $new_filename;
    $file_path_db = 'uploads/submissions/' . $new_filename; // RUTA PARA LA BASE DE DATOS
    
    if (move_uploaded_file($file['tmp_name'], $file_path_physical)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO submissions (task_id, student_id, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$task_id, $student_id, $file_path_db]);
            $success = "Entrega enviada con éxito";
            header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&success=' . urlencode($success));
        } catch (PDOException $e) {
            $error = "Error al guardar entrega: " . urlencode($e->getMessage());
            header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . $error);
        }
    } else {
        $error = "Error al subir el archivo";
        header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . $error);
    }
    ob_end_clean();
    exit;
} elseif ($action === 'replace' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $student_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    if ($submission_id && $task_id && $student_id) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $allowed)) {
            $error = "Formato de archivo no permitido (solo PDF, DOC, DOCX)";
            header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . urlencode($error));
            ob_end_clean();
            exit;
        }

        // CORRECCIÓN: Usar ruta física absoluta para guardar, pero ruta relativa para la BD
        $upload_dir_physical = __DIR__ . '/../uploads/submissions/';
        if (!file_exists($upload_dir_physical)) {
            mkdir($upload_dir_physical, 0777, true);
        }
        
        $new_filename = time() . '_' . basename($file['name']);
        $file_path_physical = $upload_dir_physical . $new_filename;
        $file_path_db = 'uploads/submissions/' . $new_filename; // RUTA PARA LA BASE DE DATOS
        
        if (move_uploaded_file($file['tmp_name'], $file_path_physical)) {
            try {
                // Obtener la ruta del archivo antiguo
                $stmt = $pdo->prepare("SELECT file_path FROM submissions WHERE id = ? AND student_id = ?");
                $stmt->execute([$submission_id, $student_id]);
                $old_submission = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($old_submission) {
                    $old_file_path = $old_submission['file_path'];
                    // Construir ruta física del archivo antiguo
                    $old_file_physical = __DIR__ . '/../' . $old_file_path;
                    if (file_exists($old_file_physical)) {
                        unlink($old_file_physical); // Eliminar el archivo antiguo
                    }
                }

                // Actualizar con el nuevo archivo
                $stmt = $pdo->prepare("UPDATE submissions SET file_path = ? WHERE id = ? AND student_id = ?");
                $stmt->execute([$file_path_db, $submission_id, $student_id]);
                $success = "Entrega reemplazada con éxito";
                header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&success=' . urlencode($success));
            } catch (PDOException $e) {
                $error = "Error al reemplazar entrega: " . urlencode($e->getMessage());
                header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . $error);
            }
        } else {
            $error = "Error al subir el nuevo archivo";
            header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . $error);
        }
    } else {
        $error = "Datos inválidos";
        header('Location: ../views/student/tasks.php?course_id=' . ($course_id ?? '') . '&error=' . $error);
    }
    ob_end_clean();
    exit;
} elseif ($action === 'delete_submission') {
    $submission_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $student_id = $_SESSION['user_id'];

    if ($submission_id && $student_id) {
        try {
            $stmt = $pdo->prepare("SELECT file_path FROM submissions WHERE id = ? AND student_id = ?");
            $stmt->execute([$submission_id, $student_id]);
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($submission) {
                $file_path = $submission['file_path'];
                $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = ? AND student_id = ?");
                $stmt->execute([$submission_id, $student_id]);
                // Construir ruta física del archivo
                $file_physical = __DIR__ . '/../' . $file_path;
                if (file_exists($file_physical)) {
                    unlink($file_physical); // Eliminar el archivo
                }
                $success = "Entrega eliminada con éxito";
                header('Location: ../views/student/tasks.php?success=' . urlencode($success));
            } else {
                $error = "Entrega no encontrada o no autorizada";
                header('Location: ../views/student/tasks.php?error=' . urlencode($error));
            }
        } catch (PDOException $e) {
            $error = "Error al eliminar entrega: " . urlencode($e->getMessage());
            header('Location: ../views/student/tasks.php?error=' . $error);
        }
    } else {
        $error = "ID inválido";
        header('Location: ../views/student/tasks.php?error=' . $error);
    }
    ob_end_clean();
    exit;
} elseif ($action === 'grade' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_VALIDATE_FLOAT);
    $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);

    // Log para depuración
    file_put_contents('debug.log', 'Grade Action - submission_id: ' . $submission_id . ', grade: ' . $grade . ', feedback: ' . $feedback . "\n", FILE_APPEND);

    if ($submission_id !== false && $grade !== false && $grade !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE submissions SET grade = ?, feedback = ? WHERE id = ?");
            $stmt->execute([$grade, $feedback, $submission_id]);
            $success = "Calificación guardada";
            header('Location: ../views/teacher/tasks.php?success=' . urlencode($success));
        } catch (PDOException $e) {
            $error = "Error al guardar calificación: " . urlencode($e->getMessage());
            file_put_contents('debug.log', 'Error en grade: ' . $e->getMessage() . "\n", FILE_APPEND);
            header('Location: ../views/teacher/tasks.php?error=' . $error);
        }
    } else {
        $error = "Datos inválidos - submission_id: " . ($submission_id !== false ? $submission_id : 'inválido') . ", grade: " . ($grade !== false ? $grade : 'inválido');
        file_put_contents('debug.log', 'Validación fallida: ' . $error . "\n", FILE_APPEND);
        header('Location: ../views/teacher/tasks.php?error=' . urlencode($error));
    }
    ob_end_clean();
    exit;
}

ob_end_flush();
?>