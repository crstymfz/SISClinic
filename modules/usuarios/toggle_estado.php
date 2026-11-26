<?php
// modules/usuarios/toggle_estado.php
// Activa o desactiva un usuario (solo Administrador).

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

// 🔒 Solo por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash'] = [
        'type'    => 'warning',
        'message' => 'Acción no permitida.'
    ];
    header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
    exit;
}

// Recoger ID
$id_usuario = (int) ($_POST['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'Usuario no especificado.'
    ];
    header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
    exit;
}

// 🔒 No se puede desactivar a sí mismo
if ($id_usuario === (int) $_SESSION['id_usuario']) {
    $_SESSION['flash'] = [
        'type'    => 'warning',
        'message' => 'No puede cambiar el estado de su propia cuenta.'
    ];
    header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
    exit;
}

// Buscar usuario
$stmt = mysqli_prepare($con, "SELECT id_usuario, nombre_completo, estado FROM usuarios WHERE id_usuario = ? LIMIT 1");
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

// Invertir estado: 1 → 0, 0 → 1
$nuevo_estado = ((int) $usuario['estado'] === 1) ? 0 : 1;

$stmt = mysqli_prepare($con, "UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, 'ii', $nuevo_estado, $id_usuario);

if (mysqli_stmt_execute($stmt)) {
    $accion = $nuevo_estado === 1 ? 'activado' : 'desactivado';

    $_SESSION['flash'] = [
        'type'    => 'success',
        'message' => 'Usuario "' . $usuario['nombre_completo'] . '" ' . $accion . ' correctamente.'
    ];
} else {
    $_SESSION['flash'] = [
        'type'    => 'danger',
        'message' => 'Error al cambiar el estado del usuario.'
    ];
}

mysqli_stmt_close($stmt);

// Redirigir al listado
header('Location: ' . BASE_URL . '/modules/usuarios/index.php');
exit;