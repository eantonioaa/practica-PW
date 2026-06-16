<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: index.php");
    exit();
}

$nombre_usuario = $_SESSION['usuario_nombre'];
$id_usuario = $_SESSION['usuario_id'];
$inicial = strtoupper(substr($nombre_usuario, 0, 1));
$url_foto = $_SESSION['usuario_foto'] ?? null;

// Paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Total de actividades de amigos
$sql_total = "SELECT COUNT(*) as total FROM actividades a
              JOIN amigos am ON a.id_usuario = am.id_usuario_amigo
              WHERE am.id_usuario = $id_usuario";
$res_total = mysqli_query($conexion, $sql_total);
$total = mysqli_fetch_assoc($res_total)['total'];
$total_paginas = ceil($total / $por_pagina);

// Mis últimas actividades
$sql_mias = "SELECT a.*, ta.nombre as tipo_actividad, r.archivo_gpx,
             (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id) as total_aplausos
             FROM actividades a
             JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
             LEFT JOIN rutas r ON a.id_ruta = r.id
             WHERE a.id_usuario = $id_usuario
             ORDER BY a.fecha_publicacion DESC
             LIMIT 3";

$res_mias = mysqli_query($conexion, $sql_mias);
$mis_actividades = mysqli_fetch_all($res_mias, MYSQLI_ASSOC);

// Actividades de amigos
$sql = "SELECT a.*, u.nombre_usuario as autor, u.id as id_autor, u.id_imagen_perfil,
        ta.nombre as tipo_actividad, r.archivo_gpx,
        (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id) as total_aplausos,
        (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id AND id_usuario_aplauso = $id_usuario) as le_he_dado
        FROM actividades a
        JOIN usuarios u ON a.id_usuario = u.id
        JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
        LEFT JOIN rutas r ON a.id_ruta = r.id
        JOIN amigos am ON a.id_usuario = am.id_usuario_amigo
        WHERE am.id_usuario = $id_usuario
        ORDER BY a.fecha_publicacion DESC
        LIMIT $por_pagina OFFSET $offset";

$resultado = mysqli_query($conexion, $sql);
$actividades = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SportRoute - Inicio</title>
    <script src="jquery-3.6.3.min.js"></script>
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <script src="leaflet/leaflet.js"></script>
    <script src="leaflet/gpx/gpx.js"></script>
    <link rel="stylesheet" href="main.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="principal.php" class="active">Inicio</a></li>
            <li><a href="explorar.php">Explorar</a></li>
            <li><a href="amigos.php">Amigos</a></li>
            <li class="user-menu-item">
                <div class="user-dropdown">
                    <button class="user-avatar-btn">
                        <?php if (!empty($url_foto)): ?>
                            <img src="<?php echo $url_foto; ?>" 
                                 style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?php echo $inicial; ?>
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

<header class="hero-mini">
    <div class="container">
        <h1>Tablón de actividades</h1>
        <p>¡Hola, <?php echo htmlspecialchars($nombre_usuario); ?>! Aquí están las últimas actividades de tus amigos.</p>
    </div>
</header>

<main class="container">

    <!-- Mis actividades -->
    <?php if (!empty($mis_actividades)): ?>
        <h2 class="seccion-titulo">🏃 Mis últimas actividades</h2>
        <?php foreach($mis_actividades as $act): ?>
            <?php $mapId = "mapa_" . $act['id']; ?>
            <div class="activity-card">
                <div class="card-header">
                    <div class="user-avatar-small">
                        <?php if (!empty($url_foto)): ?>
                            <img src="<?php echo $url_foto; ?>" class="avatar-foto">
                        <?php else: ?>
                            <?php echo $inicial; ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-meta">
                        <h4><?php echo htmlspecialchars($nombre_usuario); ?></h4>
                        <span><?php echo htmlspecialchars($act['tipo_actividad']); ?> · 
                            <?php echo date('d/m/Y', strtotime($act['fecha_publicacion'])); ?>
                        </span>
                    </div>
                </div>

                <h3><?php echo htmlspecialchars($act['titulo']); ?></h3>
                <span class="tipo-tag"><?php echo htmlspecialchars($act['tipo_actividad']); ?></span>

                <?php if ($act['archivo_gpx']): ?>
                    <div id="<?php echo $mapId; ?>" class="mapa-container"
                        data-gpx="uploads/gpx/<?php echo $act['archivo_gpx']; ?>"></div>
                <?php endif; ?>

                <div class="route-stats">
                    <div class="stat"><strong class="dist-<?php echo $act['id']; ?>">--</strong><span>KM</span></div>
                    <div class="stat"><strong class="tiempo-<?php echo $act['id']; ?>">--</strong><span>TIEMPO</span></div>
                    <div class="stat"><strong><?php echo $act['total_aplausos']; ?></strong><span>APLAUSOS</span></div>
                </div>

                <div class="actividad-seccion" style="display:flex; justify-content:flex-end;">
                    <a href="detalle_actividad.php?id=<?php echo $act['id']; ?>" class="btn-perfil">Ver detalle</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <hr class="divider" style="margin: 30px 0;">
    <h2 class="seccion-titulo">📰 Tablón de actividades</h2>

    <?php if (empty($actividades)): ?>
        <div class="activity-card" style="text-align:center; padding:40px;">
            <p style="color:var(--texto-gris);">Aún no hay actividades. ¡Sigue a otros usuarios para ver sus rutas!</p>
            <a href="amigos.php" class="btn-active" style="margin-top:15px; display:inline-block; padding:12px 25px;">
                🔍 Buscar usuarios
            </a>
        </div>
    <?php else: ?>

        <?php foreach($actividades as $act): ?>
            <?php
            $mapId = "mapa_" . $act['id'];
            $clase_activo = ($act['le_he_dado'] ?? 0) > 0 ? 'aplauso-activo' : '';

            // Foto del autor
            $foto_autor = null;
            if ($act['id_imagen_perfil']) {
                $res_foto = mysqli_query($conexion, "SELECT ruta FROM imagenes WHERE id = {$act['id_imagen_perfil']}");
                $foto_data = mysqli_fetch_assoc($res_foto);
                $foto_autor = $foto_data['ruta'] ?? null;
            }

            // Compañeros
            $res_comp = mysqli_query($conexion, "SELECT u.nombre_usuario, u.id FROM actividad_compañeros ac
                        JOIN usuarios u ON ac.id_usuario_companero = u.id
                        WHERE ac.id_actividad = {$act['id']}");
            $companeros = mysqli_fetch_all($res_comp, MYSQLI_ASSOC);

            // Imágenes
            $res_imgs = mysqli_query($conexion, "SELECT i.ruta, i.nombre FROM actividad_imagenes ai
                        JOIN imagenes i ON ai.id_imagen = i.id
                        WHERE ai.id_actividad = {$act['id']}");
            $imagenes = mysqli_fetch_all($res_imgs, MYSQLI_ASSOC);
            ?>

            <div class="activity-card">
                <div class="card-header">
                    <div class="user-avatar-small">
                        <?php if ($foto_autor): ?>
                            <img src="<?php echo htmlspecialchars($foto_autor); ?>" class="avatar-foto">
                        <?php else: ?>
                            <?php echo strtoupper(substr($act['autor'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-meta">
                        <h4>
                            <a href="perfil_amigo.php?id=<?php echo $act['id_autor']; ?>"
                               style="text-decoration:none; color:inherit;">
                                <?php echo htmlspecialchars($act['autor']); ?>
                            </a>
                        </h4>
                        <span><?php echo htmlspecialchars($act['tipo_actividad']); ?> · 
                              <?php echo date('d/m/Y', strtotime($act['fecha_publicacion'])); ?>
                        </span>
                    </div>
                </div>

                <h3><?php echo htmlspecialchars($act['titulo']); ?></h3>
                <span class="tipo-tag"><?php echo htmlspecialchars($act['tipo_actividad']); ?></span>

                <?php if ($act['archivo_gpx']): ?>
                    <div id="<?php echo $mapId; ?>" class="mapa-container"
                         data-gpx="uploads/gpx/<?php echo $act['archivo_gpx']; ?>"></div>
                <?php endif; ?>

                <div class="route-stats">
                    <div class="stat"><strong class="dist-<?php echo $act['id']; ?>">--</strong><span>KM</span></div>
                    <div class="stat"><strong class="tiempo-<?php echo $act['id']; ?>">--</strong><span>TIEMPO</span></div>
                    <div class="stat"><strong><?php echo $act['total_aplausos']; ?></strong><span>APLAUSOS</span></div>
                </div>

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

                <div class="actividad-seccion" style="display:flex; justify-content:space-between; align-items:center;">
                    <button class="btn-aplauso <?php echo $clase_activo; ?>"
                            onclick="darAplauso(this, <?php echo $act['id']; ?>)">
                        👏 <span class="count"><?php echo $act['total_aplausos']; ?></span> Aplausos
                    </button>
                    <a href="detalle_actividad.php?id=<?php echo $act['id']; ?>" class="btn-perfil">
                        Ver detalle
                    </a>
                </div>
            </div>

        <?php endforeach; ?>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="paginacion">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="btn-pagina">← Anterior</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>" 
                       class="btn-pagina <?php if($i == $pagina_actual) echo 'btn-pagina-activa'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="btn-pagina">Siguiente →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</main>

<a href="actividad.php" class="fab-add">+</a>

<script>
window.onload = function() {
    const contenedores = document.querySelectorAll('.mapa-container');
    contenedores.forEach(contenedor => {
        const mapId = contenedor.id;
        const rutaGpx = contenedor.dataset.gpx;
        const actId = mapId.replace('mapa_', '');

        if (rutaGpx && !rutaGpx.endsWith('/')) {
            const map = L.map(mapId, { scrollWheelZoom: false }).setView([35.896, -5.29], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            new L.GPX(rutaGpx, {
                async: true,
                polyline_options: { color: '#27ae60', weight: 4 },
                marker_options: {
                    startIconUrl: 'leaflet/gpx/images/pin-icon-start.png',
                    endIconUrl: 'leaflet/gpx/images/pin-icon-end.png',
                    shadowUrl: 'leaflet/gpx/images/pin-shadow.png'
                }
            }).on('loaded', function(e) {
                map.fitBounds(e.target.getBounds());
                document.querySelector('.dist-' + actId).innerText = (e.target.get_distance() / 1000).toFixed(2);
                document.querySelector('.tiempo-' + actId).innerText = e.target.get_duration_string(e.target.get_moving_time());
            }).addTo(map);
        }
    });
};

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