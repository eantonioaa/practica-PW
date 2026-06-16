<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario      = $_SESSION['usuario_id'];
$nombre_usuario  = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['nombre_usuario'])), MB_CASE_TITLE, 'UTF-8');
$email           = mysqli_real_escape_string($conexion, $_POST['email']);
$nombre          = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['nombre'])), MB_CASE_TITLE, 'UTF-8');
$apellidos       = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['apellidos'])), MB_CASE_TITLE, 'UTF-8');
$fecha_nac       = mysqli_real_escape_string($conexion, $_POST['fecha_nacimiento']);
$id_tipo         = mysqli_real_escape_string($conexion, $_POST['id_actividad_preferida']);
$id_pais         = mysqli_real_escape_string($conexion, $_POST['id_pais']);
$id_provincia    = mysqli_real_escape_string($conexion, $_POST['id_provincia']);
$id_municipio    = mysqli_real_escape_string($conexion, $_POST['id_municipio']);

// Gestionar foto de perfil
$id_imagen_perfil_sql = "";
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
    $nombre_foto = time() . "_" . $_FILES['foto_perfil']['name'];
    $carpeta = "uploads/fotos/";

    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $carpeta . $nombre_foto)) {
        // Insertar en imagenes
        $sql_img = "INSERT INTO imagenes (nombre, ruta, id_usuario) 
                    VALUES ('$nombre_foto', '$carpeta$nombre_foto', $id_usuario)";
        mysqli_query($conexion, $sql_img);
        $id_imagen_perfil = mysqli_insert_id($conexion);
        $id_imagen_perfil_sql = ", id_imagen_perfil = $id_imagen_perfil";

        $_SESSION['usuario_foto'] = $carpeta . $nombre_foto;
    }
}

// Actualizar datos
$sql = "UPDATE usuarios SET 
        nombre_usuario = '$nombre_usuario',
        email = '$email',
        nombre = '$nombre',
        apellidos = '$apellidos',
        fecha_nacimiento = '$fecha_nac',
        id_actividad_preferida = '$id_tipo',
        id_pais = '$id_pais',
        id_provincia = '$id_provincia',
        id_municipio = '$id_municipio'
        $id_imagen_perfil_sql
        WHERE id = $id_usuario";

// Actualizar contraseña solo si se introdujo una nueva
if (!empty($_POST['nueva_pass'])) {
    $nueva_pass = password_hash($_POST['nueva_pass'], PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET 
            nombre_usuario = '$nombre_usuario',
            email = '$email',
            nombre = '$nombre',
            apellidos = '$apellidos',
            fecha_nacimiento = '$fecha_nac',
            id_actividad_preferida = '$id_tipo',
            id_pais = '$id_pais',
            id_provincia = '$id_provincia',
            id_municipio = '$id_municipio',
            password = '$nueva_pass'
            $id_imagen_perfil_sql
            WHERE id = $id_usuario";
}

if (mysqli_query($conexion, $sql)) {
    $_SESSION['usuario_nombre'] = $nombre_usuario;
    header("Location: perfil.php?msg=ok");
    exit();
} else {
    echo "Error al actualizar: " . mysqli_error($conexion);
}

mysqli_close($conexion);
?>