<?php
ob_start();
session_start();

// ✅ Conexión a la base de datos (usa PDO)
require_once __DIR__ . '/../../config/database.php';

// ✅ Incluimos encabezados y navbar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// ✅ Verificamos sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Consulta con PDO
$stmt = $pdo->prepare("SELECT username, email, role, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuario no encontrado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil - Sistema Académico</title>
  <link rel="stylesheet" href="../../public/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Solo aplicar fondo al body, sin resetear todo */
    body.profile-page {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
    }

    /* Contenedor principal con padding */
    .profile-page-wrapper {
      padding: 20px;
    }

    .profile-wrapper {
      max-width: 420px;
      margin: 80px auto;
    }

    /* Tarjeta Principal */
    .profile-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 0;
      box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
      overflow: hidden;
      position: relative;
      width: 400px;   /* ajusta el ancho */
  padding: 7px;  /* reduce el espacio interno */
  margin: auto; 
  
    }

    /* Header con gradiente */
    .profile-header {
      background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
      padding: 35px 30px 70px;
      position: relative;
    }

    .profile-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 60px;
      background: #ffffff;
      border-radius: 50% 50% 0 0 / 30px 30px 0 0;
    }

    /* Contenedor de foto */
    .profile-image-container {
      position: relative;
      z-index: 2;
      margin-top: -60px;
      text-align: center;
      padding: 0 30px;
    }

    .profile-image-wrapper {
      width: 120px;
      height: 120px;
      margin: 0 auto;
      border-radius: 50%;
      padding: 4px;
      background: linear-gradient(135deg, #4a90e2, #357abd);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 20px rgba(74, 144, 226, 0.3);
      position: relative;
    }

    .profile-image-wrapper img {
      width: 112px;
      height: 112px;
      border-radius: 50%;
      object-fit: cover;
      background: #f8f9fa;
      border: 3px solid #ffffff;
    }

    .status-badge {
      position: absolute;
      bottom: 6px;
      right: 6px;
      width: 20px;
      height: 20px;
      background: #10b981;
      border: 3px solid #ffffff;
      border-radius: 50%;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.5);
    }

    /* Información del perfil */
    .profile-info {
      text-align: center;
      padding: 25px 30px 35px;
    }

    .profile-info h2 {
      font-size: 22px;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 8px;
    }

    .profile-role {
      display: inline-block;
      padding: 5px 14px;
      background: linear-gradient(135deg, #4a90e2, #357abd);
      color: #ffffff;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 20px;
    }

    /* Detalles en grid */
    .profile-details {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
      margin-top: 20px;
    }

    .detail-item {
      background: #f9fafb;
      padding: 16px;
      border-radius: 14px;
      text-align: left;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .detail-item:hover {
      background: #f3f4f6;
      border-color: #4a90e2;
      transform: translateY(-2px);
    }

    .detail-item i {
      color: #4a90e2;
      font-size: 16px;
      margin-bottom: 8px;
      display: block;
    }

    .detail-label {
      font-size: 11px;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
    }

    .detail-value {
      font-size: 14px;
      font-weight: 600;
      color: #1f2937;
      word-break: break-word;
    }

    /* Botones de acción */
    .profile-actions {
      display: flex;
      gap: 12px;
      margin-top: 25px;
      flex-wrap: wrap;
    }

    .btn {
      flex: 1;
      min-width: 100%;
      padding: 12px 20px;
      border-radius: 11px;
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
    }

    .btn-primary {
      background: linear-gradient(135deg, #4a90e2, #357abd);
      color: #ffffff;
      box-shadow: 0 6px 16px rgba(74, 144, 226, 0.25);
    }.px{
      margin-top: 35px;
      flex-wrap: wrap;
    }

    .btn {
      flex: 1;
      min-width: 200px;
      padding: 14px 28px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 15px;
      text-decoration: none;
      text-align: center;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      border: none;
      cursor: pointer;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #ffffff;
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(74, 144, 226, 0.35);
    }

    .btn-secondary {
      background: #ffffff;
      color: #4a90e2;
      border: 2px solid #4a90e2;
    }

    .btn-secondary:hover {
      background: #4a90e2;
      color: #ffffff;
      transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .profile-wrapper {
        margin: 30px auto;
      }

      .profile-header {
        padding: 30px 25px 80px;
      }

      .profile-image-container {
        padding: 0 25px;
      }

      .profile-info {
        padding: 25px 25px 30px;
      }

      .profile-info h2 {
        font-size: 24px;
      }

      .profile-details {
        grid-template-columns: 1fr;
      }

      .profile-actions {
        flex-direction: column;
      }

      .btn {
        min-width: 100%;
      }
    }
  </style>
</head>

<body>
  <div class="profile-wrapper">
    <!-- Tarjeta Principal del Perfil -->
    <div class="profile-card">
      <div class="profile-header"></div>
      
      <div class="profile-image-container">
        <div class="profile-image-wrapper">
          <img src="../../uploads/profiles/<?= htmlspecialchars($user['profile_image'] ?: 'default.png'); ?>" 
               alt="Foto de perfil de <?= htmlspecialchars($user['username']); ?>">
          <div class="status-badge" title="Usuario activo"></div>
        </div>
      </div>

      <div class="profile-info">
        <h2><?= htmlspecialchars($user['username']); ?></h2>
        <span class="profile-role">
          <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($user['role']); ?>
        </span>

        <div class="profile-details">
          <div class="detail-item">
            <i class="fas fa-envelope"></i>
            <div class="detail-label">Correo Electrónico</div>
            <div class="detail-value"><?= htmlspecialchars($user['email']); ?></div>
          </div>

          <div class="detail-item">
            <i class="fas fa-id-badge"></i>
            <div class="detail-label">Usuario</div>
            <div class="detail-value"><?= htmlspecialchars($user['username']); ?></div>
          </div>
        </div>

        <div class="profile-actions">
          <a href="edit_profile.php" class="btn btn-primary">
            <i class="fas fa-edit"></i> Editar Perfil
          </a>
        </div>
      </div>
    </div>
  </div>

  <?php ob_end_flush(); require_once '../../includes/footer.php'; ?>
</body>
</html>