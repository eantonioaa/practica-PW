<?php
include('control.php');

$seccion = $_GET['seccion'] ?? 'tipos';
$msg = $_GET['msg'] ?? '';

// ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    switch($seccion) {
        case 'tipos':
            mysqli_query($conexion, "DELETE FROM tipos_actividad WHERE id = $id");
            break;
        case 'paises':
            mysqli_query($conexion, "DELETE FROM paises WHERE id = $id");
            break;
        case 'provincias':
            mysqli_query($conexion, "DELETE FROM PROVINCIAS WHERE idProvincia = $id");
            break;
        case 'localidades':
            mysqli_query($conexion, "DELETE FROM MUNICIPIOS WHERE idMunicipio = $id");
            break;
    }
    header("Location: datos_auxiliares.php?seccion=$seccion&msg=eliminado");
    exit();
}

// GUARDAR NUEVO O EDITAR
if (isset($_POST['guardar'])) {
    switch($seccion) {
        case 'tipos':
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            if (isset($_POST['id']) && $_POST['id']) {
                $id = intval($_POST['id']);
                mysqli_query($conexion, "UPDATE tipos_actividad SET nombre = '$nombre' WHERE id = $id");
            } else {
                mysqli_query($conexion, "INSERT INTO tipos_actividad (nombre) VALUES ('$nombre')");
            }
            break;
        case 'paises':
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            if (isset($_POST['id']) && $_POST['id']) {
                $id = intval($_POST['id']);
                mysqli_query($conexion, "UPDATE paises SET nombre = '$nombre' WHERE id = $id");
            } else {
                mysqli_query($conexion, "INSERT INTO paises (nombre) VALUES ('$nombre')");
            }
            break;
        case 'provincias':
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            $id_pais = intval($_POST['id_pais']);
            if (isset($_POST['id']) && $_POST['id']) {
                $id = intval($_POST['id']);
                mysqli_query($conexion, "UPDATE PROVINCIAS SET Provincia = '$nombre', id_pais = $id_pais WHERE idProvincia = $id");
            } else {
                mysqli_query($conexion, "INSERT INTO PROVINCIAS (Provincia, id_pais) VALUES ('$nombre', $id_pais)");
            }
            break;
        case 'localidades':
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            $id_provincia = intval($_POST['id_provincia']);
            if (isset($_POST['id']) && $_POST['id']) {
                $id = intval($_POST['id']);
                mysqli_query($conexion, "UPDATE MUNICIPIOS SET Municipio = '$nombre', idProvincia = $id_provincia WHERE idMunicipio = $id");
            } else {
                mysqli_query($conexion, "INSERT INTO MUNICIPIOS (Municipio, idProvincia) VALUES ('$nombre', $id_provincia)");
            }
            break;
    }
    header("Location: datos_auxiliares.php?seccion=$seccion&msg=guardado");
    exit();
}

// Obtener item a editar
$editando = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    switch($seccion) {
        case 'tipos':
            $res = mysqli_query($conexion, "SELECT * FROM tipos_actividad WHERE id = $id");
            break;
        case 'paises':
            $res = mysqli_query($conexion, "SELECT * FROM paises WHERE id = $id");
            break;
        case 'provincias':
            $res = mysqli_query($conexion, "SELECT * FROM PROVINCIAS WHERE idProvincia = $id");
            break;
        case 'localidades':
            $res = mysqli_query($conexion, "SELECT * FROM MUNICIPIOS WHERE idMunicipio = $id");
            break;
    }
    $editando = mysqli_fetch_assoc($res);
}

// Obtener datos según sección
switch($seccion) {
    case 'tipos':
        $items = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM tipos_actividad ORDER BY nombre"), MYSQLI_ASSOC);
        break;
    case 'paises':
        $items = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM paises ORDER BY nombre"), MYSQLI_ASSOC);
        break;
    case 'provincias':
        $items = mysqli_fetch_all(mysqli_query($conexion, "SELECT pr.*, p.nombre as pais_nombre FROM PROVINCIAS pr LEFT JOIN paises p ON pr.id_pais = p.id ORDER BY pr.Provincia"), MYSQLI_ASSOC);
        break;
    case 'localidades':
        $items = mysqli_fetch_all(mysqli_query($conexion, "SELECT m.*, pr.Provincia FROM MUNICIPIOS m LEFT JOIN PROVINCIAS pr ON m.idProvincia = pr.idProvincia ORDER BY m.Municipio LIMIT 100"), MYSQLI_ASSOC);
        break;
}

$paises = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM paises ORDER BY nombre"), MYSQLI_ASSOC);
$provincias = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM PROVINCIAS ORDER BY Provincia"), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Datos Auxiliares - Admin</title>
    <link rel="stylesheet" href="../main.css">
    <script src="../jquery-3.6.3.min.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="index.php">Admin</a></li>
            <li><a href="usuarios.php">Usuarios</a></li>
            <li><a href="actividades.php">Actividades</a></li>
            <li><a href="datos_auxiliares.php" class="active">Datos Auxiliares</a></li>
            <li class="user-menu-item">
                <div class="user-dropdown">
                    <button class="user-avatar-btn">
                        <?php if (!empty($url_foto)): ?>
                            <img src="<?php echo $url_foto; ?>" style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?php echo $inicial; ?>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-content">
                        <a href="../perfil.php">👤 Mi Perfil</a>
                        <a href="index.php">⚙️ Panel Admin</a>
                        <hr>
                        <a href="../logout.php">🚪 Cerrar Sesión</a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="header-seccion">
        <h2>⚙️ Datos Auxiliares</h2>
    </div>

    <!-- Tabs de secciones -->
    <div class="tabs-admin">
        <a href="?seccion=tipos" class="tab-admin <?php if($seccion=='tipos') echo 'active'; ?>">Tipos de Actividad</a>
        <a href="?seccion=paises" class="tab-admin <?php if($seccion=='paises') echo 'active'; ?>">Países</a>
        <a href="?seccion=provincias" class="tab-admin <?php if($seccion=='provincias') echo 'active'; ?>">Provincias</a>
        <a href="?seccion=localidades" class="tab-admin <?php if($seccion=='localidades') echo 'active'; ?>">Localidades</a>
    </div>

    <?php if ($msg == 'guardado'): ?>
        <div class="alerta-exito">✅ Guardado correctamente.</div>
    <?php elseif ($msg == 'eliminado'): ?>
        <div class="alerta-exito" style="background:#f8d7da; color:#721c24;">🗑️ Eliminado correctamente.</div>
    <?php endif; ?>

    <div class="datos-aux-layout">

        <!-- Formulario alta/edición -->
        <div class="activity-card" style="flex:1; min-width:250px;">
            <h3><?php echo $editando ? '✏️ Editar' : '➕ Nuevo'; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $editando['id'] ?? $editando['idProvincia'] ?? $editando['idMunicipio'] ?? ''; ?>">

                <label class="card-perfil-label">Nombre</label>
                <input type="text" name="nombre" class="card-perfil-input" required
                       value="<?php echo htmlspecialchars($editando['nombre'] ?? $editando['Provincia'] ?? $editando['Municipio'] ?? ''); ?>">

                <?php if ($seccion == 'provincias'): ?>
                    <label class="card-perfil-label">País</label>
                    <select name="id_pais" class="card-perfil-input">
                        <option value="">-- Selecciona país --</option>
                        <?php foreach($paises as $p): ?>
                            <option value="<?php echo $p['id']; ?>"
                                <?php if(($editando['id_pais'] ?? '') == $p['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if ($seccion == 'localidades'): ?>
                    <label class="card-perfil-label">País</label>
                    <select name="id_pais_aux" id="aux-pais" class="card-perfil-input">
                        <option value="">-- Selecciona país --</option>
                        <?php foreach($paises as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="card-perfil-label">Provincia</label>
                    <select name="id_provincia" id="aux-provincia" class="card-perfil-input">
                        <option value="">-- Selecciona provincia --</option>
                        <?php foreach($provincias as $pr): ?>
                            <option value="<?php echo $pr['idProvincia']; ?>"
                                <?php if(($editando['idProvincia'] ?? '') == $pr['idProvincia']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($pr['Provincia']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <div class="form-buttons" style="margin-top:20px;">
                    <?php if ($editando): ?>
                        <a href="?seccion=<?php echo $seccion; ?>" class="btn-secundario">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" name="guardar" class="btn-active">
                        <?php echo $editando ? 'Guardar' : 'Añadir'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Listado -->
        <div class="tabla-admin" style="flex:2;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <?php if ($seccion == 'provincias'): ?>
                            <th>País</th>
                        <?php endif; ?>
                        <?php if ($seccion == 'localidades'): ?>
                            <th>Provincia</th>
                        <?php endif; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                        <?php
                        switch($seccion) {
                            case 'tipos':   $id_item = $item['id']; $nombre_item = $item['nombre']; break;
                            case 'paises':  $id_item = $item['id']; $nombre_item = $item['nombre']; break;
                            case 'provincias': $id_item = $item['idProvincia']; $nombre_item = $item['Provincia']; break;
                            case 'localidades': $id_item = $item['idMunicipio']; $nombre_item = $item['Municipio']; break;
                        }
                        ?>
                        <tr>
                            <td><?php echo $id_item; ?></td>
                            <td><?php echo htmlspecialchars($nombre_item); ?></td>
                            <?php if ($seccion == 'provincias'): ?>
                                <td><?php echo htmlspecialchars($item['pais_nombre'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <?php if ($seccion == 'localidades'): ?>
                                <td><?php echo htmlspecialchars($item['Provincia'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <td class="acciones-tabla">
                                <a href="?seccion=<?php echo $seccion; ?>&editar=<?php echo $id_item; ?>" class="btn-perfil">Editar</a>
                                <a href="?seccion=<?php echo $seccion; ?>&eliminar=<?php echo $id_item; ?>" class="btn-eliminar"
                                   onclick="return confirm('¿Eliminar este elemento?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
// AJAX País -> Provincia para localidades
$('#aux-pais').change(function() {
    const id_pais = $(this).val();
    $('#aux-provincia').html('<option value="">-- Selecciona provincia --</option>');
    if (id_pais) {
        $.post('../get_provincias.php', { id_pais: id_pais }, function(data) {
            $('#aux-provincia').html(data);
        });
    }
});
</script>

</body>
</html>