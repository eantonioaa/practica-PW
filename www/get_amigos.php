<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) exit();

$id_usuario = $_SESSION['usuario_id'];
$busqueda = mysqli_real_escape_string($conexion, $_GET['q'] ?? '');

$sql = "SELECT u.id, u.nombre_usuario FROM amigos a
        JOIN usuarios u ON a.id_usuario_amigo = u.id
        WHERE a.id_usuario = $id_usuario
        AND u.nombre_usuario LIKE '%$busqueda%'";

$resultado = mysqli_query($conexion, $sql);
$amigos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($amigos);
?>