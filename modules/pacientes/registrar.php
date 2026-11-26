<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Variables para repoblar el formulario si hay errores
$errores = [];
$datos = [
    'nombres'             => '',
    'apellidos'           => '',
    'fecha_nacimiento'    => '',
    'sexo'                => '',
    'telefono'            => '',
    'correo'              => '',
    'direccion'           => '',
    'motivo_consulta'     => '',
    'diagnostico_medico'  => '',
    'antecedentes'        => '',
    'observaciones'       => '',
    'proxima_sesion'      => '',
    'sesiones_prescritas' => '',
];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Recoger y limpiar datos
    $datos['nombres']             = trim($_POST['nombres'] ?? '');
    $datos['apellidos']           = trim($_POST['apellidos'] ?? '');
    $datos['fecha_nacimiento']    = trim($_POST['fecha_nacimiento'] ?? '');
    $datos['sexo']                = trim($_POST['sexo'] ?? '');
    $datos['telefono']            = trim($_POST['telefono'] ?? '');
    $datos['correo']              = trim($_POST['correo'] ?? '');
    $datos['direccion']           = trim($_POST['direccion'] ?? '');
    $datos['motivo_consulta']     = trim($_POST['motivo_consulta'] ?? '');
    $datos['diagnostico_medico']  = trim($_POST['diagnostico_medico'] ?? '');
    $datos['antecedentes']        = trim($_POST['antecedentes'] ?? '');
    $datos['observaciones']       = trim($_POST['observaciones'] ?? '');
    $datos['proxima_sesion']      = trim($_POST['proxima_sesion'] ?? '');
    $datos['sesiones_prescritas'] = trim($_POST['sesiones_prescritas'] ?? '');

    // 2) Validaciones
    if ($datos['nombres'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($datos['apellidos'] === '') {
        $errores[] = 'Los apellidos son obligatorios.';
    }
    if ($datos['fecha_nacimiento'] === '') {
        $errores[] = 'La fecha de nacimiento es obligatoria.';
    } elseif (strtotime($datos['fecha_nacimiento']) > time()) {
        $errores[] = 'La fecha de nacimiento no puede ser futura.';
    }
    if ($datos['sexo'] === '' || !in_array($datos['sexo'], ['Femenino', 'Masculino'], true)) {
        $errores[] = 'Debe seleccionar un sexo válido.';
    }
    if ($datos['telefono'] === '') {
        $errores[] = 'El teléfono es obligatorio.';
    }
    if ($datos['correo'] !== '' && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no tiene un formato válido.';
    }
    if ($datos['motivo_consulta'] === '') {
        $errores[] = 'El motivo de consulta es obligatorio.';
    }
    if ($datos['proxima_sesion'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['proxima_sesion'])) {
        $errores[] = 'La fecha de próxima sesión no es válida.';
    }
    if ($datos['sesiones_prescritas'] !== '' &&
        (!ctype_digit($datos['sesiones_prescritas']) ||
         (int) $datos['sesiones_prescritas'] < 1 ||
         (int) $datos['sesiones_prescritas'] > 500)) {
        $errores[] = 'La cantidad de sesiones prescritas debe ser un número entre 1 y 500.';
    }

    // 3) Si no hay errores, guardar en BD
    if (empty($errores)) {

        $sql = "INSERT INTO pacientes
                (nombres, apellidos, fecha_nacimiento, sexo, telefono, correo,
                 direccion, motivo_consulta, diagnostico_medico, antecedentes, observaciones,
                 proxima_sesion, sesiones_prescritas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);

        if ($stmt) {
            // Convertir cadenas vacías a NULL para campos opcionales
            $correo              = $datos['correo']              !== '' ? $datos['correo']              : null;
            $direccion           = $datos['direccion']           !== '' ? $datos['direccion']           : null;
            $diagnostico_medico  = $datos['diagnostico_medico']  !== '' ? $datos['diagnostico_medico']  : null;
            $antecedentes        = $datos['antecedentes']        !== '' ? $datos['antecedentes']        : null;
            $observaciones       = $datos['observaciones']       !== '' ? $datos['observaciones']       : null;
            $proxima_sesion      = $datos['proxima_sesion']      !== '' ? $datos['proxima_sesion']      : null;
            $sesiones_prescritas = $datos['sesiones_prescritas'] !== '' ? (int) $datos['sesiones_prescritas'] : null;

            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssssi',
                $datos['nombres'],
                $datos['apellidos'],
                $datos['fecha_nacimiento'],
                $datos['sexo'],
                $datos['telefono'],
                $correo,
                $direccion,
                $datos['motivo_consulta'],
                $diagnostico_medico,
                $antecedentes,
                $observaciones,
                $proxima_sesion,
                $sesiones_prescritas
            );

            if (mysqli_stmt_execute($stmt)) {
                // Mensaje flash y redirección
                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Paciente registrado correctamente.'
                ];
                header('Location: ' . BASE_URL . '/modules/pacientes/index.php');
                exit;
            } else {
                $errores[] = 'Error al guardar el paciente. Intente nuevamente.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errores[] = 'Error interno al preparar la consulta.';
        }
    }
}
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Registrar nuevo paciente</h1>
            <p class="page-subtitle mb-0">
                Complete los datos para registrar un paciente en el sistema.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/pacientes/index.php" class="btn btn-light">
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

    <div class="card">
        <div class="card-body">

            <form method="POST" action="registrar.php" novalidate>

                <h5 class="mb-3" style="color: var(--color-dark);">Datos personales</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombres" class="form-label">Nombres *</label>
                        <input type="text" class="form-control" id="nombres" name="nombres"
                               value="<?= htmlspecialchars($datos['nombres']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="apellidos" class="form-label">Apellidos *</label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos"
                               value="<?= htmlspecialchars($datos['apellidos']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento *</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                               value="<?= htmlspecialchars($datos['fecha_nacimiento']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="sexo" class="form-label">Sexo *</label>
                        <select class="form-select" id="sexo" name="sexo" required>
                            <option value="">Seleccione...</option>
                            <option value="Femenino" <?= $datos['sexo'] === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                            <option value="Masculino" <?= $datos['sexo'] === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="text" class="form-control" id="telefono" name="telefono"
                               value="<?= htmlspecialchars($datos['telefono']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo"
                               value="<?= htmlspecialchars($datos['correo']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion"
                               value="<?= htmlspecialchars($datos['direccion']) ?>">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3" style="color: var(--color-dark);">Información clínica</h5>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="motivo_consulta" class="form-label">Motivo de consulta *</label>
                        <textarea class="form-control" id="motivo_consulta" name="motivo_consulta" rows="2" required><?= htmlspecialchars($datos['motivo_consulta']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="diagnostico_medico" class="form-label">Diagnóstico médico</label>
                        <textarea class="form-control" id="diagnostico_medico" name="diagnostico_medico" rows="2"><?= htmlspecialchars($datos['diagnostico_medico']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="antecedentes" class="form-label">Antecedentes relevantes</label>
                        <textarea class="form-control" id="antecedentes" name="antecedentes" rows="2"><?= htmlspecialchars($datos['antecedentes']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2"><?= htmlspecialchars($datos['observaciones']) ?></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3" style="color: var(--color-dark);">Plan de tratamiento</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="proxima_sesion" class="form-label">Próxima sesión (fecha tentativa)</label>
                        <input type="date" class="form-control" id="proxima_sesion" name="proxima_sesion"
                               value="<?= htmlspecialchars($datos['proxima_sesion']) ?>">
                        <small class="text-muted">Opcional. Fecha estimada de la siguiente cita.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="sesiones_prescritas" class="form-label">Sesiones prescritas</label>
                        <input type="number" class="form-control" id="sesiones_prescritas" name="sesiones_prescritas"
                               min="1" max="500" placeholder="Ej: 10"
                               value="<?= htmlspecialchars($datos['sesiones_prescritas']) ?>">
                        <small class="text-muted">Opcional. Cantidad total de sesiones indicadas.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= BASE_URL ?>/modules/pacientes/index.php" class="btn btn-light">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar paciente
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>