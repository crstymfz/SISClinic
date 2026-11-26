
<?php

$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "sisclinic";

$con = mysqli_connect(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos
);

if (!$con) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8mb4");