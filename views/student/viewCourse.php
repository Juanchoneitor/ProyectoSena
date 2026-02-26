<?php
ob_start();
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
    ob_end_clean();
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : null;
$material_id = isset($_GET['material_id']) ? filter_input(INPUT_GET, 'material_id', FILTER_VALIDATE_INT) : null;

if (!$course_id) {
    header('Location: ../student/myCourses.php?error=Curso no especificado');
    ob_end_clean();
    exit;
}

// Verificar inscripción
try {
    $stmt = $pdo->prepare("SELECT c.title, c.description, u.username as teacher_name 
                           FROM enrollments e 
                           JOIN courses c ON e.course_id = c.id 
                           JOIN users u ON c.teacher_id = u.id 
                           WHERE e.student_id = ? AND e.course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        header('Location: ../student/myCourses.php?error=No estás inscrito en este curso');
        ob_end_clean();
        exit;
    }

    // Obtener materiales con detalles
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE course_id = ? ORDER BY uploaded_at ASC");
    $stmt->execute([$course_id]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si no se especificó un material, seleccionar el primero
    if (!$material_id && !empty($materials)) {
        $material_id = $materials[0]['id'];
    }

    // Obtener material actual
    $current_material = null;
    if ($material_id) {
        $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ? AND course_id = ?");
        $stmt->execute([$material_id, $course_id]);
        $current_material = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener progreso del estudiante (materiales vistos)
    $stmt = $pdo->prepare("SELECT material_id FROM material_progress WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $completed_materials = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Contar tareas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $tasks_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    $error = "Error al cargar el curso: " . $e->getMessage();
}

// Función para obtener extensión del archivo
function getFileExtension($file_path) {
    return strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
}

// Función para obtener ícono según tipo de contenido
function getContentIcon($content_type, $file_path = '') {
    $icons = [
        'video' => ['icon' => 'bi-camera-video-fill', 'color' => 'danger'],
        'youtube' => ['icon' => 'bi-youtube', 'color' => 'danger'],
        'text' => ['icon' => 'bi-file-text-fill', 'color' => 'info'],
        'link' => ['icon' => 'bi-link-45deg', 'color' => 'primary'],
        'file' => ['icon' => 'bi-file-earmark-pdf-fill', 'color' => 'danger']
    ];
    
    if ($content_type === 'file' && !empty($file_path)) {
        $ext = getFileExtension($file_path);
        if (in_array($ext, ['doc', 'docx'])) {
            return ['icon' => 'bi-file-word-fill', 'color' => 'primary'];
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            return ['icon' => 'bi-file-excel-fill', 'color' => 'success'];
        } elseif (in_array($ext, ['ppt', 'pptx'])) {
            return ['icon' => 'bi-file-powerpoint-fill', 'color' => 'warning'];
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            return ['icon' => 'bi-file-image-fill', 'color' => 'info'];
        }
    }
    
    return $icons[$content_type] ?? $icons['file'];
}

// Función para calcular duración estimada
function getEstimatedDuration($content_type) {
    $durations = [
        'video' => rand(3, 15),
        'youtube' => rand(5, 20),
        'text' => rand(2, 10),
        'file' => rand(5, 15),
        'link' => rand(3, 10)
    ];
    return $durations[$content_type] ?? 5;
}

// Función para renderizar el contenido del material
function renderMaterialContent($material) {
    if (!$material) {
        return '<div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Selecciona un material para comenzar</p>
                </div>';
    }

    $content = '';
    
    switch($material['content_type']) {
        case 'video':
            if (!empty($material['file_path'])) {
                $content = '<video controls class="w-100" style="max-height: 500px; background: #000;">
                                <source src="../../' . htmlspecialchars($material['file_path']) . '" type="video/mp4">
                                Tu navegador no soporta el elemento de video.
                            </video>';
            }
            break;
            
        case 'youtube':
            if (!empty($material['youtube_url'])) {
                $youtube_id = '';
                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?\/]+)/', $material['youtube_url'], $matches);
                if (isset($matches[1])) {
                    $youtube_id = $matches[1];
                }
                $content = '<div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/' . $youtube_id . '" allowfullscreen></iframe>
                            </div>';
            }
            break;
            
        case 'text':
            $content = '<div class="text-content p-4">
                            <div style="line-height: 1.8; font-size: 1.1rem;">
                                ' . nl2br(htmlspecialchars($material['text_content'] ?? '')) . '
                            </div>
                        </div>';
            break;
            
        case 'file':
            $ext = getFileExtension($material['file_path'] ?? '');
            if (in_array($ext, ['pdf'])) {
                $content = '<iframe src="../../' . htmlspecialchars($material['file_path']) . '" class="w-100" style="height: 600px; border: none;"></iframe>';
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $content = '<img src="../../' . htmlspecialchars($material['file_path']) . '" class="img-fluid" alt="Material">';
            } else {
                $icon_data = getContentIcon('file', $material['file_path']);
                $content = '<div class="text-center py-5">
                                <i class="bi ' . $icon_data['icon'] . '" style="font-size: 5rem; color: #667eea;"></i>
                                <h4 class="mt-3">' . htmlspecialchars($material['title']) . '</h4>
                                <a href="../../' . htmlspecialchars($material['file_path']) . '" download class="btn btn-primary mt-3">
                                    <i class="bi bi-download"></i> Descargar archivo
                                </a>
                            </div>';
            }
            break;
            
        case 'link':
            $content = '<div class="text-center py-5">
                            <i class="bi bi-link-45deg" style="font-size: 5rem; color: #667eea;"></i>
                            <h4 class="mt-3">' . htmlspecialchars($material['title']) . '</h4>
                            <a href="' . htmlspecialchars($material['external_link']) . '" target="_blank" class="btn btn-primary mt-3">
                                <i class="bi bi-box-arrow-up-right"></i> Abrir enlace externo
                            </a>
                        </div>';
            break;
    }
    
    return $content;
}
?>

<style>
body {
    background-color: #f8f9fa;
}

.course-viewer-container {
    display: flex;
    height: calc(100vh - 60px);
    margin-top: 0;
}

.main-viewer {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
    overflow-y: auto;
}

.viewer-header {
    background: #2d2f31;
    color: white;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #1c1d1f;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 10;
}

.viewer-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 400;
}

.viewer-header .back-link {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.viewer-header .back-link:hover {
    color: #a435f0;
}

.content-viewer {
    background: #000;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.content-details-section {
    padding: 2rem 1.5rem;
}

.content-title-section {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
}

.content-title-section h2 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.content-meta {
    display: flex;
    gap: 2rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.tabs-section {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.nav-tabs-custom {
    border: none;
}

.nav-tabs-custom .nav-link {
    border: none;
    color: #6c757d;
    padding: 1rem 1.5rem;
    border-bottom: 3px solid transparent;
    font-weight: 500;
}

.nav-tabs-custom .nav-link:hover {
    color: #212529;
    border-bottom-color: #dee2e6;
}

.nav-tabs-custom .nav-link.active {
    color: #212529;
    border-bottom-color: #2d2f31;
    background: none;
}

.course-description-text {
    line-height: 1.8;
    color: #495057;
}

.teacher-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 1.5rem;
}

.teacher-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

/* Sidebar de contenido */
.content-sidebar {
    width: 400px;
    background: #fff;
    border-left: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 60px);
}

.sidebar-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 10;
}

.sidebar-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: 1rem;
}

.progress-info {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.materials-list {
    overflow-y: auto;
    flex: 1;
}

.material-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
}

.material-item:hover {
    background-color: #f8f9fa;
}

.material-item.active {
    background-color: #e7f1ff;
    border-left: 4px solid #667eea;
}

.material-checkbox {
    width: 24px;
    height: 24px;
    min-width: 24px;
    border: 2px solid #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.material-item.completed .material-checkbox {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.material-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 1.1rem;
}

.material-info {
    flex: 1;
    min-width: 0;
}

.material-number {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.material-title {
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.material-duration {
    font-size: 0.8rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.2rem;
}

.toggle-sidebar-btn {
    position: fixed;
    right: 400px;
    top: 50%;
    transform: translateY(-50%);
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 4px 0 0 4px;
    padding: 0.5rem;
    cursor: pointer;
    z-index: 100;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
    transition: right 0.3s;
}

.sidebar-hidden .toggle-sidebar-btn {
    right: 0;
}

.sidebar-hidden .content-sidebar {
    transform: translateX(100%);
}

.progress-bar-container {
    height: 6px;
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s;
}

.navigation-buttons {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 1rem;
}

.btn-navigation {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: #212529;
    font-weight: 500;
}

.btn-navigation:hover {
    background: #e9ecef;
    text-decoration: none;
    color: #212529;
}

.btn-navigation:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 992px) {
    .content-sidebar {
        position: fixed;
        right: 0;
        top: 60px;
        height: calc(100vh - 60px);
        transform: translateX(100%);
        transition: transform 0.3s;
        z-index: 1000;
    }
    
    .content-sidebar.show {
        transform: translateX(0);
    }
    
    .toggle-sidebar-btn {
        right: 0;
    }
}

.text-content {
    max-width: 900px;
    margin: 0 auto;
}
</style>

<div class="course-viewer-container">
    <!-- Visor Principal -->
    <div class="main-viewer">
        <!-- Header del visor -->
        <div class="viewer-header">
            <div>
                <a href="myCourses.php" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    <span>Volver a mis cursos</span>
                </a>
            </div>
            <h5><?php echo htmlspecialchars($course['title']); ?></h5>
            <div></div>
        </div>

        <!-- Contenido del material -->
        <div class="content-viewer">
            <?php echo renderMaterialContent($current_material); ?>
        </div>

        <!-- Detalles del contenido -->
        <div class="content-details-section">
            <!-- Título y meta -->
            <div class="content-title-section">
                <h2><?php echo htmlspecialchars($current_material['title'] ?? $course['title']); ?></h2>
                <div class="content-meta">
                    <span><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($course['teacher_name']); ?></span>
                    <span><i class="bi bi-folder2-open"></i> <?php echo count($materials); ?> materiales</span>
                    <span><i class="bi bi-clipboard-check"></i> <?php echo $tasks_count; ?> tareas</span>
                </div>
            </div>

            <!-- Tabs de navegación -->
            <div class="tabs-section">
                <ul class="nav nav-tabs-custom">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#description">Descripción general</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#resources">Recursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tasks.php?course_id=<?php echo $course_id; ?>">Tareas</a>
                    </li>
                </ul>
            </div>

            <!-- Contenido de las tabs -->
            <div class="tab-content">
                <!-- Tab de Descripción -->
                <div class="tab-pane fade show active" id="description">
                    <div class="course-description-text">
                        <?php if ($current_material): ?>
                            <h5>Sobre este material</h5>
                            <p><?php echo nl2br(htmlspecialchars($current_material['description'] ?? 'Sin descripción disponible')); ?></p>
                            <hr class="my-4">
                        <?php endif; ?>
                        
                        <h5>Sobre este curso</h5>
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    </div>

                    <!-- Info del profesor -->
                    <div class="teacher-info">
                        <div class="teacher-avatar">
                            <?php echo strtoupper(substr($course['teacher_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Instructor</div>
                            <div><?php echo htmlspecialchars($course['teacher_name']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Tab de Recursos -->
                <div class="tab-pane fade" id="resources">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Todos los recursos del curso están disponibles en la barra lateral derecha.
                    </div>
                    <?php if ($current_material && !empty($current_material['file_path'])): ?>
                        <div class="card">
                            <div class="card-body">
                                <h6>Recurso actual</h6>
                                <a href="../../<?php echo htmlspecialchars($current_material['file_path']); ?>" class="btn btn-outline-primary btn-sm" download>
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar de contenido -->
    <div class="content-sidebar">
        <div class="sidebar-header">
            <h6>Contenido del curso</h6>
            <div class="progress-info">
                <?php 
                $completed = count($completed_materials);
                $total = count($materials);
                $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
                echo "$completed de $total completados";
                ?>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
            </div>
        </div>

        <div class="materials-list">
            <?php 
            $item_number = 1;
            foreach ($materials as $material): 
                $icon_data = getContentIcon($material['content_type'], $material['file_path'] ?? '');
                $duration = getEstimatedDuration($material['content_type']);
                $is_completed = in_array($material['id'], $completed_materials);
                $is_active = $material['id'] == $material_id;
            ?>
                <a href="?id=<?php echo $course_id; ?>&material_id=<?php echo $material['id']; ?>" 
                   class="material-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>"
                   data-material-id="<?php echo $material['id']; ?>">
                    <div class="material-checkbox">
                        <?php if ($is_completed): ?>
                            <i class="bi bi-check"></i>
                        <?php endif; ?>
                    </div>
                    <div class="material-icon bg-<?php echo $icon_data['color']; ?> bg-opacity-10 text-<?php echo $icon_data['color']; ?>">
                        <i class="bi <?php echo $icon_data['icon']; ?>"></i>
                    </div>
                    <div class="material-info">
                        <div class="material-number"><?php echo $item_number; ?>. <?php echo ucfirst($material['content_type']); ?></div>
                        <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                        <div class="material-duration">
                            <i class="bi bi-play-circle"></i>
                            <span><?php echo $duration; ?> min</span>
                        </div>
                    </div>
                </a>
            <?php 
                $item_number++;
            endforeach; 
            ?>
        </div>

        <!-- Botones de navegación -->
        <div class="navigation-buttons">
            <?php
            $current_index = 0;
            foreach ($materials as $index => $mat) {
                if ($mat['id'] == $material_id) {
                    $current_index = $index;
                    break;
                }
            }
            $prev_material = $current_index > 0 ? $materials[$current_index - 1] : null;
            $next_material = $current_index < count($materials) - 1 ? $materials[$current_index + 1] : null;
            ?>
            
            <?php if ($prev_material): ?>
                <a href="?id=<?php echo $course_id; ?>&material_id=<?php echo $prev_material['id']; ?>" class="btn-navigation">
                    <i class="bi bi-chevron-left"></i> Anterior
                </a>
            <?php else: ?>
                <button class="btn-navigation" disabled>
                    <i class="bi bi-chevron-left"></i> Anterior
                </button>
            <?php endif; ?>

            <?php if ($next_material): ?>
                <a href="?id=<?php echo $course_id; ?>&material_id=<?php echo $next_material['id']; ?>" class="btn-navigation">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <button class="btn-navigation" disabled>
                    Siguiente <i class="bi bi-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Marcar como completado al hacer clic en el checkbox
document.querySelectorAll('.material-checkbox').forEach(checkbox => {
    checkbox.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const materialItem = this.closest('.material-item');
        const materialId = materialItem.dataset.materialId;
        
        // Toggle completed state
        materialItem.classList.toggle('completed');
        
        // Enviar al servidor (AJAX)
        fetch('../../api/mark_material_complete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                material_id: materialId,
                course_id: <?php echo $course_id; ?>,
                completed: materialItem.classList.contains('completed')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar barra de progreso
                updateProgressBar();
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

function updateProgressBar() {
    const completed = document.querySelectorAll('.material-item.completed').length;
    const total = document.querySelectorAll('.material-item').length;
    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    
    document.querySelector('.progress-bar-fill').style.width = percentage + '%';
    document.querySelector('.progress-info').textContent = `${completed} de ${total} completados`;
}

// Responsive sidebar toggle
const toggleBtn = document.createElement('button');
toggleBtn.className = 'toggle-sidebar-btn d-lg-none';
toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
document.body.appendChild(toggleBtn);

toggleBtn.addEventListener('click', function() {
    document.querySelector('.content-sidebar').classList.toggle('show');
});
</script>

<?php 
ob_end_flush(); 
require_once '../../includes/footer.php'; 
?>