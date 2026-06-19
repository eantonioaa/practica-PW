<?php
include('control.php');

// Búsqueda
$busqueda = '';
$where = '';
if (isset($_POST['buscar'])) {
    $busqueda = mysqli_real_escape_string($conexion, $_POST['busqueda']);
    $where = "WHERE u.nombre LIKE '%$busqueda%' OR u.apellidos LIKE '%$busqueda%' OR u.nombre_usuario LIKE '%$busqueda%'";
}

// Baja de usuario
if (isset($_GET['baja'])) {
    $id_baja = intval($_GET['baja']);
    mysqli_query($conexion, "UPDATE usuarios SET fecha_baja = NOW() WHERE id = $id_baja");
    header("Location: usuarios.php");
    exit();
}

// Reactivar usuario
if (isset($_GET['activar'])) {
    $id_activar = intval($_GET['activar']);
    mysqli_query($conexion, "UPDATE usuarios SET fecha_baja = NULL WHERE id = $id_activar");
    header("Location: usuarios.php");
    exit();
}

$sql = "SELECT u.*, ta.nombre as tipo_actividad, r.nombre as rol_nombre
        FROM usuarios u
        LEFT JOIN tipos_actividad ta ON u.id_actividad_preferida = ta.id
        LEFT JOIN roles r ON u.id_rol = r.codigo
        $where
        ORDER BY u.fecha_registro DESC";

$resultado = mysqli_query($conexion, $sql);
$usuarios = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin</title>
    <link rel="stylesheet" href="../main.css">
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
        <h2>👥 Gestión de Usuarios</h2>
        <p>Busca, edita o da de baja usuarios</p>
    </div>

    <!-- Buscador -->
    <form method="POST" class="admin-buscador">
        <input type="text" name="busqueda" class="buscador-input" 
               value="<?php echo htmlspecialchars($busqueda); ?>"
               placeholder="Buscar por nombre, apellidos o usuario...">
        <button type="submit" name="buscar" class="btn-buscar" style="width:auto; padding:10px 25px;">Buscar</button>
        <?php if ($busqueda): ?>
            <a href="usuarios.php" class="btn-secundario" style="padding:10px 20px;">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Tabla de usuarios -->
    <div class="tabla-admin">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Actividad</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                    <tr class="<?php echo $u['fecha_baja'] ? 'fila-baja' : ''; ?>">
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['nombre_usuario']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['rol_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($u['tipo_actividad'] ?? '-'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
                        <td>
                            <?php if ($u['fecha_baja']): ?>
                                <span class="badge-baja">Baja</span>
                            <?php else: ?>
                                <span class="badge-activo">Activo</span>
                            <?php endif; ?>
                        </td>
                        <td class="acciones-tabla">
                            <a href="editar_usuario.php?id=<?php echo $u['id']; ?>" class="btn-perfil">Editar</a>
                            <?php if ($u['fecha_baja']): ?>
                                <a href="?activar=<?php echo $u['id']; ?>" class="btn-perfil"
                                   onclick="return confirm('¿Reactivar este usuario?')">Activar</a>
                            <?php else: ?>
                                <a href="?baja=<?php echo $u['id']; ?>" class="btn-eliminar"
                                   onclick="return confirm('¿Dar de baja a <?php echo $u['nombre_usuario']; ?>?')">Baja</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>