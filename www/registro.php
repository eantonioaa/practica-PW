<?php
session_start();
include('db.php');

$nombre_usuario = mysqli_real_escape_string($conexion, $_POST['nombre_usuario']);
$nombre    = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['nombre'])), MB_CASE_TITLE, 'UTF-8');
$apellidos = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['apellidos'])), MB_CASE_TITLE, 'UTF-8');
$email     = mysqli_real_escape_string($conexion, $_POST['email_usuario']);
$fecha     = mysqli_real_escape_string($conexion, $_POST['fecha_nacimiento']);
$deporte   = mysqli_real_escape_string($conexion, $_POST['deporte_favorito']);
$id_pais      = mysqli_real_escape_string($conexion, $_POST['id_pais']);
$id_provincia = mysqli_real_escape_string($conexion, $_POST['id_provincia']);
$id_municipio = mysqli_real_escape_string($conexion, $_POST['id_municipio']);
$password  = password_hash($_POST['pass'], PASSWORD_DEFAULT); 


$sql = "INSERT INTO usuarios (nombre_usuario, nombre, apellidos, email, password, fecha_nacimiento, id_actividad_preferida, id_pais, id_provincia, id_municipio, id_rol) 
VALUES ('$nombre_usuario', '$nombre', '$apellidos', '$email', '$password', '$fecha', '$deporte', '$id_pais', '$id_provincia', '$id_municipio', 2)";

if (mysqli_query($conexion, $sql)) {
    
    $id_nuevo_usuario = mysqli_insert_id($conexion);

    $_SESSION['usuario_id']     = $id_nuevo_usuario; 
    $_SESSION['usuario_nombre'] = $nombre_usuario;
    $_SESSION['usuario_deporte'] = $deporte;

    header("Location: principal.php");
    exit();
} else {
    echo "Error al registrar: " . mysqli_error($conexion);
}

mysqli_close($conexion);
?>