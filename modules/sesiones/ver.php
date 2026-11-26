<?php
// modules/sesiones/ver.php
// Detalle completo de una sesión de fisioterapia.

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Validar ID
$id_sesion = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_sesion <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Sesión no especificada.'];
    header('Location: ' . BASE_URL . '/modules/sesiones/index.php');
    exit;
}

// Buscar sesión + datos del paciente y fisioterapeuta
$sql = "SELECT s.*,
               p.id_paciente,
               p.nombres   AS paciente_nombres,
               p.apellidos AS paciente_apellidos,
               u.nombre_completo AS fisioterapeuta
        FROM sesiones s
        INNER JOIN pacientes p ON p.id_paciente = s.id_paciente
        INNER JOIN usuarios  u ON u.id_usuario  = s.id_usuario
        WHERE s.id_sesion = ?
        LIMIT 1";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id_sesion);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$sesion = mysqli_fetch_assoc($res);

if (!$sesion) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'La sesión solicitada no existe.'];
    header('Location: ' . BASE_URL . '/modules/sesiones/index.php');
    exit;
}

// Helper color de dolor
function color_dolor(int $dolor): string {
    if ($dolor <= 3) return 'success';
    if ($dolor <= 6) return 'warning';
    return 'danger';
}

$dolor_ini    = (int) $sesion['dolor_inicial'];
$dolor_fin    = (int) $sesion['dolor_final'];
$mejora       = $dolor_ini - $dolor_fin;

$nivel_ini    = $dolor_ini <= 3 ? 'Leve' : ($dolor_ini <= 6 ? 'Moderado' : 'Severo');
$nivel_fin    = $dolor_fin <= 3 ? 'Leve' : ($dolor_fin <= 6 ? 'Moderado' : 'Severo');
?>

<div class="container-fluid">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Detalle de sesión</h1>
            <p class="page-subtitle mb-0">
                Sesión del
                <strong><?= htmlspecialchars(date('d/m/Y', strtotime($sesion['fecha']))) ?></strong>
                para
                <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $sesion['id_paciente'] ?>"
                   style="color: var(--color-primary); font-weight: 600;">
                    <?= htmlspecialchars($sesion['paciente_nombres'] . ' ' . $sesion['paciente_apellidos']) ?>
                </a>
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/modules/sesiones/index.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>

            <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $sesion['id_paciente'] ?>"
               class="btn btn-primary">
                <i class="bi bi-person-fill me-1"></i> Ver paciente
            </a>
        </div>
    </div>

    <!-- Card: Resumen -->
    <div class="card card-section card-section--blue mb-4">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-info-circle-fill"></i></span>
            <strong>Resumen de la sesión</strong>
        </div>

        <div class="card-body">
            <div class="row g-4">

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--teal">
                        <span class="info-field-icon"><i class="bi bi-person-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Paciente</span>
                            <span class="info-field-value"><?= htmlspecialchars($sesion['paciente_nombres'] . ' ' . $sesion['paciente_apellidos']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--teal">
                        <span class="info-field-icon"><i class="bi bi-person-badge-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Fisioterapeuta</span>
                            <span class="info-field-value"><?= htmlspecialchars($sesion['fisioterapeuta']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--blue">
                        <span class="info-field-icon"><i class="bi bi-calendar-event-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Fecha</span>
                            <span class="info-field-value"><?= htmlspecialchars(date('d/m/Y', strtotime($sesion['fecha']))) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--coral">
                        <span class="info-field-icon"><i class="bi bi-thermometer-half"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Dolor antes → después</span>
                            <span class="info-field-value">
                                <span class="badge bg-<?= color_dolor($dolor_ini) ?>"><?= $dolor_ini ?></span>
                                <i class="bi bi-arrow-right mx-1" style="font-size: 11px; color: var(--color-muted);"></i>
                                <span class="badge bg-<?= color_dolor($dolor_fin) ?>"><?= $dolor_fin ?></span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Indicador de cambio -->
            <div class="mt-4 text-center">
                <?php if ($mejora > 0): ?>
                    <span class="badge bg-success" style="font-size: 13px; padding: 8px 16px;">
                        <i class="bi bi-arrow-down-short"></i> Reducción de <?= $mejora ?> punto<?= $mejora === 1 ? '' : 's' ?> de dolor
                    </span>
                <?php elseif ($mejora < 0): ?>
                    <span class="badge bg-danger" style="font-size: 13px; padding: 8px 16px;">
                        <i class="bi bi-arrow-up-short"></i> Aumento de <?= abs($mejora) ?> punto<?= abs($mejora) === 1 ? '' : 's' ?> de dolor
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary" style="font-size: 13px; padding: 8px 16px;">
                        <i class="bi bi-dash"></i> Sin cambios en el dolor
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card: Tratamiento -->
    <div class="card card-section card-section--emerald mb-4">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-heart-pulse"></i></span>
            <strong>Tratamiento realizado</strong>
        </div>

        <div class="card-body">

            <div class="clinical-note mb-3">
                <div class="clinical-note-label">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                    Tratamiento realizado
                </div>
                <p class="clinical-note-value"><?= nl2br(htmlspecialchars($sesion['tratamiento_realizado'])) ?></p>
            </div>

            <div class="clinical-note mb-3">
                <div class="clinical-note-label">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Ejercicios o técnicas utilizadas
                </div>
                <?php if (!empty($sesion['ejercicios_tecnicas'])): ?>
                    <p class="clinical-note-value"><?= nl2br(htmlspecialchars($sesion['ejercicios_tecnicas'])) ?></p>
                <?php else: ?>
                    <p class="clinical-note-value is-empty">Sin ejercicios o técnicas registradas.</p>
                <?php endif; ?>
            </div>

            <div class="clinical-note">
                <div class="clinical-note-label">
                    <i class="bi bi-chat-left-text-fill"></i>
                    Respuesta del paciente
                </div>
                <?php if (!empty($sesion['respuesta_paciente'])): ?>
                    <p class="clinical-note-value"><?= nl2br(htmlspecialchars($sesion['respuesta_paciente'])) ?></p>
                <?php else: ?>
                    <p class="clinical-note-value is-empty">Sin respuesta registrada.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Card: Observaciones -->
    <div class="card card-section card-section--violet mb-4">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-journal-text"></i></span>
            <strong>Observaciones</strong>
        </div>

        <div class="card-body">
            <?php if (!empty($sesion['observaciones'])): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($sesion['observaciones'])) ?></p>
            <?php else: ?>
                <p class="mb-0 is-empty" style="color: var(--color-muted); font-style: italic;">
                    Sin observaciones registradas.
                </p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>