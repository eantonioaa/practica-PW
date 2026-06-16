<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$inicial = strtoupper(substr($_SESSION['usuario_nombre'], 0, 1));

$sql = "SELECT u.id, u.nombre_usuario, u.nombre, u.apellidos
        FROM amigos a
        JOIN usuarios u ON a.id_usuario_amigo = u.id
        WHERE a.id_usuario = '$id_usuario'";

$resultado = mysqli_query($conexion, $sql);

$resultados = [];
if (isset($_POST['buscar']) || isset($_GET['buscar'])) {
    $busqueda = mysqli_real_escape_string($conexion, $_POST['busqueda'] ?? $_GET['busqueda'] ?? '');
    $sql_buscar = "SELECT u.id, u.nombre_usuario, u.nombre, u.apellidos,
                   (SELECT COUNT(*) FROM amigos WHERE id_usuario = $id_usuario AND id_usuario_amigo = u.id) as es_amigo,
                   (SELECT a.titulo FROM actividades a WHERE a.id_usuario = u.id ORDER BY a.fecha_publicacion DESC LIMIT 1) as ultima_actividad
                   FROM usuarios u
                   WHERE u.id != $id_usuario
                   AND (u.nombre LIKE '%$busqueda%' OR u.apellidos LIKE '%$busqueda%')";
    $res_buscar = mysqli_query($conexion, $sql_buscar);
    $resultados = mysqli_fetch_all($res_buscar, MYSQLI_ASSOC);
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Mis Amigos - SportRoute</title>
    <link rel="stylesheet" href="main.css">
    <script src="jquery-3.6.3.min.js"></script>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="principal.php" class="logo">Sport<span>Route</span></a>
            <ul class="nav-links">
                <li><a href="principal.php" class="active">Inicio</a></li>
                <li><a href="explorar.php">Explorar</a></li>
                <li><a href="amigos.php" class="active">Amigos</a></li>
                <li class="user-menu-item">
                    <div class="user-dropdown">
                        <button class="user-avatar-btn">
                            <?php if (!empty($_SESSION['usuario_foto'])): ?>
                                <img src="<?php echo $_SESSION['usuario_foto']; ?>" 
                                    style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)); ?>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="perfil.php">👤 Mi Perfil<br></a> 
                            <?php if (($_SESSION['usuario_rol'] ?? 0) == 1): ?>
                                <a href="admin/index.php">⚙️ Panel Admin</a>
                                <hr>
                            <?php endif; ?>
                            <a href="#">⚙️ Ajustes</a>
                            <hr>
                            <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="amigos-layout">

            <!-- Lista de amigos -->
            <div class="amigos-lista">
                <div class="header-seccion">
                    <h2>Mis Amigos</h2>
                    <p>Usuarios que sigues</p>
                    <button id="btn-buscar-usuarios" class="btn-active">🔍 Buscar usuarios</button>
                </div>
                <div class="grid-amigos">
                    <?php if (mysqli_num_rows($resultado) > 0): ?>
                        <?php while($amigo = mysqli_fetch_assoc($resultado)): ?>
                            <?php 
                            $inicial_amigo = strtoupper(substr($amigo['nombre_usuario'], 0, 1));
                            
                            $url_foto_amigo = null;
                            $res_foto = mysqli_query($conexion, "SELECT i.ruta FROM usuarios u 
                                                                JOIN imagenes i ON u.id_imagen_perfil = i.id 
                                                                WHERE u.id = {$amigo['id']}");
                            if ($res_foto && mysqli_num_rows($res_foto) > 0) {
                                $foto_data = mysqli_fetch_assoc($res_foto);
                                $url_foto_amigo = $foto_data['ruta'];
                            }
                            ?>
                            <div class="card-amigo">
                                <div class="avatar-grande">
                                    <?php if ($url_foto_amigo): ?>
                                        <img src="<?php echo htmlspecialchars($url_foto_amigo); ?>" class="avatar-foto" style="width:70px;height:70px;">
                                    <?php else: ?>
                                        <?php echo $inicial_amigo; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="amigo-info">
                                    <h4><?php echo htmlspecialchars($amigo['nombre_usuario']); ?></h4>
                                    <span><?php echo htmlspecialchars($amigo['nombre'] . ' ' . $amigo['apellidos']); ?></span>
                                </div>
                                <div class="card-amigo-botones">
                                    <a href="perfil_amigo.php?id=<?php echo $amigo['id']; ?>" class="btn-perfil">Ver Perfil</a>
                                    <a href="eliminar_amigo.php?id=<?php echo $amigo['id']; ?>&busqueda=<?php echo urlencode($_POST['busqueda'] ?? $_GET['busqueda'] ?? ''); ?>" 
                                    class="btn-eliminar"
                                    onclick="return confirm('¿Dejar de seguir?')">Dejar de seguir</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="texto-gris">Aún no sigues a ningún usuario. ¡Búscalos!</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel buscador -->
             <?php echo "<!-- ANTES DEL PANEL -->"; ?>
            <div id="panel-buscador" class="panel-buscador">
                <div class="activity-card">
                    <h3>🔍 Buscar usuarios</h3>
                    <form method="POST">
                        <input type="hidden" name="buscar" value="1">
                        <input type="text" name="busqueda" class="buscador-input"
                            value="<?php echo htmlspecialchars($_POST['busqueda'] ?? $_GET['busqueda'] ?? ''); ?>"
                            placeholder="Nombre o apellidos...">
                        <button type="submit" class="btn-buscar">Buscar</button>
                    </form>

                    <div id="resultados-busqueda">
                        <?php if (isset($_POST['buscar']) || isset($_GET['buscar'])): ?>
                            <?php if (!empty($resultados)): ?>
                                <?php foreach($resultados as $user): ?>
                                    <?php $ini = strtoupper(substr($user['nombre_usuario'], 0, 1)); ?>
                                    
                                    <?php if ($user['es_amigo']): ?>
                                        <!-- Si es amigo: mostrar todo -->
                                        <div class="card-amigo">
                                            <div class="avatar-grande"><?php echo $ini; ?></div>
                                            <div class="amigo-info">
                                                <h4><?php echo htmlspecialchars($user['nombre_usuario']); ?></h4>
                                                <span><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></span>
                                                <span class="tag-siguiendo">✅ Ya le sigues</span>
                                            </div>
                                            <div class="card-amigo-botones">
                                                <a href="perfil_amigo.php?id=<?php echo $user['id']; ?>" class="btn-perfil">Ver Perfil</a>
                                                <a href="eliminar_amigo.php?id=<?php echo $user['id']; ?>" class="btn-eliminar"
                                                onclick="return confirm('¿Dejar de seguir?')">Dejar de seguir</a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Si NO es amigo: solo foto, nombre, apellidos y última actividad -->
                                        <div class="card-amigo">
                                            <div class="avatar-grande"><?php echo $ini; ?></div>
                                            <div class="amigo-info">
                                                <h4><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></h4>
                                                <?php if ($user['ultima_actividad']): ?>
                                                    <span>Última actividad: <?php echo htmlspecialchars($user['ultima_actividad']); ?></span>
                                                <?php else: ?>
                                                    <span>Sin actividades recientes</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-amigo-botones">
                                                <a href="añadir_amigo.php?id=<?php echo $user['id']; ?>&busqueda=<?php echo urlencode($_POST['busqueda'] ?? ''); ?>" class="btn-perfil">+ Seguir</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="texto-gris">No se encontraron usuarios.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('buscar') || <?php echo (isset($_POST['buscar']) ? 'true' : 'false'); ?>) {
        document.getElementById('panel-buscador').classList.add('visible');
    }

    document.getElementById('btn-buscar-usuarios').addEventListener('click', function() {
        document.getElementById('panel-buscador').classList.toggle('visible');
    });
});
</script>
</body>
</html>