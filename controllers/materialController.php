<?php
ob_start();
require_once '../config/database.php';

file_put_contents('debug.log', 'Material Controller - Iniciado: ' . date('Y-m-d H:i:s') . "\nPOST: " . print_r($_POST, true) . "\nFILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// CREAR MATERIAL
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $content_type = filter_input(INPUT_POST, 'content_type', FILTER_SANITIZE_STRING);
    $content_url = filter_input(INPUT_POST, 'content_url', FILTER_SANITIZE_URL);
    $content_text = $_POST['content_text'] ?? '';

    if (!$course_id || !$title || !$content_type) {
        header('Location: ../views/teacher/materials.php?error=' . urlencode('Datos inválidos'));
        ob_end_clean();
        exit;
    }

    $file_path = null;

    // Manejar subida de archivos
    if (($content_type === 'file' || $content_type === 'video') && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'mp4', 'avi', 'mov', 'wmv', 'jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_extensions)) {
            header('Location: ../views/teacher/materials.php?error=' . urlencode('Formato de archivo no permitido'));
            ob_end_clean();
            exit;
        }

        $upload_dir = '../assets/uploads/materials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($file['name']);
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            header('Location: ../views/teacher/materials.php?error=' . urlencode('Error al subir el archivo'));
            ob_end_clean();
            exit;
        }

        // Guardar ruta relativa
        $file_path = 'assets/uploads/materials/' . $file_name;
    }

    // Guardar en la base de datos
    try {
        if ($content_type === 'text') {
            $stmt = $pdo->prepare("INSERT INTO materials (course_id, title, description, content_type, content_text, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$course_id, $title, $description, $content_type, $content_text]);
        } elseif ($content_type === 'youtube' || $content_type === 'link') {
            $stmt = $pdo->prepare("INSERT INTO materials (course_id, title, description, content_type, content_url, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$course_id, $title, $description, $content_type, $content_url]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO materials (course_id, title, description, content_type, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$course_id, $title, $description, $content_type, $file_path]);
        }

        header('Location: ../views/teacher/materials.php?success=' . urlencode('Material subido con éxito'));
    } catch (PDOException $e) {
        header('Location: ../views/teacher/materials.php?error=' . urlencode('Error al guardar: ' . $e->getMessage()));
    }
    ob_end_clean();
    exit;
}

// ACTUALIZAR MATERIAL
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $content_type = filter_input(INPUT_POST, 'content_type', FILTER_SANITIZE_STRING);
    $content_url = filter_input(INPUT_POST, 'content_url', FILTER_SANITIZE_URL);

    if ($id && $course_id && $title && $content_type) {
        try {
            $stmt = $pdo->prepare("UPDATE materials SET course_id = ?, title = ?, description = ?, content_type = ?, content_url = ? WHERE id = ?");
            $stmt->execute([$course_id, $title, $description, $content_type, $content_url, $id]);
            header('Location: ../views/teacher/materials.php?success=' . urlencode('Material actualizado'));
        } catch (PDOException $e) {
            header('Location: ../views/teacher/materials.php?error=' . urlencode('Error al actualizar: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../views/teacher/materials.php?error=' . urlencode('Datos inválidos'));
    }
    ob_end_clean();
    exit;
}

// ELIMINAR MATERIAL
if ($action === 'delete') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        try {
            // Obtener el archivo para eliminarlo
            $stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = ?");
            $stmt->execute([$id]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);

            // Eliminar de la base de datos
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$id]);

            // Eliminar archivo físico si existe
            if ($material && $material['file_path'] && file_exists('../' . $material['file_path'])) {
                unlink('../' . $material['file_path']);
            }

            header('Location: ../views/teacher/materials.php?success=' . urlencode('Material eliminado'));
        } catch (PDOException $e) {
            header('Location: ../views/teacher/materials.php?error=' . urlencode('Error al eliminar: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../views/teacher/materials.php?error=' . urlencode('ID inválido'));
    }
    ob_end_clean();
    exit;
}

ob_end_flush();
?>