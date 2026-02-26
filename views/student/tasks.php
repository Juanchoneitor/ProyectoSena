<?php
session_start();
require_once '../../config/database.php';
require_once '../../models/TaskModel.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar que el usuario esté logueado como estudiante
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

$taskModel = new TaskModel($pdo);

// 📌 Si se pasa un course_id → mostrar tareas de ese curso (solo pendientes)
if ($course_id > 0) {
    // Consulta modificada para excluir tareas ya entregadas
    $sql = "SELECT t.id, t.title, t.description, t.due_date, c.title AS course_title, t.course_id
            FROM tasks t
            INNER JOIN courses c ON t.course_id = c.id
            INNER JOIN enrollments cs ON cs.course_id = c.id
            LEFT JOIN submissions s ON s.task_id = t.id AND s.student_id = ?
            WHERE cs.student_id = ? AND c.id = ? AND s.id IS NULL
            ORDER BY t.due_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $student_id, $course_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    $pageTitle = "Mis Tareas - " . htmlspecialchars($course['title']);
} else {
    // 📌 Si no hay course_id → mostrar todas las tareas pendientes del estudiante
    $sql = "SELECT t.id, t.title, t.description, t.due_date, c.title AS course_title, t.course_id
            FROM tasks t
            INNER JOIN courses c ON t.course_id = c.id
            INNER JOIN enrollments cs ON cs.course_id = c.id
            LEFT JOIN submissions s ON s.task_id = t.id AND s.student_id = ?
            WHERE cs.student_id = ? AND s.id IS NULL
            ORDER BY t.due_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $student_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pageTitle = "Mis Tareas Pendientes (Todos los cursos)";
}

// Función para determinar urgencia
function getTaskUrgency($due_date) {
    $today = new DateTime();
    $dueDate = new DateTime($due_date);
    $diff = $today->diff($dueDate);
    
    if ($dueDate < $today) {
        return 'overdue';
    } elseif ($diff->days <= 2) {
        return 'urgent';
    } elseif ($diff->days <= 7) {
        return 'warning';
    }
    return 'normal';
}
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        min-height: 100vh;
    }
    
    .tasks-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .page-title-section {
        text-align: center;
        margin-bottom: 45px;
    }
    
    .page-title-section h1 {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        letter-spacing: -0.5px;
    }
    
    .page-subtitle {
        color: #64748b;
        font-size: 1.05rem;
        font-style: italic;
        margin-bottom: 25px;
    }
    
    .filter-section {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .filter-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 26px;
        background: white;
        color: #475569;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    
    .filter-button:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }
    
    .tasks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 28px;
        margin-bottom: 50px;
    }
    
    .task-item {
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(226, 232, 240, 0.8);
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    .task-item:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border-color: #667eea;
    }
    
    .task-header {
        padding: 24px 24px 18px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 1px solid #f1f5f9;
    }
    
    .task-badge {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 14px;
    }
    
    .task-badge.overdue {
        background: linear-gradient(135deg, #fed7d7, #fbb6ce);
        color: #c53030;
    }
    
    .task-badge.urgent {
        background: linear-gradient(135deg, #feebc8, #fbd38d);
        color: #c05621;
    }
    
    .task-badge.warning {
        background: linear-gradient(135deg, #fefcbf, #faf089);
        color: #744210;
    }
    
    .task-badge.normal {
        background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
        color: #22543d;
    }
    
    .task-name {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 10px 0;
        line-height: 1.4;
    }
    
    .task-course-name {
        color: #64748b;
        font-size: 0.92rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .task-content {
        padding: 20px 24px;
        flex-grow: 1;
    }
    
    .task-desc {
        color: #475569;
        line-height: 1.7;
        font-size: 0.95rem;
        margin: 0;
    }
    
    .task-actions {
        padding: 18px 24px;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f1f5f9;
    }
    
    .task-deadline {
        display: flex;
        align-items: center;
        gap: 7px;
        font-weight: 700;
        font-size: 0.87rem;
        color: #dc2626;
    }
    
    .task-deadline svg {
        width: 16px;
        height: 16px;
    }
    
    .submit-button {
        padding: 11px 22px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        transition: all 0.3s ease;
        border: none;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .submit-button:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        color: white;
    }
    
    .no-tasks-message {
        text-align: center;
        padding: 90px 30px;
        background: white;
        border-radius: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    
    .no-tasks-icon {
        font-size: 5rem;
        margin-bottom: 24px;
    }
    
    .no-tasks-message h3 {
        font-size: 1.6rem;
        color: #1e293b;
        margin-bottom: 12px;
        font-weight: 700;
    }
    
    .no-tasks-message p {
        color: #64748b;
        font-size: 1.05rem;
    }
    
    .bottom-navigation {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    
    .nav-button {
        padding: 13px 30px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-size: 0.95rem;
    }
    
    .nav-button-back {
        background: #e2e8f0;
        color: #475569;
    }
    
    .nav-button-back:hover {
        background: #cbd5e0;
        color: #334155;
        transform: translateY(-2px);
    }
    
    .nav-button-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .nav-button-primary:hover {
        background: linear-gradient(135deg, #5a67d8, #6b46c1);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35);
        color: white;
    }
    
    @media (max-width: 768px) {
        .tasks-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .page-title-section h1 {
            font-size: 2rem;
        }
        
        .bottom-navigation {
            flex-direction: column;
        }
        
        .nav-button {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="tasks-wrapper">
    <div class="page-title-section">
        <h1><?php echo $pageTitle; ?></h1>
        <?php if ($course_id == 0): ?>
            <p class="page-subtitle">Estás viendo todas tus tareas pendientes de todos los cursos.</p>
        <?php else: ?>
            <p class="page-subtitle">Estás viendo las tareas del curso seleccionado.</p>
        <?php endif; ?>
    </div>

    <?php if ($course_id > 0): ?>
        <div class="filter-section">
            <a href="tasks.php" class="filter-button">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M2 5h12M2 8h9M2 11h6"/>
                </svg>
                Ver todas las tareas
            </a>
        </div>
    <?php endif; ?>

    <?php if (count($tasks) > 0): 
        $urgencyLabels = [
            'overdue' => 'Vencida',
            'urgent' => 'Urgente',
            'warning' => 'Próxima',
            'normal' => 'Normal'
        ];
    ?>
        <div class="tasks-grid">
            <?php foreach ($tasks as $task): 
                $urgency = getTaskUrgency($task['due_date']);
            ?>
                <div class="task-item">
                    <div class="task-header">
                        <span class="task-badge <?php echo $urgency; ?>">
                            <?php echo $urgencyLabels[$urgency]; ?>
                        </span>
                        <h3 class="task-name"><?php echo htmlspecialchars($task['title']); ?></h3>
                        <div class="task-course-name">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0L0 4l8 4 8-4L8 0zM0 8l8 4 8-4M0 12l8 4 8-4"/>
                            </svg>
                            <span><?php echo htmlspecialchars($task['course_title']); ?></span>
                        </div>
                    </div>
                    <div class="task-content">
                        <p class="task-desc"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    </div>
                    <div class="task-actions">
                        <div class="task-deadline">
                            <svg viewBox="0 0 16 16" fill="currentColor">
                                <path d="M11 1v1h3a1 1 0 011 1v11a1 1 0 01-1 1H2a1 1 0 01-1-1V3a1 1 0 011-1h3V1h2v1h4V1h2zM5 8H3v2h2V8zm0 3H3v2h2v-2zm3-3H6v2h2V8zm0 3H6v2h2v-2zm3-3H9v2h2V8z"/>
                            </svg>
                            <span><?php echo date('d/m/Y', strtotime($task['due_date'])); ?></span>
                        </div>
                        <a href="submit.php?task_id=<?php echo htmlspecialchars($task['id']); ?>&course_id=<?php echo htmlspecialchars($task['course_id']); ?>" 
                           class="submit-button">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M15 1L0 8.5l5.5 2L13 3 7.5 11 10 16z"/>
                            </svg>
                            Entregar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-tasks-message">
            <div class="no-tasks-icon">🎉</div>
            <h3>No tienes tareas pendientes</h3>
            <p>¡Excelente trabajo! Has completado todas tus tareas</p>
        </div>
    <?php endif; ?>

    <div class="bottom-navigation">
        <a href="myCourses.php" class="nav-button nav-button-back">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0L16 8l-1.4 1.4L8 2.8l-6.6 6.6L0 8z" transform="rotate(270 8 8)"/>
            </svg>
            Volver a Mis Cursos
        </a>
        <?php if ($course_id > 0): ?>
            <a href="viewCourse.php?id=<?php echo htmlspecialchars($course_id); ?>" class="nav-button nav-button-primary">Ver Detalles del Curso</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>