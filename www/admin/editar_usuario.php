<?php
include('control.php');

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: usuarios.php");
    exit();
}

// Guardar cambios
if (isset($_POST['guardar'])) {
    $nombre_usuario = mysqli_real_escape_string($conexion, $_POST['nombre_usuario']);
    $email          = mysqli_real_escape_string($conexion, $_POST['email']);
    $nombre         = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['nombre'])), MB_CASE_TITLE, 'UTF-8');
    $apellidos      = mb_convert_case(mb_strtolower(mysqli_real_escape_string($conexion, $_POST['apellidos'])), MB_CASE_TITLE, 'UTF-8');
    $fecha_nac      = mysqli_real_escape_string($conexion, $_POST['fecha_nacimiento']);
    $id_tipo        = mysqli_real_escape_string($conexion, $_POST['id_actividad_preferida']);
    $id_rol         = mysqli_real_escape_string($conexion, $_POST['id_rol']);
    $id_pais        = mysqli_real_escape_string($conexion, $_POST['id_pais']);
    $id_provincia   = mysqli_real_escape_string($conexion, $_POST['id_provincia']);
    $id_municipio   = mysqli_real_escape_string($conexion, $_POST['id_municipio']);

    $sql = "UPDATE usuarios SET
            nombre_usuario = '$nombre_usuario',
            email = '$email',
            nombre = '$nombre',
            apellidos = '$apellidos',
            fecha_nacimiento = '$fecha_nac',
            id_actividad_preferida = '$id_tipo',
            id_rol = '$id_rol',
            id_pais = '$id_pais',
            id_provincia = '$id_provincia',
            id_municipio = '$id_municipio'
            WHERE id = $id";

    if (!empty($_POST['nueva_pass'])) {
        $pass = password_hash($_POST['nueva_pass'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET
                nombre_usuario = '$nombre_usuario',
                email = '$email',
                nombre = '$nombre',
                apellidos = '$apellidos',
                fecha_nacimiento = '$fecha_nac',
                id_actividad_preferida = '$id_tipo',
                id_rol = '$id_rol',
                id_pais = '$id_pais',
                id_provincia = '$id_provincia',
                id_municipio = '$id_municipio',
                password = '$pass'
                WHERE id = $id";
    }

    mysqli_query($conexion, $sql);
    header("Location: usuarios.php?msg=ok");
    exit();
}

// Obtener datos del usuario
$sql = "SELECT u.*, ta.nombre as tipo_actividad, p.nombre as pais_nombre,
        pr.Provincia as provincia_nombre, m.Municipio as municipio_nombre
        FROM usuarios u
        LEFT JOIN tipos_actividad ta ON u.id_actividad_preferida = ta.id
        LEFT JOIN paises p ON u.id_pais = p.id
        LEFT JOIN PROVINCIAS pr ON u.id_provincia = pr.idProvincia
        LEFT JOIN MUNICIPIOS m ON u.id_municipio = m.idMunicipio
        WHERE u.id = $id";
$resultado = mysqli_query($conexion, $sql);
$u = mysqli_fetch_assoc($resultado);

if (!$u) {
    header("Location: usuarios.php");
    exit();
}

$tipos   = mysqli_query($conexion, "SELECT * FROM tipos_actividad");
$roles   = mysqli_query($conexion, "SELECT * FROM roles");
$paises  = mysqli_query($conexion, "SELECT * FROM paises ORDER BY nombre");
$provincias = mysqli_query($conexion, "SELECT * FROM PROVINCIAS WHERE id_pais = '{$u['id_pais']}' ORDER BY Provincia");
$municipios = mysqli_query($conexion, "SELECT * FROM MUNICIPIOS WHERE idProvincia = '{$u['id_provincia']}' ORDER BY Municipio");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Admin</title>
    <link rel="stylesheet" href="../main.css">
    <script src="../jquery-3.6.3.min.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="index.php">Admin</a></li>
            <li><a href="usuarios.php" class="active">Usuarios</a></li>
            <li><a href="actividades.php">Actividades</a></li>
            <li><a href="datos_auxiliares.php">Datos Auxiliares</a></li>
            <li class="user-menu-item">
                <div class="user-dropdown">
                    <button class="user-avatar-btn">
                        <?php if (!empty($url_foto)): ?>
                            <img src="../<?php echo $url_foto; ?>" style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?php echo $inicial; ?>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-content">
                        <a href="../perfil.php">👤 Mi Perfil</a>
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
        <h2>✏️ Editar Usuario</h2>
        <p>Modificando: <strong><?php echo htmlspecialchars($u['nombre_usuario']); ?></strong></p>
    </div>

    <div class="activity-card" style="max-width:650px; margin: 0 auto 40px auto;">
        <form method="POST">

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Nombre de Usuario</label>
                    <input type="text" name="nombre_usuario" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($u['nombre_usuario']); ?>" required>
                </div>
                <div class="col">
                    <label class="card-perfil-label">Rol</label>
                    <select name="id_rol" class="card-perfil-input">
                        <?php while($rol = mysqli_fetch_assoc($roles)): ?>
                            <option value="<?php echo $rol['codigo']; ?>"
                                <?php if($rol['codigo'] == $u['id_rol']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($rol['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label class="card-perfil-label">Correo Electrónico</label>
            <input type="email" name="email" class="card-perfil-input"
                   value="<?php echo htmlspecialchars($u['email']); ?>" required>

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Nombre</label>
                    <input type="text" name="nombre" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($u['nombre']); ?>">
                </div>
                <div class="col">
                    <label class="card-perfil-label">Apellidos</label>
                    <input type="text" name="apellidos" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($u['apellidos']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="card-perfil-input"
                           value="<?php echo $u['fecha_nacimiento']; ?>">
                </div>
                <div class="col">
                    <label class="card-perfil-label">Tipo de Actividad</label>
                    <select name="id_actividad_preferida" class="card-perfil-input">
                        <?php while($tipo = mysqli_fetch_assoc($tipos)): ?>
                            <option value="<?php echo $tipo['id']; ?>"
                                <?php if($tipo['id'] == $u['id_actividad_preferida']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label class="card-perfil-label">País</label>
            <select name="id_pais" id="edit-pais" class="card-perfil-input">
                <option value="">-- Selecciona país --</option>
                <?php while($pais = mysqli_fetch_assoc($paises)): ?>
                    <option value="<?php echo $pais['id']; ?>"
                        <?php if($pais['id'] == $u['id_pais']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($pais['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Provincia</label>
                    <select name="id_provincia" id="edit-provincia" class="card-perfil-input">
                        <option value="">-- Selecciona provincia --</option>
                        <?php while($prov = mysqli_fetch_assoc($provincias)): ?>
                            <option value="<?php echo $prov['idProvincia']; ?>"
                                <?php if($prov['idProvincia'] == $u['id_provincia']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($prov['Provincia']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col">
                    <label class="card-perfil-label">Localidad</label>
                    <select name="id_municipio" id="edit-municipio" class="card-perfil-input">
                        <option value="">-- Selecciona localidad --</option>
                        <?php while($mun = mysqli_fetch_assoc($municipios)): ?>
                            <option value="<?php echo $mun['idMunicipio']; ?>"
                                <?php if($mun['idMunicipio'] == $u['id_municipio']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($mun['Municipio']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label class="card-perfil-label">Nueva Contraseña</label>
            <input type="password" name="nueva_pass" class="card-perfil-input"
                   placeholder="•••••••• (dejar vacío para no cambiar)">

            <div class="form-buttons" style="margin-top:25px;">
                <a href="usuarios.php" class="btn-secundario">Cancelar</a>
                <button type="submit" name="guardar" class="btn-active">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
$('#edit-pais').change(function() {
    const id_pais = $(this).val();
    $('#edit-provincia').html('<option value="">-- Selecciona provincia --</option>');
    $('#edit-municipio').html('<option value="">-- Selecciona localidad --</option>');
    if (id_pais) {
        $.post('../get_provincias.php', { id_pais: id_pais }, function(data) {
            $('#edit-provincia').html(data);
        });
    }
});

$('#edit-provincia').change(function() {
    const id_provincia = $(this).val();
    $('#edit-municipio').html('<option value="">-- Selecciona localidad --</option>');
    if (id_provincia) {
        $.post('../get_municipios.php', { id_provincia: id_provincia }, function(data) {
            $('#edit-municipio').html(data);
        });
    }
});
</script>

</body>
</html>