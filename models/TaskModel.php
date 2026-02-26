<?php
class TaskModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 🔹 Obtener todas las tareas de un estudiante (todos los cursos)
    public function getTasksByStudent($student_id) {
        $sql = "SELECT t.id, t.title, t.description, t.due_date, 
                       c.id AS course_id, c.title AS course_title
                FROM tasks t
                INNER JOIN courses c ON t.course_id = c.id
                INNER JOIN enrollments cs ON cs.course_id = c.id
                WHERE cs.student_id = ?
                ORDER BY t.due_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Obtener tareas de un curso específico
    public function getTasksByCourse($course_id, $student_id) {
        $sql = "SELECT t.id, t.title, t.description, t.due_date, 
                       c.id AS course_id, c.title AS course_title
                FROM tasks t
                INNER JOIN courses c ON t.course_id = c.id
                INNER JOIN enrollments cs ON cs.course_id = c.id
                WHERE cs.student_id = ? AND c.id = ?
                ORDER BY t.due_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$student_id, $course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Obtener una tarea específica
    public function getTaskById($task_id) {
        $sql = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$task_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 Crear nueva tarea
    public function createTask($course_id, $title, $description, $due_date) {
        $sql = "INSERT INTO tasks (course_id, title, description, due_date) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$course_id, $title, $description, $due_date]);
    }

    // 🔹 Actualizar tarea
    public function updateTask($task_id, $title, $description, $due_date) {
        $sql = "UPDATE tasks SET title = ?, description = ?, due_date = ? 
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$title, $description, $due_date, $task_id]);
    }

    // 🔹 Eliminar tarea
    public function deleteTask($task_id) {
        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$task_id]);
    }

        // 🔹 Obtener tareas entregadas por un estudiante
    public function getCompletedTasksByStudent($student_id) {
        $sql = "SELECT 
                    t.id AS task_id,
                    t.title AS task_title,
                    t.description,
                    t.due_date,
                    c.title AS course_title,
                    s.file_path,
                    s.text_submission,
                    s.submitted_at,
                    s.grade,
                    s.feedback
                FROM submissions s
                INNER JOIN tasks t ON s.task_id = t.id
                INNER JOIN courses c ON t.course_id = c.id
                WHERE s.student_id = ?
                ORDER BY s.submitted_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
?>
