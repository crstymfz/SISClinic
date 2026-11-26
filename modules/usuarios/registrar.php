<?php
// modules/usuarios/registrar.php
// Formulario para registrar un nuevo usuario (solo Administrador).

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';

// 🔒 Guard: solo Administrador
if (($_SESSION['rol'] ?? '') !== 'Administrador') {
    $_SESSION['flash'] = [
        'type'    => 'warning',
        'message' => 'Acceso restringido. El módulo de Usuarios solo está disponible para Administradores.'
    ];
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/navbar.php';

// Valores por defecto
$errores = [];
$datos = [
    'nombre_completo' => '',
    'usuario'         => '',
    'rol'             => 'Fisioterapeuta',
    'estado'          => 1,
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos['nombre_completo'] = trim($_POST['nombre_completo'] ?? '');
    $datos['usuario']         = trim($_POST['usuario'] ?? '');
    $datos['rol']             = trim($_POST['rol'] ?? '');
    $datos['estado']          = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones
    if ($datos['nombre_completo'] === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    }
    if ($datos['usuario'] === '') {
        $errores[] = 'El nombre de usuario es obligatorio.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $datos['usuario'])) {
        $errores[] = 'El usuario debe tener entre 3 y 50 caracteres (letras, números, punto, guion o guion bajo).';
    }
    if ($password === '') {
        $errores[] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $password_confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }
    if (!in_array($datos['rol'], ['Administrador', 'Fisioterapeuta'], true)) {
        $errores[] = 'Debe seleccionar un rol válido.';
    }

    // Verificar que el usuario no exista
    if (empty($errores)) {
        $stmt = mysqli_prepare($con, "SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $datos['usuario']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errores[] = 'El nombre de usuario ya está en uso. Elija otro.';
        }
        mysqli_stmt_close($stmt);
    }

    // Insertar
    if (empty($errores)) {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre_completo, usuario, contrasena, rol, estado)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                'ssssi',
                $datos['nombre_completo'],
                $datos['usuario'],
                $hash,
                $datos['rol'],
                $datos['estado']
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Usuario "' . $datos['nombre_completo'] . '" registrado correctamente.'
                ];
                header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
                exit;
            } else {
                $errores[] = 'Error al guardar el usuario. Intente nuevamente.';
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
            <h1 class="page-title">Registrar usuario</h1>
            <p class="page-subtitle mb-0">
                Cree una nueva cuenta de acceso al sistema.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="btn btn-light">
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

                <h5 class="mb-3" style="color: var(--color-dark);">Datos del usuario</h5>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="nombre_completo" class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="nombre_completo"
                               name="nombre_completo"
                               value="<?= htmlspecialchars($datos['nombre_completo']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="usuario" class="form-label">Nombre de usuario *</label>
                        <input type="text" class="form-control" id="usuario"
                               name="usuario"
                               placeholder="Ej: jperez"
                               value="<?= htmlspecialchars($datos['usuario']) ?>" required>
                        <small class="text-muted">Solo letras, números, punto, guion o guion bajo.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" id="password"
                               name="password" minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirmar contraseña *</label>
                        <input type="password" class="form-control" id="password_confirm"
                               name="password_confirm" minlength="6" required>
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="mb-3" style="color: var(--color-dark);">Rol y estado</h5>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="Fisioterapeuta" <?= $datos['rol'] === 'Fisioterapeuta' ? 'selected' : '' ?>>
                                Fisioterapeuta
                            </option>
                            <option value="Administrador" <?= $datos['rol'] === 'Administrador' ? 'selected' : '' ?>>
                                Administrador
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado *</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="1" <?= $datos['estado'] === 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= $datos['estado'] === 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="btn btn-light">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar usuario
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>