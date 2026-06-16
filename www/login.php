<?php
session_start();

include('db.php');

$email = mysqli_real_escape_string($conexion, $_POST['email_usuario']);
$password = $_POST['pass_usuario'];

$sql = "SELECT * FROM usuarios WHERE email = '$email'";
$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0) {

    $fila = mysqli_fetch_assoc($resultado);

    if ($fila['fecha_baja'] !== null) {
        header("Location: index.php?error=baja");
        exit();
    }
    
    if (password_verify($password, $fila['password'])){
        
        $id_foto = $fila['id_imagen_perfil'];
        $url_foto = null;
        if ($id_foto) {
            $res_foto = mysqli_query($conexion, "SELECT ruta FROM imagenes WHERE id = $id_foto");
            $foto_data = mysqli_fetch_assoc($res_foto);
            $url_foto = $foto_data['ruta'] ?? null;
        }

        $_SESSION['usuario_id'] = $fila['id'];
        $_SESSION['usuario_nombre'] = $fila['nombre_usuario'];
        $_SESSION['usuario_foto']   = $url_foto;
        $_SESSION['usuario_rol'] = $fila['id_rol'];

        header("Location: principal.php");
        exit();
        
    } else {

        header("Location: index.php?error=clave");
        exit();
    }
} else {

    header("Location: index.php?error=email");
    exit();
} 
?> 
