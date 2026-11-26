<?php
// login.php
// Inicio de sesión con consultas preparadas.

session_start();

require_once 'conexion.php';

// Si ya hay sesión, redirigir al dashboard
if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Por favor, complete todos los campos.';
    } else {

        // 🔒 Consulta preparada
        $sql = "SELECT id_usuario, nombre_completo, usuario, contrasena, rol, estado
                FROM usuarios
                WHERE usuario = ?
                LIMIT 1";

        $stmt = mysqli_prepare($con, $sql);

        if (!$stmt) {
            $error = 'Error interno al preparar la consulta.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $usuario);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if (!$resultado) {
                $error = 'Error al consultar la base de datos.';
            } elseif (mysqli_num_rows($resultado) === 0) {
                $error = 'Usuario o contraseña incorrectos.';
            } else {
                $usuario_db = mysqli_fetch_assoc($resultado);

                if ((int) $usuario_db['estado'] !== 1) {
                    $error = 'El usuario se encuentra inactivo.';
                } elseif (password_verify($password, $usuario_db['contrasena'])) {

                    // Regenerar el ID de sesión por seguridad
                    session_regenerate_id(true);

                    $_SESSION['id_usuario'] = $usuario_db['id_usuario'];
                    $_SESSION['nombre']     = $usuario_db['nombre_completo'];
                    $_SESSION['usuario']    = $usuario_db['usuario'];
                    $_SESSION['rol']        = $usuario_db['rol'];

                    // Marca de tiempo para expiración por inactividad
                    $_SESSION['ultima_actividad'] = time();

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | SisClinic</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-card">

            <div class="login-logo">

                <div class="login-icon">
                    <img src="/SISClinic/img/logo.png" alt="Icono de pulso" width="35" height="35">
                </div>

                <h1>SisClinic</h1>
                <p>Gestión clínica de fisioterapia</p>

            </div>

            <div class="login-title">
                <h2>Iniciar sesión</h2>
                <p>Acceda al sistema con sus credenciales</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger login-alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>

                        <input type="text" class="form-control" id="usuario" name="usuario"
                               placeholder="Ingrese su usuario"
                               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                               autocomplete="username" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Ingrese su contraseña"
                               autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 login-button">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Iniciar sesión
                </button>

            </form>

            <div class="login-footer">
                <p>SISClinic © <?= date('Y') ?></p>
                <small>Sistema interno para profesionales de fisioterapia</small>
            </div>

        </div>

    </div>

</body>

</html>