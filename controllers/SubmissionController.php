<?php
class SubmissionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 🔹 Registrar una nueva entrega
    public function createSubmission($task_id, $student_id, $file_path = null, $text_submission = null) {
        try {
            // Evitar duplicados (ya entregada)
            $check = $this->pdo->prepare("SELECT id FROM submissions WHERE task_id = ? AND student_id = ?");
            $check->execute([$task_id, $student_id]);
            if ($check->fetch()) {
                throw new Exception("Ya realizaste una entrega para esta tarea.");
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO submissions (task_id, student_id, file_path, text_submission, submitted_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$task_id, $student_id, $file_path, $text_submission]);
            return true;

        } catch (Exception $e) {
            error_log("❌ Error al crear entrega: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 Obtener todas las entregas de un estudiante
    public function getSubmissionsByStudent($student_id) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, t.title AS task_title, c.title AS course_title
            FROM submissions s
            JOIN tasks t ON s.task_id = t.id
            JOIN courses c ON t.course_id = c.id
            WHERE s.student_id = ?
            ORDER BY s.submitted_at DESC
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Obtener entrega específica
    public function getSubmission($task_id, $student_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM submissions
            WHERE task_id = ? AND student_id = ?
        ");
        $stmt->execute([$task_id, $student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
