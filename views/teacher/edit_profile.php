<?php
ob_start();
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener datos actuales del usuario
$stmt = $pdo->prepare("SELECT username, email, profile_image, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuario no encontrado.");
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $profile_image = $user['profile_image'];

    // --- Subida de imagen de perfil ---
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = __DIR__ . "/../../uploads/profiles/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                $profile_image = $fileName;
            } else {
                $error = "Error al subir la imagen.";
            }
        } else {
            $error = "Solo se permiten archivos JPG, JPEG o PNG.";
        }
    }

    // --- Si el usuario desea cambiar su contraseña ---
    if (!empty($new_password)) {
        if (password_verify($current_password, $user['password'])) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_image = ? WHERE id = ?");
            $ok = $update->execute([$username, $email, $hashedPassword, $profile_image, $user_id]);
        } else {
            $error = "La contraseña actual no es correcta.";
        }
    } else {
        // --- Si NO cambia la contraseña ---
        $update = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?");
        $ok = $update->execute([$username, $email, $profile_image, $user_id]);
    }

    if (empty($error) && isset($ok) && $ok) {
        $success = "Perfil actualizado correctamente.";
        header("Location: profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Perfil - Sistema Académico</title>
  <link rel="stylesheet" href="../../public/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body.edit-profile-page {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
    }

    .edit-profile-wrapper {
      padding: 20px;
      max-width: 440px;
      margin: 60px auto;
    }

    .edit-profile-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 35px 30px;
      box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
      position: relative;
    }

    .edit-profile-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .edit-profile-header h2 {
      font-size: 24px;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 8px;
    }

    .edit-profile-header p {
      font-size: 14px;
      color: #6b7280;
    }

    /* Preview de imagen actual */
    .current-image-preview {
      text-align: center;
      margin-bottom: 25px;
    }

    .current-image-preview img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #4a90e2;
      box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
    }

    .current-image-label {
      display: block;
      font-size: 12px;
      color: #6b7280;
      margin-top: 10px;
      font-weight: 500;
    }

    /* Mensajes de alerta */
    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert i {
      font-size: 16px;
    }

    .alert-error {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    .alert-success {
      background: #f0fdf4;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    /* Form groups */
    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
    }

    .form-label i {
      color: #4a90e2;
      font-size: 14px;
    }

    .form-input {
      width: 100%;
      padding: 12px 14px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      background: #f9fafb;
    }

    .form-input:focus {
      outline: none;
      border-color: #4a90e2;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
    }

    .form-input::placeholder {
      color: #9ca3af;
    }

    /* File input personalizado */
    .file-input-wrapper {
      position: relative;
      overflow: hidden;
    }

    .file-input-label {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 12px 14px;
      background: #f9fafb;
      border: 2px dashed #d1d5db;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
      color: #6b7280;
      font-weight: 500;
    }

    .file-input-label:hover {
      border-color: #4a90e2;
      background: #eff6ff;
      color: #4a90e2;
    }

    .file-input-label i {
      font-size: 16px;
    }

    input[type="file"] {
      position: absolute;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
    }

    .file-name {
      margin-top: 8px;
      font-size: 12px;
      color: #4a90e2;
      font-weight: 500;
    }

    /* Sección de contraseña */
    .password-section {
      background: #f9fafb;
      padding: 20px;
      border-radius: 12px;
      margin: 25px 0;
      border: 1px solid #e5e7eb;
    }

    .password-section-title {
      font-size: 14px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .password-section-title i {
      color: #4a90e2;
    }

    .password-hint {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 15px;
      font-style: italic;
    }

    /* Botones de acción */
    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 30px;
    }

    .btn {
      flex: 1;
      padding: 13px 20px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      text-align: center;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: none;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
    }

    .btn-primary {
      background: linear-gradient(135deg, #4a90e2, #357abd);
      color: #ffffff;
      box-shadow: 0 6px 16px rgba(74, 144, 226, 0.25);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(74, 144, 226, 0.35);
    }

    .btn-secondary {
      background: #ffffff;
      color: #6b7280;
      border: 2px solid #e5e7eb;
    }

    .btn-secondary:hover {
      background: #f9fafb;
      border-color: #d1d5db;
      transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .edit-profile-wrapper {
        margin: 30px auto;
      }

      .edit-profile-card {
        padding: 25px 20px;
      }

      .form-actions {
        flex-direction: column;
      }
    }
  </style>
</head>

<body class="edit-profile-page">
  <div class="edit-profile-wrapper">
    <div class="edit-profile-card">
      <div class="edit-profile-header">
        <h2>Editar Perfil</h2>
        <p>Actualiza tu información personal</p>
      </div>

      <!-- Preview de imagen actual -->
      <div class="current-image-preview">
        <img src="../../uploads/profiles/<?= htmlspecialchars($user['profile_image'] ?: 'default.png'); ?>" 
             alt="Foto actual" 
             id="currentImage">
        <span class="current-image-label">Foto actual</span>
      </div>

      <!-- Mensajes de error/éxito -->
      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <!-- Nombre de Usuario -->
        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-user"></i>
            Nombre de Usuario
          </label>
          <input type="text" 
                 name="username" 
                 class="form-input" 
                 value="<?= htmlspecialchars($user['username']) ?>" 
                 required>
        </div>

        <!-- Correo Electrónico -->
        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-envelope"></i>
            Correo Electrónico
          </label>
          <input type="email" 
                 name="email" 
                 class="form-input" 
                 value="<?= htmlspecialchars($user['email']) ?>" 
                 required>
        </div>

        <!-- Foto de Perfil -->
        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-camera"></i>
            Cambiar Foto de Perfil
          </label>
          <div class="file-input-wrapper">
            <label class="file-input-label" for="profileImage">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>Seleccionar nueva imagen</span>
            </label>
            <input type="file" 
                   name="profile_image" 
                   id="profileImage" 
                   accept="image/jpeg,image/jpg,image/png"
                   onchange="previewImage(event)">
          </div>
          <div class="file-name" id="fileName"></div>
        </div>

        <!-- Sección de Contraseña -->
        <div class="password-section">
          <div class="password-section-title">
            <i class="fas fa-lock"></i>
            Cambiar Contraseña (Opcional)
          </div>
          <p class="password-hint">
            Solo completa estos campos si deseas cambiar tu contraseña
          </p>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-key"></i>
              Contraseña Actual
            </label>
            <input type="password" 
                   name="current_password" 
                   class="form-input" 
                   placeholder="Ingresa tu contraseña actual">
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-lock"></i>
              Nueva Contraseña
            </label>
            <input type="password" 
                   name="new_password" 
                   class="form-input" 
                   placeholder="Ingresa tu nueva contraseña">
          </div>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Guardar Cambios
          </button>
          <a href="profile.php" class="btn btn-secondary">
            <i class="fas fa-times"></i>
            Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Preview de la imagen antes de subir
    function previewImage(event) {
      const file = event.target.files[0];
      const fileName = document.getElementById('fileName');
      const currentImage = document.getElementById('currentImage');
      
      if (file) {
        fileName.textContent = '📎 ' + file.name;
        
        const reader = new FileReader();
        reader.onload = function(e) {
          currentImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
      } else {
        fileName.textContent = '';
      }
    }
  </script>

  <?php require_once '../../includes/footer.php'; 
  ob_end_flush(); ?>
</body>
</html>