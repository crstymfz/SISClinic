<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/SISClinic');
}

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$nombre_usuario = $_SESSION['nombre'] ?? 'Profesional';
$rol_usuario = $_SESSION['rol'] ?? 'Fisioterapeuta';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SisClinic</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
</head>

<body>