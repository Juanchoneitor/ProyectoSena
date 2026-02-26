<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guardamos el nombre del archivo actual
$currentPage = basename($_SERVER['PHP_SELF']);

// NO hacer redirección desde el navbar
// La redirección debe manejarse en cada página individualmente
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/cursos_app/views/student/dashboard.php">
            <img src="/cursos_app/Img/LOGOCURSOS.png" alt="Plataforma de Cursos" class="navbar-logo">
            <span class="brand-text ms-2">CursosApp</span>
        </a>

        <?php if ($currentPage !== 'login.php' && $currentPage !== 'register.php') : ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <?php
                if (isset($_SESSION['user_id'])) {
                    $role = $_SESSION['role'] ?? 'guest';
                    if ($role === 'student') { ?>
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="/cursos_app/views/student/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'courses.php' ? 'active' : '' ?>" href="/cursos_app/views/student/courses.php"><i class="fas fa-book me-1"></i> Mis Cursos</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'tasks.php' ? 'active' : '' ?>" href="/cursos_app/views/student/tasks.php"><i class="fas fa-tasks me-1"></i> Tareas</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link user-dropdown" href="#" id="userDropdownStudent" onclick="toggleDropdown(event, 'dropdownMenuStudent')">
                                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Estudiante'); ?></span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" id="dropdownMenuStudent">
                                    <li class="dropdown-header"><i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'Estudiante'); ?></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/cursos_app/views/student/profile.php"><i class="fas fa-eye me-2"></i> Ver Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="/cursos_app/controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        </ul>
                    <?php } elseif ($role === 'teacher') { ?>
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="/cursos_app/views/teacher/dashboard.php"><i class="fas fa-chalkboard-teacher me-1"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'courses.php' ? 'active' : '' ?>" href="/cursos_app/views/teacher/courses.php"><i class="fas fa-book me-1"></i> Mis Cursos</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'tasks.php' ? 'active' : '' ?>" href="/cursos_app/views/teacher/tasks.php"><i class="fas fa-tasks me-1"></i> Tareas</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'materials.php' ? 'active' : '' ?>" href="/cursos_app/views/teacher/materials.php"><i class="fas fa-folder-open me-1"></i>Materiales</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link user-dropdown" href="#" id="userDropdownTeacher" onclick="toggleDropdown(event, 'dropdownMenuTeacher')">
                                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Docente'); ?></span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" id="dropdownMenuTeacher">
                                    <li class="dropdown-header"><i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'Docente'); ?></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/cursos_app/views/teacher/profile.php"><i class="fas fa-eye me-2"></i> Ver Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="/cursos_app/controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        </ul>
                    <?php } elseif ($role === 'admin') { ?>
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="/cursos_app/views/admin/dashboard.php"><i class="fas fa-user-shield me-1"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="/cursos_app/views/admin/users.php"><i class="fas fa-users me-1"></i> Usuarios</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link user-dropdown" href="#" id="userDropdownAdmin" onclick="toggleDropdown(event, 'dropdownMenuAdmin')">
                                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" id="dropdownMenuAdmin">
                                    <li class="dropdown-header"><i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/cursos_app/views/admin/profile.php"><i class="fas fa-eye me-2"></i> Ver Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="/cursos_app/controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        </ul>
                    <?php }
                } ?>
            </div>
        <?php endif; ?>
    </div>
</nav>

<style>
    .navbar { 
        background-color: #fff; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        padding: 0.5rem 1rem; 
    }
    .navbar-logo { 
        height: 55px; 
        width: auto; 
    }
    .brand-text { 
        font-weight: 600; 
        font-size: 1.1rem; 
        color: #1a365d; 
    }
    .nav-link { 
        font-weight: 500; 
        color: #4a5568 !important; 
        padding: 6px 12px !important; 
        border-radius: 6px; 
        transition: 0.3s; 
        margin: 0 2px; 
        display: flex; 
        align-items: center; 
    }
    .nav-link:hover, 
    .nav-link.active { 
        background-color: #edf2f7; 
        color: #1a365d !important; 
    }
    .user-dropdown { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        color: #fff !important; 
        border-radius: 20px; 
        padding: 6px 12px !important; 
        display: flex; 
        align-items: center; 
        cursor: pointer; 
    }
    .user-dropdown:hover { 
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%); 
        box-shadow: 0 4px 10px rgba(102,126,234,0.3); 
    }
    .user-avatar { 
        width: 28px; 
        height: 28px; 
        background: rgba(255,255,255,0.2); 
        border-radius: 50%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin-right: 6px; 
    }
    .user-name { 
        font-weight: 600; 
        max-width: 110px; 
        overflow: hidden; 
        text-overflow: ellipsis; 
        white-space: nowrap; 
    }
    .dropdown { 
        position: relative; 
    }
    .dropdown-menu { 
        border-radius: 10px; 
        border: none; 
        box-shadow: 0 6px 20px rgba(0,0,0,0.15); 
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 8px;
        min-width: 200px;
        background: #fff;
        z-index: 1000;
        padding: 8px 0;
    }
    .dropdown-menu.show { 
        display: block; 
    }
    .dropdown-item { 
        padding: 10px 16px; 
        transition: all 0.2s; 
        display: flex;
        align-items: center;
        color: #4a5568;
        text-decoration: none;
    }
    .dropdown-item:hover { 
        background-color: #f7fafc; 
        color: #1a365d;
    }
    .dropdown-header { 
        font-weight: 600; 
        color: #2d3748; 
        padding: 8px 16px;
        font-size: 0.875rem;
    }
    .dropdown-divider {
        height: 1px;
        margin: 8px 0;
        background-color: #e2e8f0;
        border: none;
    }
    @media (max-width: 991px) { 
        .navbar-logo { 
            height: 45px; 
        } 
        .brand-text { 
            font-size: 1rem; 
        } 
        .nav-link { 
            margin: 4px 0; 
        }
        .dropdown-menu { 
            position: static; 
            box-shadow: none; 
            margin-top: 4px; 
        }
    }
</style>

<script>
function toggleDropdown(event, menuId) {
    event.preventDefault();
    event.stopPropagation();
    
    // Cerrar todos los otros dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== menuId) {
            menu.classList.remove('show');
        }
    });
    
    // Toggle el dropdown actual
    const menu = document.getElementById(menuId);
    menu.classList.toggle('show');
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Cerrar dropdown al presionar Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
</script>