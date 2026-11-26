<?php

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// 1) Lista de pacientes para el dropdown
$res_pacientes = mysqli_query(
    $con,
    "SELECT id_paciente, nombres, apellidos FROM pacientes ORDER BY apellidos, nombres"
);

// 2) Pre-seleccionar paciente si viene por GET
$id_paciente_sel = isset($_GET['paciente']) ? (int) $_GET['paciente'] : 0;

// 3) Valores por defecto
$errores = [];
$datos = [
    'id_paciente'          => $id_paciente_sel,
    'fecha'                => date('Y-m-d'),
    'dolor_inicial'        => 0,
    'tratamiento_realizado'=> '',
    'ejercicios_tecnicas'  => '',
    'respuesta_paciente'   => '',
    'dolor_final'          => 0,
    'observaciones'        => '',
];

// 4) Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos['id_paciente']           = (int) ($_POST['id_paciente'] ?? 0);
    $datos['fecha']                 = trim($_POST['fecha'] ?? '');
    $datos['dolor_inicial']         = (int) ($_POST['dolor_inicial'] ?? 0);
    $datos['tratamiento_realizado'] = trim($_POST['tratamiento_realizado'] ?? '');
    $datos['ejercicios_tecnicas']   = trim($_POST['ejercicios_tecnicas'] ?? '');
    $datos['respuesta_paciente']    = trim($_POST['respuesta_paciente'] ?? '');
    $datos['dolor_final']           = (int) ($_POST['dolor_final'] ?? 0);
    $datos['observaciones']         = trim($_POST['observaciones'] ?? '');

    // Validaciones
    if ($datos['id_paciente'] <= 0) {
        $errores[] = 'Debe seleccionar un paciente.';
    }
    if ($datos['fecha'] === '') {
        $errores[] = 'La fecha es obligatoria.';
    } elseif (strtotime($datos['fecha']) > time()) {
        $errores[] = 'La fecha no puede ser futura.';
    }
    if ($datos['dolor_inicial'] < 0 || $datos['dolor_inicial'] > 10) {
        $errores[] = 'El dolor inicial debe estar entre 0 y 10.';
    }
    if ($datos['dolor_final'] < 0 || $datos['dolor_final'] > 10) {
        $errores[] = 'El dolor final debe estar entre 0 y 10.';
    }
    if ($datos['tratamiento_realizado'] === '') {
        $errores[] = 'El tratamiento realizado es obligatorio.';
    }

    // 5) Guardar
    if (empty($errores)) {

        $id_usuario = (int) $_SESSION['id_usuario'];

        $sql = "INSERT INTO sesiones
                (id_paciente, id_usuario, fecha, dolor_inicial,
                 tratamiento_realizado, ejercicios_tecnicas, respuesta_paciente,
                 dolor_final, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);

        if ($stmt) {
            // Vacíos → NULL
            $ejercicios   = $datos['ejercicios_tecnicas']  !== '' ? $datos['ejercicios_tecnicas']  : null;
            $respuesta    = $datos['respuesta_paciente']   !== '' ? $datos['respuesta_paciente']   : null;
            $observaciones= $datos['observaciones']        !== '' ? $datos['observaciones']        : null;

            mysqli_stmt_bind_param(
                $stmt,
                'iisississ',
                $datos['id_paciente'],
                $id_usuario,
                $datos['fecha'],
                $datos['dolor_inicial'],
                $datos['tratamiento_realizado'],
                $ejercicios,
                $respuesta,
                $datos['dolor_final'],
                $observaciones
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Sesión registrada correctamente.'
                ];
                header('Location: ' . BASE_URL . '/modules/pacientes/ver.php?id=' . $datos['id_paciente']);
                exit;
            } else {
                $errores[] = 'Error al guardar la sesión. Intente nuevamente.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errores[] = 'Error interno al preparar la consulta.';
        }
    }
}

$nombre_fisio = $_SESSION['nombre'] ?? 'Profesional';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Nueva sesión</h1>
            <p class="page-subtitle mb-0">
                Registre una sesión de fisioterapia para un paciente.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/sesiones/index.php" class="btn btn-light">
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
                        <label for="fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha" name="fecha"
                               value="<?= htmlspecialchars($datos['fecha']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fisioterapeuta</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($nombre_fisio) ?>" disabled>
                        <small class="text-muted">Se asigna automáticamente.</small>
                    </div>

                </div>
            </div>
        </div>

        <!-- Card: Niveles de dolor -->
        <div class="card card-section card-section--coral mb-4">
            <div class="card-header">
                <span class="section-icon"><i class="bi bi-thermometer-half"></i></span>
                <strong>Niveles de dolor (antes y después)</strong>
            </div>

            <div class="card-body">
                <div class="row g-4">

                    <!-- Dolor inicial -->
                    <div class="col-md-6">
                        <label for="dolor_inicial" class="form-label">
                            Dolor inicial (0 = sin dolor, 10 = máximo) *
                        </label>

                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1" id="dolor_inicial"
                                   name="dolor_inicial" min="0" max="10" step="1"
                                   value="<?= (int) $datos['dolor_inicial'] ?>">

                            <span id="badgeInicial" class="badge bg-success"
                                  style="font-size: 15px; min-width: 55px;">
                                <?= (int) $datos['dolor_inicial'] ?> / 10
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">0</small>
                            <small class="text-muted">10</small>
                        </div>
                    </div>

                    <!-- Dolor final -->
                    <div class="col-md-6">
                        <label for="dolor_final" class="form-label">
                            Dolor final (después de la sesión) *
                        </label>

                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1" id="dolor_final"
                                   name="dolor_final" min="0" max="10" step="1"
                                   value="<?= (int) $datos['dolor_final'] ?>">

                            <span id="badgeFinal" class="badge bg-success"
                                  style="font-size: 15px; min-width: 55px;">
                                <?= (int) $datos['dolor_final'] ?> / 10
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">0</small>
                            <small class="text-muted">10</small>
                        </div>
                    </div>

                </div>

                <!-- Indicador de cambio en vivo -->
                <div class="mt-3 text-center">
                    <span id="indicadorMejora" class="text-muted">
                        Sin cambios
                    </span>
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
                <div class="row g-3">

                    <div class="col-12">
                        <label for="tratamiento_realizado" class="form-label">
                            Tratamiento realizado *
                        </label>
                        <textarea class="form-control" id="tratamiento_realizado"
                                  name="tratamiento_realizado" rows="2" required
                                  placeholder="Ej: Terapia manual, calor local, movilización articular..."><?= htmlspecialchars($datos['tratamiento_realizado']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="ejercicios_tecnicas" class="form-label">
                            Ejercicios o técnicas utilizadas
                        </label>
                        <textarea class="form-control" id="ejercicios_tecnicas"
                                  name="ejercicios_tecnicas" rows="2"
                                  placeholder="Ej: Estiramientos de cadena posterior, ejercicios isométricos..."><?= htmlspecialchars($datos['ejercicios_tecnicas']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="respuesta_paciente" class="form-label">
                            Respuesta del paciente
                        </label>
                        <textarea class="form-control" id="respuesta_paciente"
                                  name="respuesta_paciente" rows="2"
                                  placeholder="Ej: Buena tolerancia, refiere alivio..."><?= htmlspecialchars($datos['respuesta_paciente']) ?></textarea>
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
            <a href="<?= BASE_URL ?>/modules/sesiones/index.php" class="btn btn-light">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Guardar sesión
            </button>
        </div>

    </form>

</div>

<!-- Script: sliders de dolor + indicador de mejora en vivo -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sliderIni   = document.getElementById('dolor_inicial');
    const sliderFin   = document.getElementById('dolor_final');
    const badgeIni    = document.getElementById('badgeInicial');
    const badgeFin    = document.getElementById('badgeFinal');
    const indicador   = document.getElementById('indicadorMejora');

    if (!sliderIni || !sliderFin) return;

    function claseDolor(v) {
        if (v <= 3) return 'bg-success';
        if (v <= 6) return 'bg-warning';
        return 'bg-danger';
    }

    function actualizarBadge(slider, badge) {
        const v = parseInt(slider.value, 10);
        badge.textContent = v + ' / 10';
        badge.classList.remove('bg-success', 'bg-warning', 'bg-danger');
        badge.classList.add(claseDolor(v));
        return v;
    }

    function actualizarTodo() {
        const vi = actualizarBadge(sliderIni, badgeIni);
        const vf = actualizarBadge(sliderFin, badgeFin);

        const dif = vi - vf;

        if (dif > 0) {
            indicador.className = 'text-success fw-bold';
            indicador.innerHTML = '<i class="bi bi-arrow-down-short"></i> Reducción de ' + dif + ' punto(s)';
        } else if (dif < 0) {
            indicador.className = 'text-danger fw-bold';
            indicador.innerHTML = '<i class="bi bi-arrow-up-short"></i> Aumento de ' + Math.abs(dif) + ' punto(s)';
        } else {
            indicador.className = 'text-muted';
            indicador.textContent = 'Sin cambios';
        }
    }

    sliderIni.addEventListener('input', actualizarTodo);
    sliderFin.addEventListener('input', actualizarTodo);

    actualizarTodo();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>