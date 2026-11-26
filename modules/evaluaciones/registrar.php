<?php
// modules/evaluaciones/registrar.php
// Formulario para registrar una nueva evaluación fisioterapéutica.

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// 1) Lista de pacientes para el dropdown
$res_pacientes = mysqli_query(
    $con,
    "SELECT id_paciente, nombres, apellidos FROM pacientes ORDER BY apellidos, nombres"
);

// 2) Pre-seleccionar paciente si viene por GET (útil desde el expediente)
$id_paciente_sel = isset($_GET['paciente']) ? (int) $_GET['paciente'] : 0;

// 3) Valores por defecto
$errores = [];
$datos = [
    'id_paciente'                  => $id_paciente_sel,
    'fecha_evaluacion'             => date('Y-m-d'),
    'dolor'                        => 0,
    'fuerza_muscular'              => '',
    'rango_articular'              => '',
    'movilidad'                    => '',
    'equilibrio'                   => '',
    'marcha'                       => '',
    'observaciones'                => '',
    'diagnostico_fisioterapeutico' => '',
    'objetivos_tratamiento'        => '',
];

// 4) Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos['id_paciente']                  = (int) ($_POST['id_paciente'] ?? 0);
    $datos['fecha_evaluacion']             = trim($_POST['fecha_evaluacion'] ?? '');
    $datos['dolor']                        = (int) ($_POST['dolor'] ?? 0);
    $datos['fuerza_muscular']              = trim($_POST['fuerza_muscular'] ?? '');
    $datos['rango_articular']              = trim($_POST['rango_articular'] ?? '');
    $datos['movilidad']                    = trim($_POST['movilidad'] ?? '');
    $datos['equilibrio']                   = trim($_POST['equilibrio'] ?? '');
    $datos['marcha']                       = trim($_POST['marcha'] ?? '');
    $datos['observaciones']                = trim($_POST['observaciones'] ?? '');
    $datos['diagnostico_fisioterapeutico'] = trim($_POST['diagnostico_fisioterapeutico'] ?? '');
    $datos['objetivos_tratamiento']        = trim($_POST['objetivos_tratamiento'] ?? '');

    // Validaciones
    if ($datos['id_paciente'] <= 0) {
        $errores[] = 'Debe seleccionar un paciente.';
    }
    if ($datos['fecha_evaluacion'] === '') {
        $errores[] = 'La fecha de evaluación es obligatoria.';
    } elseif (strtotime($datos['fecha_evaluacion']) > time()) {
        $errores[] = 'La fecha de evaluación no puede ser futura.';
    }
    if ($datos['dolor'] < 0 || $datos['dolor'] > 10) {
        $errores[] = 'El nivel de dolor debe estar entre 0 y 10.';
    }

    // 5) Guardar
    if (empty($errores)) {

        $id_usuario = (int) $_SESSION['id_usuario'];

        $sql = "INSERT INTO evaluaciones
                (id_paciente, id_usuario, fecha_evaluacion, dolor,
                 fuerza_muscular, rango_articular, movilidad, equilibrio, marcha,
                 observaciones, diagnostico_fisioterapeutico, objetivos_tratamiento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);

        if ($stmt) {
            // Vacíos → NULL
            $fuerza        = $datos['fuerza_muscular']              !== '' ? $datos['fuerza_muscular']              : null;
            $rango         = $datos['rango_articular']              !== '' ? $datos['rango_articular']              : null;
            $movilidad     = $datos['movilidad']                    !== '' ? $datos['movilidad']                    : null;
            $equilibrio    = $datos['equilibrio']                   !== '' ? $datos['equilibrio']                   : null;
            $marcha        = $datos['marcha']                       !== '' ? $datos['marcha']                       : null;
            $observaciones = $datos['observaciones']                !== '' ? $datos['observaciones']                : null;
            $diagnostico   = $datos['diagnostico_fisioterapeutico'] !== '' ? $datos['diagnostico_fisioterapeutico'] : null;
            $objetivos     = $datos['objetivos_tratamiento']        !== '' ? $datos['objetivos_tratamiento']        : null;

            mysqli_stmt_bind_param(
                $stmt,
                'iissssssssss',
                $datos['id_paciente'],
                $id_usuario,
                $datos['fecha_evaluacion'],
                $datos['dolor'],
                $fuerza,
                $rango,
                $movilidad,
                $equilibrio,
                $marcha,
                $observaciones,
                $diagnostico,
                $objetivos
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Evaluación registrada correctamente.'
                ];
                header('Location: ' . BASE_URL . '/modules/pacientes/ver.php?id=' . $datos['id_paciente']);
                exit;
            } else {
                $errores[] = 'Error al guardar la evaluación. Intente nuevamente.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errores[] = 'Error interno al preparar la consulta.';
        }
    }
}

// 6) Datos del fisioterapeuta actual (para mostrar en el form)
$nombre_fisio = $_SESSION['nombre'] ?? 'Profesional';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Nueva evaluación</h1>
            <p class="page-subtitle mb-0">
                Registre una evaluación fisioterapéutica para un paciente.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/evaluaciones/index.php" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado
        </a>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <strong>Se encontraron los siguientes errores:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="registrar.php" novalidate>

        <!-- Card: Datos generales -->
        <div class="card card-section card-section--blue mb-4">
            <div class="card-header">
                <span class="section-icon"><i class="bi bi-info-circle-fill"></i></span>
                <strong>Datos generales</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="id_paciente" class="form-label">Paciente *</label>
                        <select class="form-select" id="id_paciente" name="id_paciente" required>
                            <option value="">Seleccione un paciente...</option>
                            <?php while ($p = mysqli_fetch_assoc($res_pacientes)): ?>
                                <option value="<?= (int) $p['id_paciente'] ?>"
                                    <?= $datos['id_paciente'] === (int) $p['id_paciente'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['apellidos'] . ', ' . $p['nombres']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="fecha_evaluacion" class="form-label">Fecha de evaluación *</label>
                        <input type="date" class="form-control" id="fecha_evaluacion"
                               name="fecha_evaluacion"
                               value="<?= htmlspecialchars($datos['fecha_evaluacion']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fisioterapeuta</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($nombre_fisio) ?>" disabled>
                        <small class="text-muted">Se asigna automáticamente.</small>
                    </div>

                </div>
            </div>
        </div>

        <!-- Card: Nivel de dolor -->
        <div class="card card-section card-section--coral mb-4">
            <div class="card-header">
                <span class="section-icon"><i class="bi bi-thermometer-half"></i></span>
                <strong>Nivel de dolor</strong>
            </div>

            <div class="card-body">
                <label for="dolor" class="form-label">
                    Escala de dolor (0 = sin dolor, 10 = dolor máximo) *
                </label>

                <div class="d-flex align-items-center gap-3">
                    <input type="range" class="form-range flex-grow-1" id="dolor" name="dolor"
                           min="0" max="10" step="1"
                           value="<?= (int) $datos['dolor'] ?>">

                    <span id="dolorBadge" class="badge bg-success" style="font-size: 15px; min-width: 55px;">
                        <?= (int) $datos['dolor'] ?> / 10
                    </span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">0 - Sin dolor</small>
                    <small class="text-muted">10 - Dolor severo</small>
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
                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="fuerza_muscular" class="form-label">Fuerza muscular</label>
                        <input type="text" class="form-control" id="fuerza_muscular" name="fuerza_muscular"
                               placeholder="Ej: 4/5 en cuádriceps derecho"
                               value="<?= htmlspecialchars($datos['fuerza_muscular']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="rango_articular" class="form-label">Rango articular</label>
                        <input type="text" class="form-control" id="rango_articular" name="rango_articular"
                               placeholder="Ej: Flexión limitada a 90°"
                               value="<?= htmlspecialchars($datos['rango_articular']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="movilidad" class="form-label">Movilidad</label>
                        <input type="text" class="form-control" id="movilidad" name="movilidad"
                               placeholder="Ej: Reducida"
                               value="<?= htmlspecialchars($datos['movilidad']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="equilibrio" class="form-label">Equilibrio</label>
                        <input type="text" class="form-control" id="equilibrio" name="equilibrio"
                               placeholder="Ej: Estable"
                               value="<?= htmlspecialchars($datos['equilibrio']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="marcha" class="form-label">Marcha</label>
                        <input type="text" class="form-control" id="marcha" name="marcha"
                               placeholder="Ej: Normal"
                               value="<?= htmlspecialchars($datos['marcha']) ?>">
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
                <div class="row g-3">

                    <div class="col-12">
                        <label for="diagnostico_fisioterapeutico" class="form-label">
                            Diagnóstico fisioterapéutico
                        </label>
                        <textarea class="form-control" id="diagnostico_fisioterapeutico"
                                  name="diagnostico_fisioterapeutico" rows="2"><?= htmlspecialchars($datos['diagnostico_fisioterapeutico']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="objetivos_tratamiento" class="form-label">
                            Objetivos del tratamiento
                        </label>
                        <textarea class="form-control" id="objetivos_tratamiento"
                                  name="objetivos_tratamiento" rows="2"><?= htmlspecialchars($datos['objetivos_tratamiento']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones"
                                  name="observaciones" rows="2"><?= htmlspecialchars($datos['observaciones']) ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="<?= BASE_URL ?>/modules/evaluaciones/index.php" class="btn btn-light">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Guardar evaluación
            </button>
        </div>

    </form>

</div>

<!-- Script: actualizar el badge del dolor cuando se mueve el slider -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('dolor');
    const badge  = document.getElementById('dolorBadge');

    if (!slider || !badge) return;

    function actualizar() {
        const valor = parseInt(slider.value, 10);
        badge.textContent = valor + ' / 10';

        badge.classList.remove('bg-success', 'bg-warning', 'bg-danger');

        if (valor <= 3) {
            badge.classList.add('bg-success');
        } else if (valor <= 6) {
            badge.classList.add('bg-warning');
        } else {
            badge.classList.add('bg-danger');
        }
    }

    slider.addEventListener('input', actualizar);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>