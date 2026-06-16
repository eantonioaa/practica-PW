<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

include('db.php');
$mi_id = $_SESSION['usuario_id'];

$sql = "SELECT a.*, u.nombre_usuario as autor, u.id as id_autor, ta.nombre as tipo_actividad, r.archivo_gpx,
    (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id) as total_aplausos,
    (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id AND id_usuario_aplauso = $mi_id) as le_he_dado
    FROM actividades a
    JOIN usuarios u ON a.id_usuario = u.id
    JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
    LEFT JOIN rutas r ON a.id_ruta = r.id
    WHERE a.id_usuario != $mi_id
    AND a.id_usuario NOT IN (
        SELECT id_usuario_amigo FROM amigos WHERE id_usuario = $mi_id
    )
    ORDER BY a.fecha_publicacion DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>SportRoute - Explorar Rutas</title>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.7.0/gpx.min.js"></script>
    <link rel="stylesheet" href="main.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="principal.php">Inicio</a></li>
            <li><a href="explorar.php" class="active">Explorar</a></li>
            <li><a href="amigos.php">Amigos</a></li>
            <li><a href="#">Mis Rutas</a></li>
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
                        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div class="container">
    <h2 style="margin-top: 30px;">🌎 Explorar Actividades de la Comunidad</h2>
    
    <div class="grid-explorar">
        <?php 
        // 4. Bucle usando los datos reales, pero con el diseño HTML del código 1
        if (mysqli_num_rows($resultado) > 0) {
            while($ruta = mysqli_fetch_assoc($resultado)) { 
                $mapId = "mapa_" . $ruta['id'];
                $num_aplausos = $ruta['total_aplausos'] ?? 0;
                $clase_activo = ($ruta['le_he_dado'] > 0) ? 'aplauso-activo' : '';
            ?>
                <div class="card-ruta">
                    <div id="<?php echo $mapId; ?>" class="mapa-container" 
                        data-gpx="uploads/gpx/<?php echo $ruta['archivo_gpx']; ?>"
                        style="height: 250px; background: #eee; border-radius: 10px 10px 0 0;"></div>
                    
                    <div class="card-info">
                        <div class="autor-info">
                            👤 <strong><?php echo htmlspecialchars($ruta['autor']); ?></strong>
                        </div>
                        <h3><?php echo htmlspecialchars($ruta['titulo']); ?></h3>
                        <span class="tipo-tag"><?php echo htmlspecialchars($ruta['tipo_actividad']); ?></span>
                        <p class="fecha-info">
                            📅 <?php echo date("d/m/Y", strtotime($ruta['fecha_publicacion'])); ?>
                        </p>
                        <div class="card-ruta-footer">
                            <button class="btn-aplauso <?php echo $clase_activo; ?>" 
                                    onclick="darAplauso(this, <?php echo $ruta['id']; ?>)">
                                👏 <span class="count"><?php echo $num_aplausos; ?></span> Aplausos
                            </button>
                            <a href="detalle_actividad.php?id=<?php echo $ruta['id']; ?>" class="btn-perfil">
                                Ver actividad
                            </a>
                        </div>
                    </div>
                </div>
            <?php }
        } else {
            echo "<p style='text-align: center; grid-column: 1 / -1; color: #7f8c8d; padding: 40px;'>Aún no hay rutas publicadas. ¡Sé el primero!</p>";
        }
        ?>
    </div>
</div>

<a href="actividad.php" class="fab-add">+</a>

<script>
    window.onload = function() {
        const contenedoresMapas = document.querySelectorAll('.mapa-container');
        
        contenedoresMapas.forEach(contenedor => {
            const mapId = contenedor.id;
            const rutaGpx = contenedor.dataset.gpx;

            // Validación mejorada para evitar cargar GPX vacíos
            if (rutaGpx && rutaGpx !== 'uploads/gpx/' && !rutaGpx.endsWith('/')) {
                const map = L.map(mapId, {
                    scrollWheelZoom: false,
                    dragging: !L.Browser.mobile
                }).setView([35.889, -5.319], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                new L.GPX(rutaGpx, {
                    async: true,
                    polyline_options: { color: '#27ae60', weight: 4 },
                    marker_options: {
                        startIconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                        endIconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png'
                    }
                }).on('loaded', function(e) {
                    map.fitBounds(e.target.getBounds(), {padding: [15, 15]});
                }).on('error', function() {
                    contenedor.innerHTML = '<p style="text-align: center; padding-top: 100px; color: #bdc3c7;">GPX no encontrado</p>';
                }).addTo(map);
            } else {
                contenedor.innerHTML = '<p style="text-align: center; padding-top: 100px; color: #bdc3c7;">Sin ruta GPS</p>';
            }
        });
    };

    function darAplauso(boton, idActividad) {
        const contador = boton.querySelector('.count');
        let numeroActual = parseInt(contador.innerText);

        // 5. Animación fluida de la interfaz (Del código 1)
        if (boton.classList.contains('aplauso-activo')) {
            boton.classList.remove('aplauso-activo');
            contador.innerText = numeroActual - 1;
            boton.style.backgroundColor = "transparent";
            boton.style.color = "inherit";
        } else {
            boton.classList.add('aplauso-activo');
            contador.innerText = numeroActual + 1;
            
            contador.style.display = "inline-block";
            contador.style.transform = "scale(1.4)";
            setTimeout(() => { contador.style.transform = "scale(1)"; }, 150);

            boton.style.backgroundColor = "#27ae60";
            boton.style.color = "white";
        }

        // 6. Conexión real con el backend mediante Fetch (Del código 2)
        let datos = new FormData();
        datos.append('id_actividad', idActividad);

        fetch('aplauso.php', {
            method: 'POST',
            body: datos
        })
        .then(response => response.text())
        .then(nuevoTotal => {
            if (nuevoTotal.includes("error")) {
                alert("Error de conexión, intenta de nuevo.");
                // Opcional: Revertir el efecto visual si da error el servidor
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>

</body>
</html>