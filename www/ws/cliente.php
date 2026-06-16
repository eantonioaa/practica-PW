<?php
require_once('lib/nusoap.php');
error_reporting(E_ALL & ~E_DEPRECATED);

$client = new nusoap_client('http://host.docker.internal:8082/ws/servidor.php?wsdl', 'wsdl');
$client->soap_defencoding = 'UTF-8';
$client->decode_utf8 = FALSE;

$err = $client->getError();
$resultados = [];
$buscado = false;

if (isset($_POST['buscar'])) {
    $buscado = true;
    $parameters = array(
        'nombre'    => $_POST['nombre'] ?? '',
        'apellidos' => $_POST['apellidos'] ?? ''
    );

    $result = $client->call('ObtenerUsuarios', $parameters);

    if (!$client->fault && !$client->getError()) {
        $resultados = $result ?? [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscador de Usuarios - SportRoute WS</title>
    <link rel="stylesheet" href="../main.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../index.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="../index.php">Volver a SportRoute</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="header-seccion">
        <h2>🔍 Buscador de Usuarios</h2>
        <p>Búsqueda mediante Servicio Web SOAP</p>
    </div>

    <div class="activity-card">
        <form method="POST">
            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Nombre</label>
                    <input type="text" name="nombre" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                           placeholder="Ej: Juan">
                </div>
                <div class="col">
                    <label class="card-perfil-label">Apellidos</label>
                    <input type="text" name="apellidos" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>"
                           placeholder="Ej: García">
                </div>
            </div>
            <div style="margin-top:15px;">
                <button type="submit" name="buscar" class="btn-active">🔍 Buscar</button>
            </div>
        </form>
    </div>

    <?php if ($buscado): ?>
        <div class="activity-card" style="margin-top:20px;">
            <h3>Resultados</h3>
            <?php if (!empty($resultados)): ?>
                <div class="tabla-admin">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellidos</th>
                                <th>Tipo de Actividad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Si solo hay un resultado NuSoap lo devuelve como array simple
                            if (isset($resultados['nombre'])) {
                                $resultados = [$resultados];
                            }
                            foreach($resultados as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($u['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($u['tipo_actividad']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="texto-gris">No se encontraron usuarios.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>