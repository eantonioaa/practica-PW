<?php
include('db.php');

if (isset($_POST['id_provincia'])) {
    $id_provincia = mysqli_real_escape_string($conexion, $_POST['id_provincia']);
    
    $sql = "SELECT * FROM MUNICIPIOS WHERE idProvincia = '$id_provincia' ORDER BY Municipio";
    $resultado = mysqli_query($conexion, $sql);
    
    echo '<option value="">-- Selecciona localidad --</option>';
    while($mun = mysqli_fetch_assoc($resultado)) {
        echo '<option value="' . $mun['idMunicipio'] . '">' . $mun['Municipio'] . '</option>';
    }
}
?>