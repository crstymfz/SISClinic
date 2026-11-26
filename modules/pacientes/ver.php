<?php
// modules/pacientes/ver.php
// Expediente del paciente con pestañas (tabs).

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// 1) Validar ID
$id_paciente = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_paciente <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Paciente no especificado.'];
    header('Location: ' . BASE_URL . '/modules/pacientes/index.php');
    exit;
}

// 2) Buscar paciente
$sql = "SELECT * FROM pacientes WHERE id_paciente = ? LIMIT 1";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$paciente  = mysqli_fetch_assoc($resultado);

if (!$paciente) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'El paciente solicitado no existe.'];
    header('Location: ' . BASE_URL . '/modules/pacientes/index.php');
    exit;
}

// 3) Edad
$fecha_nac = new DateTime($paciente['fecha_nacimiento']);
$hoy       = new DateTime();
$edad      = $hoy->diff($fecha_nac)->y;

// 4) Evaluaciones del paciente (lista)
$stmt = mysqli_prepare($con, "
    SELECT e.id_evaluacion, e.fecha_evaluacion, e.dolor,
           e.diagnostico_fisioterapeutico,
           u.nombre_completo AS fisioterapeuta
    FROM evaluaciones e
    INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
    WHERE e.id_paciente = ?
    ORDER BY e.fecha_evaluacion DESC, e.id_evaluacion DESC
");
mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
mysqli_stmt_execute($stmt);
$evaluaciones = mysqli_stmt_get_result($stmt);

// 4a) Total de evaluaciones mediante COUNT explícito
$stmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM evaluaciones WHERE id_paciente = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
mysqli_stmt_execute($stmt);
$res_count_eval = mysqli_stmt_get_result($stmt);
$total_evaluaciones = (int) (mysqli_fetch_assoc($res_count_eval)['total'] ?? 0);
mysqli_stmt_close($stmt);

// 5) Sesiones del paciente (lista)
$stmt = mysqli_prepare($con, "
    SELECT s.id_sesion, s.fecha, s.dolor_inicial, s.dolor_final,
           s.tratamiento_realizado, s.respuesta_paciente,
           u.nombre_completo AS fisioterapeuta
    FROM sesiones s
    INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
    WHERE s.id_paciente = ?
    ORDER BY s.fecha DESC, s.id_sesion DESC
");
mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
mysqli_stmt_execute($stmt);
$sesiones = mysqli_stmt_get_result($stmt);

// 5a) Total de sesiones mediante COUNT explícito
// (independiente del buffer del resultado de arriba)
$stmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM sesiones WHERE id_paciente = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
mysqli_stmt_execute($stmt);
$res_count_ses = mysqli_stmt_get_result($stmt);
$total_sesiones = (int) (mysqli_fetch_assoc($res_count_ses)['total'] ?? 0);
mysqli_stmt_close($stmt);

// 5b) Cálculos de próxima sesión y progreso del tratamiento
$hoy_ts    = strtotime(date('Y-m-d'));
$prox_ts   = !empty($paciente['proxima_sesion']) ? strtotime($paciente['proxima_sesion']) : null;
$dias_prox = $prox_ts !== null ? (int) floor(($prox_ts - $hoy_ts) / 86400) : null;

$prescritas  = (int) ($paciente['sesiones_prescritas'] ?? 0);
$realizadas  = $total_sesiones;
$progreso    = $prescritas > 0 ? min(100, (int) round(($realizadas / $prescritas) * 100)) : 0;

// 5c) Datos para el gráfico de evolución
$stmt = mysqli_prepare($con, "
    SELECT fecha, dolor_inicial, dolor_final
    FROM sesiones
    WHERE id_paciente = ?
    ORDER BY fecha ASC, id_sesion ASC
");
mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
mysqli_stmt_execute($stmt);
$res_evolucion = mysqli_stmt_get_result($stmt);

$evol_labels   = [];
$evol_inicial  = [];
$evol_final    = [];
$suma_inicial  = 0;
$suma_final    = 0;
$cant_evol     = 0;

while ($row = mysqli_fetch_assoc($res_evolucion)) {
    $evol_labels[]  = date('d/m/Y', strtotime($row['fecha']));
    $evol_inicial[] = (int) $row['dolor_inicial'];
    $evol_final[]   = (int) $row['dolor_final'];
    $suma_inicial  += (int) $row['dolor_inicial'];
    $suma_final    += (int) $row['dolor_final'];
    $cant_evol++;
}

$prom_inicial = $cant_evol > 0 ? round($suma_inicial / $cant_evol, 1) : 0;
$prom_final   = $cant_evol > 0 ? round($suma_final / $cant_evol, 1) : 0;

$reduccion_total = 0;
if ($cant_evol >= 2) {
    $reduccion_total = $evol_inicial[0] - $evol_final[$cant_evol - 1];
}

$chart_evolucion = json_encode([
    'labels'  => $evol_labels,
    'inicial' => $evol_inicial,
    'final'   => $evol_final,
]);

// 6) Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// 7) Helper color de dolor
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

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">
                <?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos']) ?>
            </h1>
            <p class="page-subtitle mb-0">
                Expediente del paciente
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/modules/pacientes/index.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>

            <a href="<?= BASE_URL ?>/modules/pacientes/editar.php?id=<?= (int) $paciente['id_paciente'] ?>"
               class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Editar datos
            </a>
        </div>
    </div>

    <!-- Stat-cards (5) -->
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl">
            <div class="card stat-card stat-card--teal">
                <div class="stat-icon"><i class="bi bi-person-vcard"></i></div>
                <div class="stat-info">
                    <small>Edad</small>
                    <strong><?= $edad ?> años</strong>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card stat-card--blue">
                <div class="stat-icon"><i class="bi bi-gender-ambiguous"></i></div>
                <div class="stat-info">
                    <small>Sexo</small>
                    <strong style="font-size: 16px;"><?= htmlspecialchars($paciente['sexo']) ?></strong>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card stat-card--emerald">
                <div class="stat-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                <div class="stat-info">
                    <small>Evaluaciones</small>
                    <strong><?= $total_evaluaciones ?></strong>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card stat-card--violet">
                <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
                <div class="stat-info">
                    <small>Sesiones</small>
                    <strong><?= $total_sesiones ?></strong>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card stat-card--amber">
                <div class="stat-icon"><i class="bi bi-calendar-event-fill"></i></div>
                <div class="stat-info">
                    <small>Próxima sesión</small>
                    <strong style="font-size: 15px;">
                        <?php if ($prox_ts === null): ?>
                            Sin fecha
                        <?php elseif ($dias_prox < 0): ?>
                            Vencida
                        <?php elseif ($dias_prox === 0): ?>
                            Hoy
                        <?php elseif ($dias_prox === 1): ?>
                            Mañana
                        <?php else: ?>
                            En <?= $dias_prox ?> días
                        <?php endif; ?>
                    </strong>
                </div>
            </div>
        </div>

    </div>

    <!-- TABS -->
    <div class="card card-section card-section--blue">
        <div class="card-header p-0" style="border-bottom: 2px solid var(--color-border);">
            <ul class="nav nav-tabs tabs-expediente" id="tabsExpediente" role="tablist">

                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-datos-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-datos" type="button" role="tab">
                        <i class="bi bi-person-fill me-1"></i> Datos personales
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-clinica-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-clinica" type="button" role="tab">
                        <i class="bi bi-activity me-1"></i> Información clínica
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-evaluaciones-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-evaluaciones" type="button" role="tab">
                        <i class="bi bi-clipboard2-pulse-fill me-1"></i> Evaluaciones
                        <span class="badge bg-secondary ms-1"><?= $total_evaluaciones ?></span>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-sesiones-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-sesiones" type="button" role="tab">
                        <i class="bi bi-calendar3 me-1"></i> Sesiones
                        <span class="badge bg-secondary ms-1"><?= $total_sesiones ?></span>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-evolucion-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-evolucion" type="button" role="tab">
                        <i class="bi bi-graph-up-arrow me-1"></i> Evolución
                    </button>
                </li>

            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="tabsExpedienteContent">

                <!-- TAB: Datos personales -->
                <div class="tab-pane fade show active" id="tab-datos" role="tabpanel">

                    <p class="info-block-title">Identificación</p>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="info-field info-field--teal">
                                <span class="info-field-icon"><i class="bi bi-person-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Nombres</span>
                                    <span class="info-field-value"><?= htmlspecialchars($paciente['nombres']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-field info-field--teal">
                                <span class="info-field-icon"><i class="bi bi-person-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Apellidos</span>
                                    <span class="info-field-value"><?= htmlspecialchars($paciente['apellidos']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-field info-field--coral">
                                <span class="info-field-icon"><i class="bi bi-calendar-event"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Fecha de nacimiento</span>
                                    <span class="info-field-value"><?= htmlspecialchars(date('d/m/Y', strtotime($paciente['fecha_nacimiento']))) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-field info-field--coral">
                                <span class="info-field-icon"><i class="bi bi-gender-ambiguous"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Sexo</span>
                                    <span class="info-field-value"><?= htmlspecialchars($paciente['sexo']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="info-block-title">Contacto</p>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="info-field info-field--emerald">
                                <span class="info-field-icon"><i class="bi bi-telephone-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Teléfono</span>
                                    <span class="info-field-value"><?= htmlspecialchars($paciente['telefono']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-field info-field--emerald">
                                <span class="info-field-icon"><i class="bi bi-envelope-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Correo electrónico</span>
                                    <span class="info-field-value">
                                        <?= !empty($paciente['correo']) ? htmlspecialchars($paciente['correo']) : '—' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-field info-field--emerald">
                                <span class="info-field-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Dirección</span>
                                    <span class="info-field-value">
                                        <?= !empty($paciente['direccion']) ? htmlspecialchars($paciente['direccion']) : '—' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="info-block-title">Registro</p>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="info-field info-field--violet">
                                <span class="info-field-icon"><i class="bi bi-clock-history"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Fecha de registro</span>
                                    <span class="info-field-value"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($paciente['fecha_registro']))) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NUEVA SECCIÓN: Tratamiento -->
                    <p class="info-block-title">Tratamiento</p>
                    <div class="row g-4">

                        <div class="col-md-6">
                            <div class="info-field info-field--amber">
                                <span class="info-field-icon"><i class="bi bi-calendar-check-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Próxima sesión</span>
                                    <span class="info-field-value">
                                        <?php if (!empty($paciente['proxima_sesion'])): ?>
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($paciente['proxima_sesion']))) ?>
                                            <?php if ($dias_prox !== null && $dias_prox >= 0 && $dias_prox <= 7): ?>
                                                <span class="badge bg-success ms-1">
                                                    <?= $dias_prox === 0 ? 'Hoy' : ($dias_prox === 1 ? 'Mañana' : 'En ' . $dias_prox . ' días') ?>
                                                </span>
                                            <?php elseif ($dias_prox !== null && $dias_prox < 0): ?>
                                                <span class="badge bg-danger ms-1">Vencida</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--color-muted); font-style: italic;">No programada</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-field info-field--amber">
                                <span class="info-field-icon"><i class="bi bi-clipboard-check-fill"></i></span>
                                <div class="info-field-body">
                                    <span class="info-field-label">Sesiones prescritas</span>
                                    <span class="info-field-value">
                                        <?php if ($prescritas > 0): ?>
                                            <?= $realizadas ?> de <?= $prescritas ?>
                                            <span class="badge <?= $progreso >= 100 ? 'bg-success' : 'bg-warning text-dark' ?> ms-1">
                                                <?= $progreso ?>%
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--color-muted); font-style: italic;">Sin prescripción</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($prescritas > 0): ?>
                            <div class="col-12">
                                <div class="progress" style="height: 10px; border-radius: 6px;">
                                    <div class="progress-bar <?= $progreso >= 100 ? 'bg-success' : 'bg-warning' ?>"
                                         role="progressbar"
                                         style="width: <?= $progreso ?>%"
                                         aria-valuenow="<?= $progreso ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <?php if ($progreso >= 100): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        Tratamiento completado según lo prescrito.
                                    <?php else: ?>
                                        Faltan <?= max(0, $prescritas - $realizadas) ?> sesión<?= ($prescritas - $realizadas) === 1 ? '' : 'es' ?> para completar.
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <!-- TAB: Información clínica -->
                <div class="tab-pane fade" id="tab-clinica" role="tabpanel">

                    <div class="clinical-note mb-3">
                        <div class="clinical-note-label">
                            <i class="bi bi-chat-left-text-fill"></i>
                            Motivo de consulta
                        </div>
                        <p class="clinical-note-value"><?= nl2br(htmlspecialchars($paciente['motivo_consulta'])) ?></p>
                    </div>

                    <div class="clinical-note mb-3">
                        <div class="clinical-note-label">
                            <i class="bi bi-clipboard2-pulse-fill"></i>
                            Diagnóstico médico
                        </div>
                        <?php if (!empty($paciente['diagnostico_medico'])): ?>
                            <p class="clinical-note-value"><?= nl2br(htmlspecialchars($paciente['diagnostico_medico'])) ?></p>
                        <?php else: ?>
                            <p class="clinical-note-value is-empty">Sin diagnóstico registrado.</p>
                        <?php endif; ?>
                    </div>

                    <div class="clinical-note mb-3">
                        <div class="clinical-note-label">
                            <i class="bi bi-bookmark-fill"></i>
                            Antecedentes relevantes
                        </div>
                        <?php if (!empty($paciente['antecedentes'])): ?>
                            <p class="clinical-note-value"><?= nl2br(htmlspecialchars($paciente['antecedentes'])) ?></p>
                        <?php else: ?>
                            <p class="clinical-note-value is-empty">Sin antecedentes registrados.</p>
                        <?php endif; ?>
                    </div>

                    <div class="clinical-note">
                        <div class="clinical-note-label">
                            <i class="bi bi-journal-text"></i>
                            Observaciones
                        </div>
                        <?php if (!empty($paciente['observaciones'])): ?>
                            <p class="clinical-note-value"><?= nl2br(htmlspecialchars($paciente['observaciones'])) ?></p>
                        <?php else: ?>
                            <p class="clinical-note-value is-empty">Sin observaciones.</p>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- TAB: Evaluaciones -->
                <div class="tab-pane fade" id="tab-evaluaciones" role="tabpanel">

                    <div class="d-flex justify-content-end mb-3">
                        <a href="<?= BASE_URL ?>/modules/evaluaciones/registrar.php?paciente=<?= (int) $paciente['id_paciente'] ?>"
                           class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill me-1"></i> Nueva evaluación
                        </a>
                    </div>

                    <?php if ($total_evaluaciones === 0): ?>

                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-clipboard2-x"></i></div>
                            <p>Este paciente aún no tiene evaluaciones registradas.</p>
                            <small>Usa el botón "Nueva evaluación" para registrar la primera.</small>
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Dolor</th>
                                        <th>Fisioterapeuta</th>
                                        <th>Diagnóstico fisioterapéutico</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($e = mysqli_fetch_assoc($evaluaciones)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($e['fecha_evaluacion']))) ?></td>
                                            <td>
                                                <span class="badge bg-<?= color_dolor((int) $e['dolor']) ?>">
                                                    <?= (int) $e['dolor'] ?> / 10
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($e['fisioterapeuta']) ?></td>
                                            <td>
                                                <?php if (!empty($e['diagnostico_fisioterapeutico'])): ?>
                                                    <?= htmlspecialchars(mb_strimwidth($e['diagnostico_fisioterapeutico'], 0, 70, '…')) ?>
                                                <?php else: ?>
                                                    <span style="color: var(--color-muted);">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>/modules/evaluaciones/ver.php?id=<?= (int) $e['id_evaluacion'] ?>"
                                                   class="btn btn-sm btn-light" title="Ver detalle">
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

                <!-- TAB: Sesiones -->
                <div class="tab-pane fade" id="tab-sesiones" role="tabpanel">

                    <div class="d-flex justify-content-end mb-3">
                        <a href="<?= BASE_URL ?>/modules/sesiones/registrar.php?paciente=<?= (int) $paciente['id_paciente'] ?>"
                           class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill me-1"></i> Nueva sesión
                        </a>
                    </div>

                    <?php if ($total_sesiones === 0): ?>

                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                            <p>Este paciente aún no tiene sesiones registradas.</p>
                            <small>Usa el botón "Nueva sesión" para registrar la primera.</small>
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Dolor</th>
                                        <th>Tratamiento</th>
                                        <th>Fisioterapeuta</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($s = mysqli_fetch_assoc($sesiones)): ?>
                                        <?php $mejora = (int) $s['dolor_inicial'] - (int) $s['dolor_final']; ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($s['fecha']))) ?></td>
                                            <td>
                                                <span class="badge bg-<?= color_dolor((int) $s['dolor_inicial']) ?>">
                                                    <?= (int) $s['dolor_inicial'] ?>
                                                </span>
                                                <i class="bi bi-arrow-right mx-1" style="font-size: 11px; color: var(--color-muted);"></i>
                                                <span class="badge bg-<?= color_dolor((int) $s['dolor_final']) ?>">
                                                    <?= (int) $s['dolor_final'] ?>
                                                </span>
                                                <?php if ($mejora > 0): ?>
                                                    <small class="text-success d-block">-<?= $mejora ?></small>
                                                <?php elseif ($mejora < 0): ?>
                                                    <small class="text-danger d-block">+<?= abs($mejora) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(mb_strimwidth($s['tratamiento_realizado'], 0, 60, '…')) ?></td>
                                            <td><?= htmlspecialchars($s['fisioterapeuta']) ?></td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>/modules/sesiones/ver.php?id=<?= (int) $s['id_sesion'] ?>"
                                                   class="btn btn-sm btn-light" title="Ver detalle">
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

                <!-- TAB: Evolución -->
                <div class="tab-pane fade" id="tab-evolucion" role="tabpanel">

                    <?php if ($cant_evol < 2): ?>

                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-graph-up-arrow"></i></div>
                            <p>Se necesitan al menos 2 sesiones para mostrar la evolución.</p>
                            <small>Actualmente hay <?= $cant_evol ?> sesión<?= $cant_evol === 1 ? '' : 'es' ?> registrada<?= $cant_evol === 1 ? '' : 's' ?>.</small>
                        </div>

                    <?php else: ?>

                        <div class="row g-3 mb-4">

                            <div class="col-md-6 col-xl-3">
                                <div class="card stat-card stat-card--teal">
                                    <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
                                    <div class="stat-info">
                                        <small>Total sesiones</small>
                                        <strong><?= $cant_evol ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <div class="card stat-card stat-card--coral">
                                    <div class="stat-icon"><i class="bi bi-thermometer-high"></i></div>
                                    <div class="stat-info">
                                        <small>Dolor inicial prom.</small>
                                        <strong><?= $prom_inicial ?> / 10</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <div class="card stat-card stat-card--emerald">
                                    <div class="stat-icon"><i class="bi bi-thermometer-low"></i></div>
                                    <div class="stat-info">
                                        <small>Dolor final prom.</small>
                                        <strong><?= $prom_final ?> / 10</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <div class="card stat-card stat-card--violet">
                                    <div class="stat-icon"><i class="bi bi-graph-down-arrow"></i></div>
                                    <div class="stat-info">
                                        <small>Reducción total</small>
                                        <strong>
                                            <?php if ($reduccion_total > 0): ?>
                                                <span class="text-success">-<?= $reduccion_total ?></span>
                                            <?php elseif ($reduccion_total < 0): ?>
                                                <span class="text-danger">+<?= abs($reduccion_total) ?></span>
                                            <?php else: ?>
                                                0
                                            <?php endif; ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card">
                            <div class="card-header">
                                <strong><i class="bi bi-activity me-1"></i> Evolución del dolor por sesión</strong>
                            </div>

                            <div class="card-body">
                                <div class="chart-container" style="height: 320px;">
                                    <canvas id="graficoEvolucion"></canvas>
                                </div>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        Cada punto representa una sesión. La línea roja es el dolor antes y la verde después.
                                    </small>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataEvolucion = <?= $chart_evolucion ?>;
    const ctx = document.getElementById('graficoEvolucion');

    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dataEvolucion.labels,
            datasets: [
                {
                    label: 'Dolor inicial',
                    data: dataEvolucion.inicial,
                    borderColor: '#e76f51',
                    backgroundColor: 'rgba(231, 111, 81, 0.10)',
                    pointBackgroundColor: '#e76f51',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                    tension: 0.35,
                    fill: false
                },
                {
                    label: 'Dolor final',
                    data: dataEvolucion.final,
                    borderColor: '#57cc99',
                    backgroundColor: 'rgba(87, 204, 153, 0.10)',
                    pointBackgroundColor: '#57cc99',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                    tension: 0.35,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: { size: 12, family: "'Inter', sans-serif" },
                        color: '#5a7d92'
                    }
                },
                tooltip: {
                    backgroundColor: '#023e8a',
                    titleFont: { size: 13, family: "'Inter', sans-serif" },
                    bodyFont: { size: 12, family: "'Inter', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function (context) {
                            return ' ' + context.dataset.label + ': ' + context.parsed.y + ' / 10';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    ticks: {
                        stepSize: 2,
                        color: '#5a7d92',
                        font: { size: 11, family: "'Inter', sans-serif" }
                    },
                    grid: { color: 'rgba(90, 125, 146, 0.10)' }
                },
                x: {
                    ticks: {
                        color: '#5a7d92',
                        font: { size: 11, family: "'Inter', sans-serif" }
                    },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>