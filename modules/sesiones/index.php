<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// 1) Filtros (GET)
$busqueda        = trim($_GET['q'] ?? '');
$filtro_paciente = isset($_GET['paciente']) ? (int) $_GET['paciente'] : 0;
$fecha_desde     = trim($_GET['desde'] ?? '');
$fecha_hasta     = trim($_GET['hasta'] ?? '');

// Validar formato de fechas (YYYY-MM-DD)
if ($fecha_desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
    $fecha_desde = '';
}
if ($fecha_hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
    $fecha_hasta = '';
}

// Lista de pacientes para el dropdown de filtro
$res_pacientes_filtro = mysqli_query(
    $con,
    "SELECT id_paciente, nombres, apellidos FROM pacientes ORDER BY apellidos, nombres"
);

// 2) Consulta principal
$sql = "SELECT s.id_sesion,
               s.fecha,
               s.dolor_inicial,
               s.dolor_final,
               s.tratamiento_realizado,
               p.id_paciente,
               p.nombres   AS paciente_nombres,
               p.apellidos AS paciente_apellidos,
               u.nombre_completo AS fisioterapeuta
        FROM sesiones s
        INNER JOIN pacientes p ON p.id_paciente = s.id_paciente
        INNER JOIN usuarios  u ON u.id_usuario  = s.id_usuario
        WHERE 1 = 1";

$params = [];
$tipos  = '';

if ($filtro_paciente > 0) {
    $sql     .= " AND s.id_paciente = ?";
    $params[] = $filtro_paciente;
    $tipos   .= 'i';
}

if ($fecha_desde !== '') {
    $sql     .= " AND s.fecha >= ?";
    $params[] = $fecha_desde;
    $tipos   .= 's';
}

if ($fecha_hasta !== '') {
    $sql     .= " AND s.fecha <= ?";
    $params[] = $fecha_hasta;
    $tipos   .= 's';
}

if ($busqueda !== '') {
    $sql .= " AND (s.tratamiento_realizado LIKE ?
                 OR s.ejercicios_tecnicas   LIKE ?
                 OR s.observaciones         LIKE ?
                 OR p.nombres               LIKE ?
                 OR p.apellidos             LIKE ?)";
    $termino  = '%' . $busqueda . '%';
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $tipos   .= 'sssss';
}

$sql .= " ORDER BY s.fecha DESC, s.id_sesion DESC LIMIT 100";

$stmt = mysqli_prepare($con, $sql);
if ($tipos !== '') {
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
}

mysqli_stmt_execute($stmt);
$resultado        = mysqli_stmt_get_result($stmt);
$total_resultados = mysqli_num_rows($resultado);

// Total general
$res_total = mysqli_query($con, "SELECT COUNT(*) AS total FROM sesiones");
$total_sesiones = (int) ($res_total ? mysqli_fetch_assoc($res_total)['total'] : 0);

// ¿Hay algún filtro activo?
$hay_filtros = $busqueda !== '' || $filtro_paciente > 0 || $fecha_desde !== '' || $fecha_hasta !== '';

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Helper color de dolor
function color_dolor(int $dolor): string {
    if ($dolor <= 3) return 'success';
    if ($dolor <= 6) return 'warning';
    return 'danger';
}
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
            <h1 class="page-title">Sesiones</h1>
            <p class="page-subtitle mb-0">
                Sesiones de fisioterapia registradas en el sistema.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/sesiones/registrar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle-fill me-1"></i>
            Nueva sesión
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-2 align-items-end">

                <div class="col-md-4">
                    <label class="form-label" style="font-size: 12px;">Paciente</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <select name="paciente" class="form-select">
                            <option value="0">Todos los pacientes</option>
                            <?php while ($pf = mysqli_fetch_assoc($res_pacientes_filtro)): ?>
                                <option value="<?= (int) $pf['id_paciente'] ?>"
                                    <?= $filtro_paciente === (int) $pf['id_paciente'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pf['apellidos'] . ', ' . $pf['nombres']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label" style="font-size: 12px;">Desde</label>
                    <input type="date" class="form-control" name="desde"
                           value="<?= htmlspecialchars($fecha_desde) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label" style="font-size: 12px;">Hasta</label>
                    <input type="date" class="form-control" name="hasta"
                           value="<?= htmlspecialchars($fecha_hasta) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-size: 12px;">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" name="q"
                               placeholder="Tratamiento, técnicas o paciente"
                               value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i> Filtrar
                    </button>

                    <?php if ($hay_filtros): ?>
                        <a href="index.php" class="btn btn-light">
                            <i class="bi bi-x-lg me-1"></i> Limpiar filtros
                        </a>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>

    <!-- Card con tabla -->
    <div class="card card-section card-section--violet">
        <div class="card-header">
            <span class="section-icon">
                <i class="bi bi-calendar3"></i>
            </span>
            <strong>
                <?php if ($hay_filtros): ?>
                    Resultados filtrados
                <?php else: ?>
                    Listado de sesiones
                <?php endif; ?>
            </strong>

            <small class="text-muted ms-auto">
                <?php if ($hay_filtros): ?>
                    <?= $total_resultados ?> resultado<?= $total_resultados === 1 ? '' : 's' ?>
                    de <?= $total_sesiones ?> en total
                <?php else: ?>
                    <?= $total_sesiones ?> sesión<?= $total_sesiones === 1 ? '' : 'es' ?> registrada<?= $total_sesiones === 1 ? '' : 's' ?>
                <?php endif; ?>
            </small>
        </div>

        <?php if ($total_resultados === 0): ?>

            <div class="card-body empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <p>
                    <?php if ($hay_filtros): ?>
                        No hay sesiones que coincidan con los filtros aplicados.
                    <?php else: ?>
                        Aún no se han registrado sesiones en el sistema.
                    <?php endif; ?>
                </p>
                <small>
                    <?php if ($hay_filtros): ?>
                        Prueba ampliar el rango de fechas o quitar filtros.
                    <?php else: ?>
                        Registra una nueva sesión para comenzar.
                    <?php endif; ?>
                </small>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Fisioterapeuta</th>
                            <th>Dolor inicial</th>
                            <th>Dolor final</th>
                            <th>Tratamiento</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = mysqli_fetch_assoc($resultado)): ?>
                            <?php
                                $di = (int) $s['dolor_inicial'];
                                $df = (int) $s['dolor_final'];
                                $mejora = $di - $df;
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($s['fecha']))) ?>
                                </td>

                                <td>
                                    <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $s['id_paciente'] ?>"
                                       style="color: var(--color-primary); font-weight: 600;">
                                        <?= htmlspecialchars($s['paciente_apellidos'] . ', ' . $s['paciente_nombres']) ?>
                                    </a>
                                </td>

                                <td>
                                    <?= htmlspecialchars($s['fisioterapeuta']) ?>
                                </td>

                                <td>
                                    <span class="badge bg-<?= color_dolor($di) ?>">
                                        <?= $di ?> / 10
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-<?= color_dolor($df) ?>">
                                        <?= $df ?> / 10
                                    </span>

                                    <?php if ($mejora > 0): ?>
                                        <br>
                                        <small class="text-success">
                                            <i class="bi bi-arrow-down-short"></i>
                                            -<?= $mejora ?>
                                        </small>
                                    <?php elseif ($mejora < 0): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="bi bi-arrow-up-short"></i>
                                            +<?= abs($mejora) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($s['tratamiento_realizado'])): ?>
                                        <?= htmlspecialchars(mb_strimwidth($s['tratamiento_realizado'], 0, 45, '…')) ?>
                                    <?php else: ?>
                                        <span style="color: var(--color-muted);">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/modules/sesiones/ver.php?id=<?= (int) $s['id_sesion'] ?>"
                                       class="btn btn-sm btn-light" title="Ver detalle de la sesión">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>