<?php
ob_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../views/auth/login.php');
    ob_end_clean();
    exit;
}

$success = filter_input(INPUT_GET, 'success');
$error = filter_input(INPUT_GET, 'error');

// Asegurar que el token CSRF esté siempre presente
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$teacher_id = $_SESSION['user_id'];

// Obtener todos los cursos del docente
try {
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $courses = [];
    $error = "Error al cargar cursos: " . $e->getMessage();
}

// Obtener todos los materiales del docente
try {
    $stmt = $pdo->prepare("SELECT m.*, c.title as course_title 
                          FROM materials m 
                          INNER JOIN courses c ON m.course_id = c.id 
                          WHERE c.teacher_id = ? 
                          ORDER BY m.uploaded_at DESC");
    $stmt->execute([$teacher_id]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $materials = [];
    $error = "Error al cargar materiales: " . $e->getMessage();
}

ob_end_flush();
?>

<div class="container mt-5">
    <h1 class="text-center text-primary mb-4">Gestión de Materiales</h1>
    
    <div class="row">
        <!-- Columna de materiales existentes -->
        <div class="col-md-8">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4>Lista de Materiales</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($materials)): ?>
                        <p class="text-muted">No hay materiales subidos aún.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Curso</th>
                                        <th>Tipo</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($material['title']); ?></td>
                                            <td><?php echo htmlspecialchars($material['course_title']); ?></td>
                                            <td>
                                                <?php 
                                                $type = $material['content_type'] ?? 'file';
                                                $badges = [
                                                    'file' => 'bg-primary',
                                                    'video' => 'bg-danger',
                                                    'youtube' => 'bg-danger',
                                                    'text' => 'bg-success',
                                                    'link' => 'bg-info'
                                                ];
                                                $badge_class = $badges[$type] ?? 'bg-secondary';
                                                echo '<span class="badge ' . $badge_class . '">' . strtoupper($type) . '</span>';
                                                ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($material['uploaded_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm edit-material" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editMaterialModal" 
                                                    data-id="<?php echo $material['id']; ?>" 
                                                    data-course-id="<?php echo $material['course_id']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($material['title']); ?>" 
                                                    data-description="<?php echo htmlspecialchars($material['description'] ?? ''); ?>"
                                                    data-content-type="<?php echo htmlspecialchars($material['content_type'] ?? 'file'); ?>"
                                                    data-content-url="<?php echo htmlspecialchars($material['content_url'] ?? ''); ?>">
                                                    Editar
                                                </button>
                                                <a href="/controllers/materialController.php?action=delete&id=<?php echo $material['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('¿Estás seguro de eliminar este material?');">
                                                    Eliminar
                                                </a>
                                                <?php if ($material['file_path']): ?>
                                                    <a href="<?php echo '/' . htmlspecialchars($material['file_path']); ?>" 
                                                       target="_blank" 
                                                       class="btn btn-info btn-sm">
                                                        Ver
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna del formulario para crear material -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4>Subir Nuevo Material</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="/controllers/materialController.php?action=create" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Curso *</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Selecciona un curso</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="content_type" class="form-label">Tipo de Contenido *</label>
                            <select class="form-select" id="content_type" name="content_type" required>
                                <option value="file">Archivo (PDF, DOC, etc.)</option>
                                <option value="video">Video (MP4, AVI, etc.)</option>
                                <option value="youtube">Video de YouTube</option>
                                <option value="text">Texto/Contenido HTML</option>
                                <option value="link">Enlace Externo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <!-- Campo para archivo -->
                        <div class="mb-3" id="file_field">
                            <label for="file" class="form-label">Archivo</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.mp4,.avi,.mov,.wmv,.jpg,.jpeg,.png,.gif">
                            <small class="form-text text-muted">Formatos permitidos: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, MP4, AVI, MOV, WMV, JPG, PNG, GIF</small>
                        </div>

                        <!-- Campo para URL (YouTube o enlaces) -->
                        <div class="mb-3 d-none" id="url_field">
                            <label for="content_url" class="form-label">URL</label>
                            <input type="url" class="form-control" id="content_url" name="content_url" placeholder="https://...">
                            <small class="form-text text-muted" id="url_help">Ingresa la URL del video de YouTube o enlace externo</small>
                        </div>

                        <!-- Campo para texto -->
                        <div class="mb-3 d-none" id="text_field">
                            <label for="content_text" class="form-label">Contenido</label>
                            <textarea class="form-control" id="content_text" name="content_text" rows="8"></textarea>
                            <small class="form-text text-muted">Puedes usar HTML básico</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Subir Material</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Material -->
    <div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMaterialModalLabel">Editar Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="/controllers/materialController.php?action=update" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" id="edit_material_id">
                        
                        <div class="mb-3">
                            <label for="edit_course_id" class="form-label">Curso *</label>
                            <select class="form-select" id="edit_course_id" name="course_id" required>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="edit_content_type" class="form-label">Tipo de Contenido *</label>
                            <select class="form-select" id="edit_content_type" name="content_type" required>
                                <option value="file">Archivo</option>
                                <option value="video">Video</option>
                                <option value="youtube">YouTube</option>
                                <option value="text">Texto</option>
                                <option value="link">Enlace</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_content_url" class="form-label">URL (si aplica)</label>
                            <input type="url" class="form-control" id="edit_content_url" name="content_url">
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cambiar campos según el tipo de contenido seleccionado
    const contentType = document.getElementById('content_type');
    const fileField = document.getElementById('file_field');
    const urlField = document.getElementById('url_field');
    const textField = document.getElementById('text_field');
    const fileInput = document.getElementById('file');
    const urlInput = document.getElementById('content_url');
    const textInput = document.getElementById('content_text');
    const urlHelp = document.getElementById('url_help');

    contentType.addEventListener('change', function() {
        // Ocultar todos los campos
        fileField.classList.add('d-none');
        urlField.classList.add('d-none');
        textField.classList.add('d-none');
        
        // Quitar required de todos
        fileInput.removeAttribute('required');
        urlInput.removeAttribute('required');
        textInput.removeAttribute('required');

        // Mostrar el campo correspondiente
        switch(this.value) {
            case 'file':
            case 'video':
                fileField.classList.remove('d-none');
                fileInput.setAttribute('required', 'required');
                break;
            case 'youtube':
                urlField.classList.remove('d-none');
                urlInput.setAttribute('required', 'required');
                urlHelp.textContent = 'Ingresa la URL del video de YouTube (ej: https://www.youtube.com/watch?v=...)';
                break;
            case 'link':
                urlField.classList.remove('d-none');
                urlInput.setAttribute('required', 'required');
                urlHelp.textContent = 'Ingresa la URL del enlace externo';
                break;
            case 'text':
                textField.classList.remove('d-none');
                textInput.setAttribute('required', 'required');
                break;
        }
    });

    // Modal para editar material
    var editMaterialModal = document.getElementById('editMaterialModal');
    editMaterialModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var courseId = button.getAttribute('data-course-id');
        var title = button.getAttribute('data-title');
        var description = button.getAttribute('data-description');
        var contentType = button.getAttribute('data-content-type');
        var contentUrl = button.getAttribute('data-content-url');

        document.getElementById('edit_material_id').value = id;
        document.getElementById('edit_course_id').value = courseId;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_content_type').value = contentType;
        document.getElementById('edit_content_url').value = contentUrl;
    });
});
</script>