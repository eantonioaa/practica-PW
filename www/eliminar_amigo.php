<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$id_amigo = mysqli_real_escape_string($conexion, $_GET['id']);

mysqli_query($conexion, "DELETE FROM amigos WHERE id_usuario = $id_usuario AND id_usuario_amigo = $id_amigo");

// Redirigir a donde vino
$referer = $_SERVER['HTTP_REFERER'] ?? 'amigos.php';
header("Location: " . $referer);
exit();
?>