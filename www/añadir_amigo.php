<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$id_amigo = mysqli_real_escape_string($conexion, $_GET['id']);

// Comprobar que no existe ya
$check = mysqli_query($conexion, "SELECT * FROM amigos WHERE id_usuario = $id_usuario AND id_usuario_amigo = $id_amigo");

if (mysqli_num_rows($check) == 0) {
    mysqli_query($conexion, "INSERT INTO amigos (id_usuario, id_usuario_amigo) VALUES ($id_usuario, $id_amigo)");
}

header("Location: amigos.php?buscar=1&busqueda=" . urlencode($_GET['busqueda'] ?? ''));
exit();
?>