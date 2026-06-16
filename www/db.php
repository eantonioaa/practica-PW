<?php
$host = "db"; 
$user = "practica";
$pass = "practica";
$db   = "mi_base_de_datos";

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión: " + mysqli_connect_error());
}

if (isset($_SESSION['usuario_id']) && empty($_SESSION['usuario_foto'])) {
    $id = $_SESSION['usuario_id'];
    $res = mysqli_query($conexion, "SELECT i.ruta FROM usuarios u 
                                    JOIN imagenes i ON u.id_imagen_perfil = i.id 
                                    WHERE u.id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $foto = mysqli_fetch_assoc($res);
        $_SESSION['usuario_foto'] = $foto['ruta'];
    }
}
?>