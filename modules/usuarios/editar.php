<?php
// modules/usuarios/editar.php
// Edición de un usuario (solo Administrador).

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

// Validar ID
$id_usuario = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_usuario <= 0) {
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'Usuario no especificado.'
    ];
    header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
    exit;
}

// Buscar usuario
$stmt = mysqli_prepare($con, "SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($res);

if (!$usuario) {
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'El usuario solicitado no existe.'
    ];
    header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
    exit;
}

$es_yo = (int) $usuario['id_usuario'] === (int) $_SESSION['id_usuario'];

// Valores para el formulario
$errores = [];
$datos = [
    'nombre_completo' => $usuario['nombre_completo'],
    'usuario'         => $usuario['usuario'],
    'rol'             => $usuario['rol'],
    'estado'          => (int) $usuario['estado'],
];

// Procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos['nombre_completo'] = trim($_POST['nombre_completo'] ?? '');
    $datos['usuario']         = trim($_POST['usuario'] ?? '');
    $datos['rol']             = trim($_POST['rol'] ?? '');
    $datos['estado']          = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;

    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones básicas
    if ($datos['nombre_completo'] === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    }
    if ($datos['usuario'] === '') {
        $errores[] = 'El nombre de usuario es obligatorio.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $datos['usuario'])) {
        $errores[] = 'El usuario debe tener entre 3 y 50 caracteres (letras, números, punto, guion o guion bajo).';
    }
    if (!in_array($datos['rol'], ['Administrador', 'Fisioterapeuta'], true)) {
        $errores[] = 'Debe seleccionar un rol válido.';
    }

    // Si escribió nueva contraseña, validarla
    $cambiar_password = false;
    if ($password !== '' || $password_confirm !== '') {
        if (strlen($password) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        }
        if ($password !== $password_confirm) {
            $errores[] = 'Las contraseñas no coinciden.';
        } else {
            $cambiar_password = true;
        }
    }

    // 🔒 Reglas de auto-edición:
    // - No puede quitarse a sí mismo el rol de Administrador
    // - No puede desactivarse a sí mismo
    if ($es_yo) {
        if ($datos['rol'] !== 'Administrador') {
            $errores[] = 'No puede cambiarse a sí mismo el rol. Debe seguir siendo Administrador.';
        }
        if ($datos['estado'] !== 1) {
            $errores[] = 'No puede desactivar su propia cuenta.';
        }
    }

    // Verificar que el usuario no esté en uso por OTRO registro
    if (empty($errores)) {
        $stmt = mysqli_prepare($con, "SELECT id_usuario FROM usuarios WHERE usuario = ? AND id_usuario <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $datos['usuario'], $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errores[] = 'El nombre de usuario ya está en uso por otro usuario.';
        }
        mysqli_stmt_close($stmt);
    }

    // Guardar
    if (empty($errores)) {

        if ($cambiar_password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE usuarios SET
                        nombre_completo = ?,
                        usuario         = ?,
                        contrasena      = ?,
                        rol             = ?,
                        estado          = ?
                    WHERE id_usuario = ?";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssssii',
                $datos['nombre_completo'],
                $datos['usuario'],
                $hash,
                $datos['rol'],
                $datos['estado'],
                $id_usuario
            );
        } else {
            $sql = "UPDATE usuarios SET
                        nombre_completo = ?,
                        usuario         = ?,
                        rol             = ?,
                        estado          = ?
                    WHERE id_usuario = ?";

            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'sssii',
                $datos['nombre_completo'],
                $datos['usuario'],
                $datos['rol'],
                $datos['estado'],
                $id_usuario
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Usuario actualizado correctamente.'
            ];
            header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
            exit;
        } else {
            $errores[] = 'Error al actualizar el usuario. Intente nuevamente.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Editar usuario</h1>
            <p class="page-subtitle mb-0">
                Modificando a
                <strong><?= htmlspecialchars($usuario['nombre_completo']) ?></strong>
                <?php if ($es_yo): ?>
                    <span class="badge bg-info ms-1">Usted</span>
                <?php endif; ?>
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

            <form method="POST" action="editar.php?id=<?= (int) $id_usuario ?>" novalidate>

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
                               value="<?= htmlspecialchars($datos['usuario']) ?>" required>
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="mb-3" style="color: var(--color-dark);">Cambiar contraseña</h5>

                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Deje estos campos vacíos si no desea cambiar la contraseña.
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="password"
                               name="password" minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" id="password_confirm"
                               name="password_confirm" minlength="6">
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="mb-3" style="color: var(--color-dark);">Rol y estado</h5>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required
                                <?= $es_yo ? 'disabled' : '' ?>>
                            <option value="Fisioterapeuta" <?= $datos['rol'] === 'Fisioterapeuta' ? 'selected' : '' ?>>
                                Fisioterapeuta
                            </option>
                            <option value="Administrador" <?= $datos['rol'] === 'Administrador' ? 'selected' : '' ?>>
                                Administrador
                            </option>
                        </select>
                        <?php if ($es_yo): ?>
                            <input type="hidden" name="rol" value="<?= htmlspecialchars($datos['rol']) ?>">
                            <small class="text-muted">No puede cambiar su propio rol.</small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado *</label>
                        <select class="form-select" id="estado" name="estado" required
                                <?= $es_yo ? 'disabled' : '' ?>>
                            <option value="1" <?= $datos['estado'] === 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= $datos['estado'] === 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                        <?php if ($es_yo): ?>
                            <input type="hidden" name="estado" value="1">
                            <small class="text-muted">No puede desactivar su propia cuenta.</small>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="btn btn-light">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar cambios
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>