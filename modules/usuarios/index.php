<?php
// modules/usuarios/index.php
// Listado de usuarios (solo Administrador).

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';

// 🔒 Guard: solo Administrador
if (($_SESSION['rol'] ?? '') !== 'Administrador') {
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'Acceso restringido. No tiene permisos para acceder a este módulo.'
    ];
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/navbar.php';

// Filtros
$busqueda    = trim($_GET['q'] ?? '');
$filtro_rol  = trim($_GET['rol'] ?? '');
$filtro_est  = $_GET['estado'] ?? '';

$sql    = "SELECT id_usuario, nombre_completo, usuario, rol, estado
           FROM usuarios
           WHERE 1 = 1";
$params = [];
$tipos  = '';

if ($busqueda !== '') {
    $sql .= " AND (nombre_completo LIKE ? OR usuario LIKE ?)";
    $termino  = '%' . $busqueda . '%';
    $params[] = $termino;
    $params[] = $termino;
    $tipos   .= 'ss';
}

if ($filtro_rol !== '' && in_array($filtro_rol, ['Administrador', 'Fisioterapeuta'], true)) {
    $sql .= " AND rol = ?";
    $params[] = $filtro_rol;
    $tipos   .= 's';
}

if ($filtro_est === '0' || $filtro_est === '1') {
    $sql .= " AND estado = ?";
    $params[] = (int) $filtro_est;
    $tipos   .= 'i';
}

$sql .= " ORDER BY estado DESC, nombre_completo ASC";

$stmt = mysqli_prepare($con, $sql);
if ($tipos !== '') {
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado        = mysqli_stmt_get_result($stmt);
$total_resultados = mysqli_num_rows($resultado);

// Totales generales
$res_totales = mysqli_query($con, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) AS inactivos
    FROM usuarios");
$totales = $res_totales ? mysqli_fetch_assoc($res_totales) : ['total'=>0,'activos'=>0,'inactivos'=>0];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="container-fluid">

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Título -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle mb-0">
                Gestión de usuarios del sistema (solo Administrador).
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/usuarios/registrar.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Registrar usuario
        </a>
    </div>

    <!-- Mini stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card stat-card--blue">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <small>Total de usuarios</small>
                    <strong><?= (int) $totales['total'] ?></strong>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card stat-card--emerald">
                <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-info">
                    <small>Activos</small>
                    <strong><?= (int) $totales['activos'] ?></strong>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card stat-card--coral">
                <div class="stat-icon"><i class="bi bi-person-x-fill"></i></div>
                <div class="stat-info">
                    <small>Inactivos</small>
                    <strong><?= (int) $totales['inactivos'] ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-2 align-items-center">

                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="q"
                               placeholder="Buscar por nombre o usuario"
                               value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="rol" class="form-select">
                        <option value="">Todos los roles</option>
                        <option value="Administrador" <?= $filtro_rol === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="Fisioterapeuta" <?= $filtro_rol === 'Fisioterapeuta' ? 'selected' : '' ?>>Fisioterapeuta</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?= $filtro_est === '1' ? 'selected' : '' ?>>Activos</option>
                        <option value="0" <?= $filtro_est === '0' ? 'selected' : '' ?>>Inactivos</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <?php if ($busqueda !== '' || $filtro_rol !== '' || $filtro_est !== ''): ?>
                        <a href="index.php" class="btn btn-light"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card card-section card-section--blue">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-people-fill"></i></span>
            <strong>
                <?php if ($busqueda !== '' || $filtro_rol !== '' || $filtro_est !== ''): ?>
                    Resultados filtrados
                <?php else: ?>
                    Listado de usuarios
                <?php endif; ?>
            </strong>
            <small class="text-muted ms-auto">
                <?= $total_resultados ?> resultado<?= $total_resultados === 1 ? '' : 's' ?>
            </small>
        </div>

        <?php if ($total_resultados === 0): ?>

            <div class="card-body empty-state">
                <div class="empty-state-icon"><i class="bi bi-person-x"></i></div>
                <p>No se encontraron usuarios con los filtros aplicados.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nombre completo</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($resultado)): ?>
                            <?php $es_yo = (int) $u['id_usuario'] === (int) $_SESSION['id_usuario']; ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($u['nombre_completo']) ?></strong>
                                    <?php if ($es_yo): ?>
                                        <span class="badge bg-info ms-1">Usted</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($u['usuario']) ?></td>

                                <td>
                                    <?php if ($u['rol'] === 'Administrador'): ?>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-shield-fill-check me-1"></i>Administrador
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-person-fill me-1"></i>Fisioterapeuta
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ((int) $u['estado'] === 1): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle-fill me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle-fill me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <a href="editar.php?id=<?= (int) $u['id_usuario'] ?>"
                                       class="btn btn-sm btn-light" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <?php if (!$es_yo): ?>
                                        <button type="button"
        class="btn btn-sm btn-light <?= (int) $u['estado'] === 1 ? 'text-danger' : 'text-success' ?> btn-toggle-usuario"
        title="<?= (int) $u['estado'] === 1 ? 'Desactivar' : 'Activar' ?>"
        data-id="<?= (int) $u['id_usuario'] ?>"
        data-nombre="<?= htmlspecialchars($u['nombre_completo']) ?>"
        data-estado="<?= (int) $u['estado'] ?>">
    <i class="bi <?= (int) $u['estado'] === 1 ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

</div>

<!-- Modal de confirmación para cambiar estado -->
<div class="modal fade" id="modalToggleUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="modal-icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                    Confirmar cambio de estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0" id="modalToggleMensaje">
                    ¿Está seguro de cambiar el estado de este usuario?
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <form method="POST" action="toggle_estado.php" id="formToggleUsuario" class="d-inline m-0">
                    <input type="hidden" name="id_usuario" id="modalToggleId" value="">
                    <button type="submit" class="btn btn-primary" id="btnConfirmarToggle">
                        <i class="bi bi-check-lg me-1"></i> Sí, cambiar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script del modal -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalToggleUsuario');
    if (!modalEl) return;

    const modal        = new bootstrap.Modal(modalEl);
    const inputId      = document.getElementById('modalToggleId');
    const mensaje      = document.getElementById('modalToggleMensaje');
    const btnConfirmar = document.getElementById('btnConfirmarToggle');

    document.querySelectorAll('.btn-toggle-usuario').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const id     = boton.dataset.id;
            const nombre = boton.dataset.nombre;
            const estado = boton.dataset.estado;
            const accion = estado === '1' ? 'desactivar' : 'activar';

            inputId.value = id;
            mensaje.innerHTML = '¿Está seguro de <strong>' + accion + '</strong> al usuario <strong>"' +
                                nombre + '"</strong>?';

            // Cambiar color del botón según la acción
            btnConfirmar.className = 'btn ' + (accion === 'desactivar' ? 'btn-danger' : 'btn-success');
            btnConfirmar.innerHTML = '<i class="bi bi-check-lg me-1"></i> Sí, ' + accion;

            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>