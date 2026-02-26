<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../../views/auth/login.php?error=Por favor inicia sesión como estudiante');
    exit;
}

// Validar course_id
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Location: dashboard.php?error=ID de curso no válido');
    exit;
}

$course_id = (int)$_GET['course_id'];
$student_id = $_SESSION['user_id'];

try {
    // Verificar si el estudiante está inscrito en el curso
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    if (!$stmt->fetch()) {
        header('Location: dashboard.php?error=No estás inscrito en este curso');
        exit;
    }

    // Obtener materiales
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE course_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$course_id]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener título del curso
    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        header('Location: dashboard.php?error=Curso no encontrado');
        exit;
    }
} catch (PDOException $e) {
    $error = "Error al cargar los materiales: " . $e->getMessage();
}

// Función para extraer ID de video de YouTube
function getYouTubeID($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/';
    preg_match($pattern, $url, $matches);
    return $matches[1] ?? null;
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary">Materiales de <?php echo htmlspecialchars($course['title']); ?></h1>
        <a href="viewCourse.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Curso
        </a>
    </div>

    <?php if (isset($_GET['error'])) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } elseif (isset($error)) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (empty($materials)) { ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder-x" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3 text-muted">No hay materiales disponibles</h4>
                <p class="text-muted">Tu profesor aún no ha subido materiales para este curso.</p>
            </div>
        </div>
    <?php } else { ?>
        <div class="row">
            <?php foreach ($materials as $material) { 
                $content_type = $material['content_type'] ?? 'file';
                $icon_map = [
                    'file' => 'bi-file-earmark-pdf',
                    'video' => 'bi-camera-video',
                    'youtube' => 'bi-youtube',
                    'text' => 'bi-file-text',
                    'link' => 'bi-link-45deg'
                ];
                $color_map = [
                    'file' => 'primary',
                    'video' => 'danger',
                    'youtube' => 'danger',
                    'text' => 'success',
                    'link' => 'info'
                ];
                $icon = $icon_map[$content_type] ?? 'bi-file-earmark';
                $color = $color_map[$content_type] ?? 'secondary';
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm hover-card">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="me-3">
                                    <i class="bi <?php echo $icon; ?> text-<?php echo $color; ?>" style="font-size: 2.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($material['title']); ?></h5>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> 
                                        <?php echo date('d/m/Y', strtotime($material['uploaded_at'])); ?>
                                    </small>
                                </div>
                            </div>

                            <?php if (!empty($material['description'])): ?>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($material['description']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-3">
                                <?php if ($content_type === 'youtube'): 
                                    $youtube_id = getYouTubeID($material['content_url']);
                                    if ($youtube_id): ?>
                                        <button class="btn btn-danger btn-sm w-100" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#videoModal<?php echo $material['id']; ?>">
                                            <i class="bi bi-play-circle"></i> Ver Video
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($material['content_url']); ?>" 
                                           
                                           class="btn btn-danger btn-sm w-100">
                                            <i class="bi bi-box-arrow-up-right"></i> Abrir en YouTube
                                        </a>
                                    <?php endif; ?>
                                
                                <?php elseif ($content_type === 'text'): ?>
                                    <button class="btn btn-success btn-sm w-100" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#textModal<?php echo $material['id']; ?>">
                                        <i class="bi bi-book"></i> Leer Contenido
                                    </button>
                                
                                <?php elseif ($content_type === 'link'): ?>
                                    <a href="<?php echo htmlspecialchars($material['content_url']); ?>" 
                                       
                                       class="btn btn-info btn-sm w-100">
                                        <i class="bi bi-box-arrow-up-right"></i> Abrir Enlace
                                    </a>
                                
                                <?php elseif ($content_type === 'video'): ?>
                                    <button class="btn btn-danger btn-sm w-100" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#videoFileModal<?php echo $material['id']; ?>">
                                        <i class="bi bi-play-circle"></i> Ver Video
                                    </button>
                                
                                <?php else: // file ?>
                                    <a href="<?php echo '/' . htmlspecialchars($material['file_path']); ?>" 
                         
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-download"></i> Descargar / Ver
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal para videos de YouTube -->
                <?php if ($content_type === 'youtube' && !empty($youtube_id)): ?>
                    <div class="modal fade" id="videoModal<?php echo $material['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div class="ratio ratio-16x9">
                                        <iframe src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" 
                                            allowfullscreen></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Modal para contenido de texto -->
                <?php if ($content_type === 'text'): ?>
                    <div class="modal fade" id="textModal<?php echo $material['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (!empty($material['description'])): ?>
                                        <div class="alert alert-info">
                                            <?php echo htmlspecialchars($material['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="content-text">
                                        <?php echo $material['content_text']; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Modal para videos de archivo -->
                <?php if ($content_type === 'video' && !empty($material['file_path'])): ?>
                    <div class="modal fade" id="videoFileModal<?php echo $material['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <video controls class="w-100" style="max-height: 70vh;">
                                        <source src="<?php echo '/' . htmlspecialchars($material['file_path']); ?>" type="video/mp4">
                                        Tu navegador no soporta el tag de video.
                                    </video>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php } ?>
        </div>
    <?php } ?>
</div>

<?php require_once '../../includes/footer.php'; ?>

<style>
.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}

.content-text {
    line-height: 1.8;
    font-size: 1rem;
}

.content-text img {
    max-width: 100%;
    height: auto;
}

.content-text h1, .content-text h2, .content-text h3 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
}

.content-text p {
    margin-bottom: 1rem;
}
</style>