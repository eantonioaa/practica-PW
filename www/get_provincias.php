<?php
include('db.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "recibido id_pais: " . ($_POST['id_pais'] ?? 'NADA');

if (isset($_POST['id_pais'])) {
    $id_pais = mysqli_real_escape_string($conexion, $_POST['id_pais']);
    
    $sql = "SELECT * FROM PROVINCIAS WHERE id_pais = '$id_pais' ORDER BY Provincia";
    $resultado = mysqli_query($conexion, $sql);
    
    echo "filas: " . mysqli_num_rows($resultado);
    
    echo '<option value="">-- Selecciona provincia --</option>';
    while($prov = mysqli_fetch_assoc($resultado)) {
        echo '<option value="' . $prov['idProvincia'] . '">' . $prov['Provincia'] . '</option>';
    }
}
?>