<?php
session_start();
include 'db.php';

$id_usuario = mysqli_real_escape_string($conexion, $_SESSION['usuario_id']);
$titulo = mysqli_real_escape_string($conexion, $_POST['nombre_actividad']);
$id_tipo = mysqli_real_escape_string($conexion, $_POST['tipo_deporte']);

$id_ruta = null;
if (isset($_FILES['gpx_file']) && $_FILES['gpx_file']['error'] == 0) {
    $nombre_gpx = time() . "_" . $_FILES['gpx_file']['name'];
    $carpeta_destino = "uploads/gpx/";

    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    if (move_uploaded_file($_FILES['gpx_file']['tmp_name'], $carpeta_destino . $nombre_gpx)) {
        $sql_ruta = "INSERT INTO rutas (archivo_gpx) VALUES ('$nombre_gpx')";
        mysqli_query($conexion, $sql_ruta);
        $id_ruta = mysqli_insert_id($conexion);
    } else {
        die("Error: No se pudo mover el archivo GPX.");
    }
}

$sql = "INSERT INTO actividades (id_usuario, titulo, id_tipo_actividad, id_ruta) 
        VALUES ('$id_usuario', '$titulo', '$id_tipo', '$id_ruta')";

if (mysqli_query($conexion, $sql)) {
    $id_actividad = mysqli_insert_id($conexion);

    if (isset($_FILES['fotos'])) {
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['fotos']['error'][$key] == 0) {
                $nombre_foto = time() . "_" . $_FILES['fotos']['name'][$key];
                
                if (move_uploaded_file($tmp_name, "uploads/fotos/" . $nombre_foto)) {
                    
                    $sql_img = "INSERT INTO imagenes (nombre, ruta, id_usuario) VALUES ('$nombre_foto', 'uploads/fotos/$nombre_foto', '$id_usuario')";
                    mysqli_query($conexion, $sql_img);
                    $id_imagen = mysqli_insert_id($conexion);

                    // Vincular imagen a actividad
                    $sql_act_img = "INSERT INTO actividad_imagenes (id_actividad, id_imagen) VALUES ('$id_actividad', '$id_imagen')";
                    mysqli_query($conexion, $sql_act_img);
                }
            }
        }
    }

    if (isset($_POST['companeros'])) {
        foreach ($_POST['companeros'] as $id_companero) {
            $id_companero = intval($id_companero);
            $sql_comp = "INSERT INTO actividad_compañeros (id_actividad, id_usuario_companero) 
                        VALUES ($id_actividad, $id_companero)";
            if (!mysqli_query($conexion, $sql_comp)) {
                echo "Error compañero: " . mysqli_error($conexion);
            }
        }
    }

    header("Location: principal.php?msg=success");
} else {
    echo "Error al guardar: " . mysqli_error($conexion);
}

mysqli_close($conexion);
?>