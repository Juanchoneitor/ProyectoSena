<?php
session_start();
require_once '../config/database.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// Asegurar CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//
// ─────────────────────────────────────────────────────────────
//   CREAR CURSO
// ─────────────────────────────────────────────────────────────
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../views/teacher/courses.php?error=Token CSRF inválido");
        exit;
    }

    $title = filter_input(INPUT_POST, 'title');
    $description = filter_input(INPUT_POST, 'description');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $teacher_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO courses (title, description, category_id, teacher_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $category_id, $teacher_id]);

        header("Location: ./views/teacher/courses.php?success=Curso creado correctamente");
        exit;

    } catch (PDOException $e) {
        header("Location: ../views/teacher/courses.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

//
// ─────────────────────────────────────────────────────────────
//   EDITAR CURSO
// ─────────────────────────────────────────────────────────────
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../views/teacher/courses.php?error=Token CSRF inválido");
        exit;
    }

    $id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title');
    $description = filter_input(INPUT_POST, 'description');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $teacher_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("
            UPDATE courses 
            SET title=?, description=?, category_id=? 
            WHERE id=? AND teacher_id=?
        ");
        $stmt->execute([$title, $description, $category_id, $id, $teacher_id]);

        header("Location: ../views/teacher/courses.php?success=Curso actualizado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../views/teacher/courses.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

//
// ─────────────────────────────────────────────────────────────
//   ELIMINAR CURSO
// ─────────────────────────────────────────────────────────────
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../views/teacher/courses.php?error=Token CSRF inválido");
        exit;
    }

    $course_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $teacher_id = $_SESSION['user_id'];

    if (!$course_id || $_SESSION['role'] != 'teacher') {
        header("Location: ../views/teacher/courses.php?error=No autorizado");
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id=? AND teacher_id=?");
        $stmt->execute([$course_id, $teacher_id]);

        header("Location: ../views/teacher/courses.php?success=Curso eliminado correctamente");
        exit;

    } catch (PDOException $e) {
        header("Location: ../views/teacher/courses.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

//
// ─────────────────────────────────────────────────────────────
//   INSCRIBIRSE A CURSO (NUEVO + CORREGIDO)
// ─────────────────────────────────────────────────────────────
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'enroll') {

    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../views/student/courses.php?error=Token CSRF inválido");
        exit;
    }

    // Debe ser estudiante
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
        header("Location: ../views/auth/login.php?error=Debes iniciar sesión como estudiante");
        exit;
    }

    $student_id = $_SESSION['user_id'];
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    if (!$course_id) {
        header("Location: ../views/student/courses.php?error=Curso inválido");
        exit;
    }

    try {
        // Verificar si ya está inscrito
        $check = $pdo->prepare("SELECT id FROM enrollments WHERE course_id=? AND student_id=?");
        $check->execute([$course_id, $student_id]);

        if ($check->rowCount() > 0) {
            header("Location: ../views/student/courses.php?error=Ya estás inscrito en este curso");
            exit;
        }

        // Inscribir
        $stmt = $pdo->prepare("INSERT INTO enrollments (course_id, student_id) VALUES (?, ?)");
        $stmt->execute([$course_id, $student_id]);

        header("Location: ../views/student/courses.php?success=Inscripción exitosa");
        exit;

    } catch (PDOException $e) {
        header("Location: ../views/student/courses.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

//
// ─────────────────────────────────────────────────────────────
//   ACCIÓN INVÁLIDA
// ─────────────────────────────────────────────────────────────
//
header("Location: ../views/teacher/courses.php?error=Acción no válida");
exit;

?>
