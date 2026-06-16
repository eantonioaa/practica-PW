<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$mi_id = $_SESSION['usuario_id'];
$inicial = strtoupper(substr($_SESSION['usuario_nombre'], 0, 1));
$id_actividad = mysqli_real_escape_string($conexion, $_GET['id']);

// Obtener actividad
$sql = "SELECT a.*, u.nombre_usuario as autor, u.id as id_autor, u.id_imagen_perfil, ta.nombre as tipo_actividad, r.archivo_gpx
        FROM actividades a
        JOIN usuarios u ON a.id_usuario = u.id
        JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
        LEFT JOIN rutas r ON a.id_ruta = r.id
        WHERE a.id = '$id_actividad'";
$resultado = mysqli_query($conexion, $sql);
$actividad = mysqli_fetch_assoc($resultado);

if (!$actividad) {
    header("Location: explorar.php");
    exit();
}

$foto_autor = null;
if ($actividad['id_imagen_perfil'] ?? null) {
    $res_foto_autor = mysqli_query($conexion, "SELECT ruta FROM imagenes WHERE id = {$actividad['id_imagen_perfil']}");
    $foto_autor_data = mysqli_fetch_assoc($res_foto_autor);
    $foto_autor = $foto_autor_data['ruta'] ?? null;
}

// Obtener compañeros
$res_comp = mysqli_query($conexion, "SELECT u.nombre_usuario, u.id FROM actividad_compañeros ac
            JOIN usuarios u ON ac.id_usuario_companero = u.id
            WHERE ac.id_actividad = $id_actividad");
$companeros = mysqli_fetch_all($res_comp, MYSQLI_ASSOC);

// Obtener imágenes
$res_imgs = mysqli_query($conexion, "SELECT i.ruta, i.nombre FROM actividad_imagenes ai
            JOIN imagenes i ON ai.id_imagen = i.id
            WHERE ai.id_actividad = $id_actividad");
$imagenes = mysqli_fetch_all($res_imgs, MYSQLI_ASSOC);

// Obtener aplausos
$res_aplausos = mysqli_query($conexion, "SELECT u.id, u.nombre_usuario FROM actividad_aplausos aa
                JOIN usuarios u ON aa.id_usuario_aplauso = u.id
                WHERE aa.id_actividad = $id_actividad");

$aplausos = mysqli_fetch_all($res_aplausos, MYSQLI_ASSOC);
$total_aplausos = count($aplausos);

$check_aplauso = mysqli_query($conexion, "SELECT * FROM actividad_aplausos WHERE id_actividad = $id_actividad AND id_usuario_aplauso = $mi_id");
$ya_aplaudio = mysqli_num_rows($check_aplauso) > 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($actividad['titulo']); ?> - SportRoute</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <script src="leaflet/leaflet.js"></script>
    <script src="leaflet/gpx/gpx.js"></script>
    <script src="jquery-3.6.3.min.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="principal.php">Inicio</a></li>
            <li><a href="explorar.php" class="active">Explorar</a></li>
            <li><a href="amigos.php">Amigos</a></li>
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

    <div class="activity-card" style="margin-top:30px;">
        
        <!-- Header -->
        <div class="card-header">
            <div class="user-avatar-small">
                <?php if ($foto_autor): ?>
                    <img src="<?php echo htmlspecialchars($foto_autor); ?>" class="avatar-foto">
                <?php else: ?>
                    <?php echo strtoupper(substr($actividad['autor'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="user-meta">
                <h4>
                    <a href="perfil_amigo.php?id=<?php echo $actividad['id_autor']; ?>" 
                       style="text-decoration:none; color:inherit;">
                        <?php echo htmlspecialchars($actividad['autor']); ?>
                    </a>
                </h4>
                <span><?php echo htmlspecialchars($actividad['tipo_actividad']); ?> · 
                      <?php echo date('d/m/Y', strtotime($actividad['fecha_publicacion'])); ?>
                </span>
            </div>
        </div>

        <h2><?php echo htmlspecialchars($actividad['titulo']); ?></h2>
        <span class="tipo-tag"><?php echo htmlspecialchars($actividad['tipo_actividad']); ?></span>

        <!-- Mapa -->
        <?php if ($actividad['archivo_gpx']): ?>
            <div id="mapa" class="mapa-container"></div>
            <div class="route-stats">
                <div class="stat"><strong id="stat-dist">--</strong><span>KM</span></div>
                <div class="stat"><strong id="stat-tiempo">--</strong><span>TIEMPO</span></div>
                <div class="stat"><strong><?php echo $total_aplausos; ?></strong><span>APLAUSOS</span></div>
            </div>
        <?php endif; ?>

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

        <!-- Aplausos -->
        <div class="actividad-seccion">
            <button class="btn-aplauso <?php echo $ya_aplaudio ? 'aplauso-activo' : ''; ?>"
                    onclick="darAplauso(this, <?php echo $id_actividad; ?>)">
                👏 <span class="count"><?php echo $total_aplausos; ?></span> Aplausos
            </button>
            <?php if (!empty($aplausos)): ?>
                <div class="aplausos-lista">
                    <?php foreach($aplausos as $ap): ?>
                        <a href="perfil_amigo.php?id=<?php echo $ap['id']; ?>" class="friend-tag">
                            👤 <?php echo htmlspecialchars($ap['nombre_usuario']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="perfil-amigo-volver">
        <a href="explorar.php" class="btn-secundario">⬅️ Volver a explorar</a>
    </div>

</div>

<?php if ($actividad['archivo_gpx']): ?>
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