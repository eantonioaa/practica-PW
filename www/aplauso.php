<?php
session_start();
include('db.php');

if (isset($_POST['id_actividad']) && isset($_SESSION['usuario_id'])) {
    $id_actividad = intval($_POST['id_actividad']);
    $id_usuario = intval($_SESSION['usuario_id']);

    $check = mysqli_query($conexion, "SELECT * FROM actividad_aplausos WHERE id_actividad = $id_actividad AND id_usuario_aplauso = $id_usuario");

    if (mysqli_num_rows($check) == 0) {

        mysqli_query($conexion, "INSERT INTO actividad_aplausos (id_actividad, id_usuario_aplauso) VALUES ($id_actividad, $id_usuario)");
    } else {

        mysqli_query($conexion, "DELETE FROM actividad_aplausos WHERE id_actividad = $id_actividad AND id_usuario_aplauso = $id_usuario");
    }

    $res_total = mysqli_query($conexion, "SELECT COUNT(*) as total FROM actividad_aplausos WHERE id_actividad = $id_actividad");
    $datos = mysqli_fetch_assoc($res_total);
    echo $datos['total'];
}
?>