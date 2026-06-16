<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

mysqli_query($conexion, "UPDATE usuarios SET fecha_baja = NOW() WHERE id = $id_usuario");

session_destroy();
header("Location: index.php");
exit();
?>