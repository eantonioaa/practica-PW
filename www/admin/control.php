<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

$sql_rol = "SELECT id_rol FROM usuarios WHERE id = {$_SESSION['usuario_id']}";
$res_rol = mysqli_query($conexion, $sql_rol);
$datos_rol = mysqli_fetch_assoc($res_rol);

if ($datos_rol['id_rol'] != 1) {
    header("Location: ../principal.php");
    exit();
}

$nombre = $_SESSION['usuario_nombre'];
$inicial = strtoupper(substr($nombre, 0, 1));
$url_foto = $_SESSION['usuario_foto'] ?? null;

if (empty($url_foto)) {
    $id = $_SESSION['usuario_id'];
    $res = mysqli_query($conexion, "SELECT i.ruta FROM usuarios u 
                                    JOIN imagenes i ON u.id_imagen_perfil = i.id 
                                    WHERE u.id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $foto = mysqli_fetch_assoc($res);
        $_SESSION['usuario_foto'] = $foto['ruta'];
        $url_foto = $foto['ruta'];
    }
}
?>