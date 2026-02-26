<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$student_id = $_SESSION['user_id'];
$material_id = $data['material_id'] ?? null;
$course_id = $data['course_id'] ?? null;
$completed = $data['completed'] ?? false;

if (!$material_id || !$course_id) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    if ($completed) {
        // Marcar como completado
        $stmt = $pdo->prepare("INSERT INTO material_progress (student_id, course_id, material_id) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE completed_at = NOW()");
        $stmt->execute([$student_id, $course_id, $material_id]);
    } else {
        // Desmarcar
        $stmt = $pdo->prepare("DELETE FROM material_progress 
                               WHERE student_id = ? AND material_id = ?");
        $stmt->execute([$student_id, $material_id]);
    }
    
    // Calcular nuevo progreso
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM material_progress 
                           WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $completed_count = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM materials WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $percentage = $total_count > 0 ? round(($completed_count / $total_count) * 100) : 0;
    
    echo json_encode([
        'success' => true, 
        'completed' => $completed_count,
        'total' => $total_count,
        'percentage' => $percentage
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos']);
}