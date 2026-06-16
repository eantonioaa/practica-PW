<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$mi_id = $_SESSION['usuario_id'];
$inicial_yo = strtoupper(substr($_SESSION['usuario_nombre'], 0, 1));

// Obtener datos del amigo
$id_amigo = mysqli_real_escape_string($conexion, $_GET['id']);

$sql = "SELECT u.*, ta.nombre as tipo_actividad, m.Municipio as municipio, pr.Provincia as provincia
        FROM usuarios u
        LEFT JOIN tipos_actividad ta ON u.id_actividad_preferida = ta.id
        LEFT JOIN MUNICIPIOS m ON u.id_municipio = m.idMunicipio
        LEFT JOIN PROVINCIAS pr ON u.id_provincia = pr.idProvincia
        WHERE u.id = '$id_amigo'";

$resultado = mysqli_query($conexion, $sql);
$amigo = mysqli_fetch_assoc($resultado);

if (!$amigo) {
    header("Location: amigos.php");
    exit();
}

$url_foto_amigo = null;
if ($amigo['id_imagen_perfil']) {
    $res_foto = mysqli_query($conexion, "SELECT ruta FROM imagenes WHERE id = {$amigo['id_imagen_perfil']}");
    $foto_data = mysqli_fetch_assoc($res_foto);
    $url_foto_amigo = $foto_data['ruta'] ?? null;
}

// Verificar que es amigo
$check = mysqli_query($conexion, "SELECT * FROM amigos WHERE id_usuario = $mi_id AND id_usuario_amigo = $id_amigo");
$es_amigo = mysqli_num_rows($check) > 0;

// Obtener última actividad
$sql_act = "SELECT a.*, ta.nombre as tipo_actividad, r.archivo_gpx,
            (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id) as total_aplausos
            FROM actividades a
            LEFT JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
            LEFT JOIN rutas r ON a.id_ruta = r.id
            WHERE a.id_usuario = $id_amigo
            ORDER BY a.fecha_publicacion DESC
            LIMIT 1";
$res_act = mysqli_query($conexion, $sql_act);
$actividad = mysqli_fetch_assoc($res_act);

$companeros = [];
$imagenes = [];

if ($actividad) {
    // Obtener compañeros de la actividad
    $sql_comp = "SELECT u.nombre_usuario, u.id FROM actividad_compañeros ac
                 JOIN usuarios u ON ac.id_usuario_companero = u.id
                 WHERE ac.id_actividad = {$actividad['id']}";
    $res_comp = mysqli_query($conexion, $sql_comp);
    $companeros = mysqli_fetch_all($res_comp, MYSQLI_ASSOC);

    // Obtener imágenes de la actividad
    $sql_imgs = "SELECT i.ruta, i.nombre FROM actividad_imagenes ai
                 JOIN imagenes i ON ai.id_imagen = i.id
                 WHERE ai.id_actividad = {$actividad['id']}";
    $res_imgs = mysqli_query($conexion, $sql_imgs);
    $imagenes = mysqli_fetch_all($res_imgs, MYSQLI_ASSOC);
}

$nombre_amigo = $amigo['nombre_usuario'];
$inicial_amigo = strtoupper(substr($nombre_amigo, 0, 1));
$fecha_registro = date('Y', strtotime($amigo['fecha_registro']));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo htmlspecialchars($nombre_amigo); ?> - SportRoute</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <script src="leaflet/leaflet.js"></script>
    <script src="leaflet/gpx/gpx.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="principal.php">Inicio</a></li>
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
                        <a href="perfil.php">👤 Mi Perfil</a>
                        <hr>
                        <?php if (($_SESSION['usuario_rol'] ?? 0) == 1): ?>
                            <a href="admin/index.php">⚙️ Panel Admin</a>
                            <hr>
                        <?php endif; ?>
                        <a href="logout.php">🚪 Cerrar Sesión</a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div class="container">

    <!-- Card perfil -->
    <div class="activity-card perfil-amigo-card">
        <div class="avatar-perfil-grande">
            <?php if ($url_foto_amigo): ?>
                <img src="<?php echo htmlspecialchars($url_foto_amigo); ?>" class="avatar-foto" onclick="abrirLightbox(this.src)">
            <?php else: ?>
                <div class="avatar-grande avatar-amigo"><?php echo $inicial_amigo; ?></div>
            <?php endif; ?>
        </div>
        <h2><?php echo htmlspecialchars($amigo['nombre'] . ' ' . $amigo['apellidos']); ?></h2>
        <p class="texto-gris">@<?php echo htmlspecialchars($nombre_amigo); ?></p>

        <div class="perfil-amigo-datos">
            <p class="texto-gris">📍 <?php echo htmlspecialchars($amigo['municipio'] . ', ' . $amigo['provincia']); ?></p>
            <p class="texto-gris">🏅 <?php echo htmlspecialchars($amigo['tipo_actividad']); ?></p>
            <p class="texto-gris">📅 Miembro desde <?php echo $fecha_registro; ?></p>
        </div>
        <?php if ($es_amigo): ?>
            <a href="eliminar_amigo.php?id=<?php echo $id_amigo; ?>" class="btn-eliminar"
               onclick="return confirm('¿Dejar de seguir a <?php echo $nombre_amigo; ?>?')">
               Dejar de seguir
            </a>
        <?php endif; ?>
    </div>

    <!-- Última actividad -->
    <?php if ($actividad): ?>
        <h3 class="seccion-titulo">📍 Última actividad de <?php echo htmlspecialchars($nombre_amigo); ?></h3>

        <div class="activity-card">
            <div class="card-header">
                <div class="user-avatar-small">
                    <?php if ($url_foto_amigo): ?>
                        <img src="<?php echo htmlspecialchars($url_foto_amigo); ?>" class="avatar-foto" onclick="abrirLightbox(this.src)">
                    <?php else: ?>
                        <?php echo $inicial_amigo; ?>
                    <?php endif; ?>
                </div>
                <div class="user-meta">
                    <h4><?php echo htmlspecialchars($nombre_amigo); ?></h4>
                    <span><?php echo htmlspecialchars($actividad['titulo']); ?> · <?php echo htmlspecialchars($actividad['tipo_actividad']); ?></span>
                    <span><?php echo date('d/m/Y', strtotime($actividad['fecha_publicacion'])); ?></span>
                </div>
            </div>

            <?php if ($actividad['archivo_gpx']): ?>
                <div id="mapa" class="mapa-container"></div>
            <?php endif; ?>

            <div class="route-stats">
                <div class="stat">
                    <strong id="stat-dist">--</strong>
                    <span>KM</span>
                </div>
                <div class="stat">
                    <strong id="stat-tiempo">--</strong>
                    <span>TIEMPO</span>
                </div>
                <div class="stat">
                    <strong><?php echo $actividad['total_aplausos']; ?></strong>
                    <span>APLAUSOS</span>
                </div>
            </div>

            <!-- Compañeros -->
            <?php if (!empty($companeros)): ?>
                <div class="actividad-seccion">
                    <h4>👥 Compañeros</h4>
                    <div class="companeros-row">
                        <?php foreach($companeros as $comp): ?>
                            <a href="perfil_amigo.php?id=<?php echo $comp['id']; ?>" class="friend-tag">
                                👤 <?php echo htmlspecialchars($comp['nombre_usuario']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Imágenes -->
            <?php if (!empty($imagenes)): ?>
                <div class="actividad-seccion">
                    <h4>📸 Imágenes</h4>
                    <div class="imagenes-grid">
                        <?php foreach($imagenes as $img): ?>
                            <img src="<?php echo htmlspecialchars($img['ruta']); ?>"
                                alt="<?php echo htmlspecialchars($img['nombre']); ?>"
                                class="actividad-imagen"
                                onclick="abrirLightbox(this.src)">
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Botón aplauso -->
            <?php
            $check_aplauso = mysqli_query($conexion, "SELECT * FROM actividad_aplausos WHERE id_actividad = {$actividad['id']} AND id_usuario_aplauso = $mi_id");
            $ya_aplaudio = mysqli_num_rows($check_aplauso) > 0;
            ?>
            <div style="margin-top:15px;">
                <button class="btn-aplauso <?php echo $ya_aplaudio ? 'aplauso-activo' : ''; ?>"
                        onclick="darAplauso(this, <?php echo $actividad['id']; ?>)">
                    👏 <span class="count"><?php echo $actividad['total_aplausos']; ?></span> Aplausos
                </button>
            </div>
        </div>
    <?php else: ?>
        <p class="texto-gris">Este usuario aún no ha publicado actividades.</p>
    <?php endif; ?>

    <div class="perfil-amigo-volver">
        <a href="amigos.php" class="btn-secundario">⬅️ Volver a mis amigos</a>
    </div>
</div>

<?php if ($actividad && $actividad['archivo_gpx']): ?>
<script>
    var map = L.map('mapa').setView([35.896, -5.29], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    new L.GPX('uploads/gpx/<?php echo $actividad['archivo_gpx']; ?>', {
        async: true,
        polyline_options: { color: '#27ae60', weight: 5 },
        marker_options: {
            startIconUrl: 'leaflet/gpx/images/pin-icon-start.png',
            endIconUrl: 'leaflet/gpx/images/pin-icon-end.png',
            shadowUrl: 'leaflet/gpx/images/pin-shadow.png'
        }
    }).on('loaded', function(e) {
        map.fitBounds(e.target.getBounds());
        document.getElementById('stat-dist').innerText = (e.target.get_distance() / 1000).toFixed(2);
        document.getElementById('stat-tiempo').innerText = e.target.get_duration_string(e.target.get_moving_time());
    }).addTo(map);
</script>
<?php endif; ?>

<script>
function darAplauso(boton, idActividad) {
    const contador = boton.querySelector('.count');
    let numeroActual = parseInt(contador.innerText);

    if (boton.classList.contains('aplauso-activo')) {
        boton.classList.remove('aplauso-activo');
        contador.innerText = numeroActual - 1;
    } else {
        boton.classList.add('aplauso-activo');
        contador.innerText = numeroActual + 1;
    }

    let datos = new FormData();
    datos.append('id_actividad', idActividad);
    fetch('aplauso.php', { method: 'POST', body: datos });
}
</script>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox" onclick="cerrarLightbox()">
    <span class="lightbox-cerrar">✕</span>
    <img src="" id="lightbox-img" class="lightbox-img">
</div>

<script>
function abrirLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarLightbox() {
    document.getElementById('lightbox').classList.remove('visible');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarLightbox();
});
</script>

</body>
</html>