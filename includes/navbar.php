<div class="app-container">

<?php
// Detectar la sección actual según la URL
$uri_actual      = $_SERVER['REQUEST_URI'] ?? '';
$es_inicio       = !preg_match('#/modules/#', $uri_actual);
$es_pacientes    = strpos($uri_actual, '/modules/pacientes/')    !== false;
$es_evaluaciones = strpos($uri_actual, '/modules/evaluaciones/') !== false;
$es_sesiones     = strpos($uri_actual, '/modules/sesiones/')     !== false;
$es_usuarios     = strpos($uri_actual, '/modules/usuarios/')     !== false;
?>

    <!-- Barra lateral -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="brand-icon">
                <img src="/SISClinic/img/logo.png" alt="Icono de pulso" width="28" height="28">

             
            </div>

            <div>
                <h5>SISClinic</h5>
                <small>Gestión fisioterapéutica</small>
            </div>
        </div>

        <div class="sidebar-menu">

            <p class="menu-title">MENÚ PRINCIPAL</p>

            <a href="<?= BASE_URL ?>/index.php"
   class="menu-link <?= $es_inicio ? 'active' : '' ?>">
    <i class="bi bi-grid-1x2-fill"></i>
    <span>Inicio</span>
</a>

<a href="<?= BASE_URL ?>/modules/pacientes/index.php"
   class="menu-link <?= $es_pacientes ? 'active' : '' ?>">
    <i class="bi bi-people-fill"></i>
    <span>Pacientes</span>
</a>

<a href="<?= BASE_URL ?>/modules/evaluaciones/index.php"
   class="menu-link <?= $es_evaluaciones ? 'active' : '' ?>">
    <i class="bi bi-clipboard2-pulse-fill"></i>
    <span>Evaluaciones</span>
</a>

<a href="<?= BASE_URL ?>/modules/sesiones/index.php"
   class="menu-link <?= $es_sesiones ? 'active' : '' ?>">
    <i class="bi bi-calendar"></i>
    <span>Sesiones</span>
</a>
            <?php if ($rol_usuario === 'Administrador'): ?>

                <p class="menu-title mt-4">ADMINISTRACIÓN</p>

               <a href="<?= BASE_URL ?>/modules/usuarios/index.php"
   class="menu-link <?= $es_usuarios ? 'active' : '' ?>">
    <i class="bi bi-person-gear"></i>
    <span>Usuarios</span>
</a>
            <?php endif; ?>

        </div>

        <div class="sidebar-footer">

            <a href="<?= BASE_URL ?>/logout.php" class="logout-link">
                <i class="bi bi-box-arrow-left"></i>
                <span>Cerrar sesión</span>
            </a>

        </div>

    </aside>


    <!-- Área principal -->
    <div class="main-area">

        <!-- Barra superior -->
        <header class="topbar">

            <div>
                <button class="btn sidebar-toggle d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <div class="user-area">

                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>

                <div class="user-info">
                    <strong><?= htmlspecialchars($nombre_usuario) ?></strong>
                    <small><?= htmlspecialchars($rol_usuario) ?></small>
                </div>

            </div>

        </header>

        <!-- Contenido de la página -->
        <main class="content-area">