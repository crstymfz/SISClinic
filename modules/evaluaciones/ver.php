<?php
// modules/evaluaciones/ver.php
// Detalle completo de una evaluación fisioterapéutica.

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Validar ID
$id_evaluacion = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_evaluacion <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Evaluación no especificada.'];
    header('Location: ' . BASE_URL . '/modules/evaluaciones/index.php');
    exit;
}

// Buscar evaluación + datos del paciente y fisioterapeuta
$sql = "SELECT e.*,
               p.id_paciente,
               p.nombres   AS paciente_nombres,
               p.apellidos AS paciente_apellidos,
               u.nombre_completo AS fisioterapeuta
        FROM evaluaciones e
        INNER JOIN pacientes p ON p.id_paciente = e.id_paciente
        INNER JOIN usuarios  u ON u.id_usuario  = e.id_usuario
        WHERE e.id_evaluacion = ?
        LIMIT 1";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id_evaluacion);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$evaluacion = mysqli_fetch_assoc($res);

if (!$evaluacion) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'La evaluación solicitada no existe.'];
    header('Location: ' . BASE_URL . '/modules/evaluaciones/index.php');
    exit;
}

// Helper color de dolor
function color_dolor(int $dolor): string {
    if ($dolor <= 3) return 'success';
    if ($dolor <= 6) return 'warning';
    return 'danger';
}

$dolor = (int) $evaluacion['dolor'];
$nivel = $dolor <= 3 ? 'Leve' : ($dolor <= 6 ? 'Moderado' : 'Severo');
?>

<div class="container-fluid">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Detalle de evaluación</h1>
            <p class="page-subtitle mb-0">
                Evaluación del
                <strong><?= htmlspecialchars(date('d/m/Y', strtotime($evaluacion['fecha_evaluacion']))) ?></strong>
                para
                <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $evaluacion['id_paciente'] ?>"
                   style="color: var(--color-primary); font-weight: 600;">
                    <?= htmlspecialchars($evaluacion['paciente_nombres'] . ' ' . $evaluacion['paciente_apellidos']) ?>
                </a>
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/modules/evaluaciones/index.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>

            <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $evaluacion['id_paciente'] ?>#tab-evaluaciones"
               class="btn btn-primary">
                <i class="bi bi-person-fill me-1"></i> Ver paciente
            </a>
        </div>
    </div>

    <!-- Card: Resumen (paciente, fisio, fecha, dolor) -->
    <div class="card card-section card-section--blue mb-4">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-info-circle-fill"></i></span>
            <strong>Resumen de la evaluación</strong>
        </div>

        <div class="card-body">
            <div class="row g-4">

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--teal">
                        <span class="info-field-icon"><i class="bi bi-person-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Paciente</span>
                            <span class="info-field-value"><?= htmlspecialchars($evaluacion['paciente_nombres'] . ' ' . $evaluacion['paciente_apellidos']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--teal">
                        <span class="info-field-icon"><i class="bi bi-person-badge-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Fisioterapeuta</span>
                            <span class="info-field-value"><?= htmlspecialchars($evaluacion['fisioterapeuta']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--blue">
                        <span class="info-field-icon"><i class="bi bi-calendar-event-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Fecha</span>
                            <span class="info-field-value"><?= htmlspecialchars(date('d/m/Y', strtotime($evaluacion['fecha_evaluacion']))) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="info-field info-field--coral">
                        <span class="info-field-icon"><i class="bi bi-thermometer-half"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Dolor reportado</span>
                            <span class="info-field-value">
                                <span class="badge bg-<?= color_dolor($dolor) ?>">
                                    <?= $dolor ?> / 10 · <?= $nivel ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Card: Evaluación funcional -->
    <div class="card card-section card-section--emerald mb-4">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-activity"></i></span>
            <strong>Evaluación funcional</strong>
        </div>

        <div class="card-body">
            <div class="row g-4">

                <div class="col-md-6">
                    <div class="info-field info-field--emerald">
                        <span class="info-field-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Fuerza muscular</span>
                            <span class="info-field-value">
                                <?= !empty($evaluacion['fuerza_muscular']) ? htmlspecialchars($evaluacion['fuerza_muscular']) : '—' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-field info-field--emerald">
                        <span class="info-field-icon"><i class="bi bi-arrows-angle-expand"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Rango articular</span>
                            <span class="info-field-value">
                                <?= !empty($evaluacion['rango_articular']) ? htmlspecialchars($evaluacion['rango_articular']) : '—' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-field info-field--emerald">
                        <span class="info-field-icon"><i class="bi bi-arrow-left-right"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Movilidad</span>
                            <span class="info-field-value">
                                <?= !empty($evaluacion['movilidad']) ? htmlspecialchars($evaluacion['movilidad']) : '—' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-field info-field--emerald">
                        <span class="info-field-icon"><i class="bi bi-align-center"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Equilibrio</span>
                            <span class="info-field-value">
                                <?= !empty($evaluacion['equilibrio']) ? htmlspecialchars($evaluacion['equilibrio']) : '—' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-field info-field--emerald">
                        <span class="info-field-icon"><i class="bi bi-person-walking"></i></span>
                        <div class="info-field-body">
                            <span class="info-field-label">Marcha</span>
                            <span class="info-field-value">
                                <?= !empty($evaluacion['marcha']) ? htmlspecialchars($evaluacion['marcha']) : '—' ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Card: Diagnóstico y objetivos -->
    <div class="card card-section card-section--violet mb-4">
        <div class="card-header">
            <span class="section-icon"><i class="bi bi-clipboard2-pulse-fill"></i></span>
            <strong>Diagnóstico y objetivos</strong>
        </div>

        <div class="card-body">

            <div class="clinical-note mb-3">
                <div class="clinical-note-label">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                    Diagnóstico fisioterapéutico
                </div>
                <?php if (!empty($evaluacion['diagnostico_fisioterapeutico'])): ?>
                    <p class="clinical-note-value"><?= nl2br(htmlspecialchars($evaluacion['diagnostico_fisioterapeutico'])) ?></p>
                <?php else: ?>
                    <p class="clinical-note-value is-empty">Sin diagnóstico registrado.</p>
                <?php endif; ?>
            </div>

            <div class="clinical-note mb-3">
                <div class="clinical-note-label">
                    <i class="bi bi-bullseye"></i>
                    Objetivos del tratamiento
                </div>
                <?php if (!empty($evaluacion['objetivos_tratamiento'])): ?>
                    <p class="clinical-note-value"><?= nl2br(htmlspecialchars($evaluacion['objetivos_tratamiento'])) ?></p>
                <?php else: ?>
                    <p class="clinical-note-value is-empty">Sin objetivos definidos.</p>
                <?php endif; ?>
            </div>

            <div class="clinical-note">
                <div class="clinical-note-label">
                    <i class="bi bi-journal-text"></i>
                    Observaciones
                </div>
                <?php if (!empty($evaluacion['observaciones'])): ?>
                    <p class="clinical-note-value"><?= nl2br(htmlspecialchars($evaluacion['observaciones'])) ?></p>
                <?php else: ?>
                    <p class="clinical-note-value is-empty">Sin observaciones.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>