<?php
include('control.php');

// Eliminar imagen
if (isset($_GET['eliminar_imagen'])) {
    $id_imagen = intval($_GET['eliminar_imagen']);
    
    // Obtener ruta para borrar el archivo
    $res = mysqli_query($conexion, "SELECT ruta FROM imagenes WHERE id = $id_imagen");
    $img = mysqli_fetch_assoc($res);
    
    if ($img) {
        // Borrar archivo físico
        if (file_exists('../' . $img['ruta'])) {
            unlink('../' . $img['ruta']);
        }
        // Borrar de actividad_imagenes
        mysqli_query($conexion, "DELETE FROM actividad_imagenes WHERE id_imagen = $id_imagen");
        // Borrar de imagenes
        mysqli_query($conexion, "DELETE FROM imagenes WHERE id = $id_imagen");
    }
    
    header("Location: actividades.php");
    exit();
}

// Obtener todas las actividades
$sql = "SELECT a.*, u.nombre_usuario as autor, ta.nombre as tipo_actividad
        FROM actividades a
        JOIN usuarios u ON a.id_usuario = u.id
        JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
        ORDER BY a.fecha_publicacion DESC";
$resultado = mysqli_query($conexion, $sql);
$actividades = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Actividades - Admin</title>
    <link rel="stylesheet" href="../main.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="index.php">Admin</a></li>
            <li><a href="usuarios.php">Usuarios</a></li>
            <li><a href="actividades.php" class="active">Actividades</a></li>
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
        <h2>🏃 Gestión de Actividades</h2>
        <p>Consulta todas las actividades y elimina imágenes inapropiadas</p>
    </div>

    <div class="tabla-admin" style="margin-bottom:30px;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Autor</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Imágenes</th>
                    <th>Actividades</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($actividades as $act): ?>
                    <?php
                    $res_imgs = mysqli_query($conexion, "SELECT i.id, i.ruta, i.nombre FROM actividad_imagenes ai
                                JOIN imagenes i ON ai.id_imagen = i.id
                                WHERE ai.id_actividad = {$act['id']}");
                    $imagenes = mysqli_fetch_all($res_imgs, MYSQLI_ASSOC);
                    ?>
                    <tr>
                        <td><?php echo $act['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($act['autor']); ?></strong></td>
                        <td><?php echo htmlspecialchars($act['titulo']); ?></td>
                        <td><span class="tipo-tag"><?php echo htmlspecialchars($act['tipo_actividad']); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($act['fecha_publicacion'])); ?></td>
                        <td>
                            <?php if (!empty($imagenes)): ?>
                                <div class="imagenes-admin">
                                    <?php foreach($imagenes as $img): ?>
                                        <div class="imagen-admin-item">
                                            <img src="../<?php echo htmlspecialchars($img['ruta']); ?>"
                                                 alt="<?php echo htmlspecialchars($img['nombre']); ?>"
                                                 class="imagen-admin-thumb">
                                            <a href="?eliminar_imagen=<?php echo $img['id']; ?>"
                                               class="btn-eliminar-imagen"
                                               onclick="return confirm('¿Eliminar esta imagen?')">✕</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--texto-gris); font-size:0.85rem;">Sin imágenes</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <a href="../detalle_actividad.php?id=<?php echo $act['id']; ?>" class="btn-perfil" target="_blank">Ver</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>