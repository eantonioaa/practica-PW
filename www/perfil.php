<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: index.php");
    exit();
}

$user_identificado = mysqli_real_escape_string($conexion, $_SESSION['usuario_nombre']);

$sql = "SELECT u.*, ta.nombre as tipo_actividad_nombre, p.nombre as pais_nombre, 
        pr.Provincia as provincia_nombre, m.Municipio as municipio_nombre 
        FROM usuarios u
        LEFT JOIN tipos_actividad ta ON u.id_actividad_preferida = ta.id
        LEFT JOIN paises p ON u.id_pais = p.id
        LEFT JOIN PROVINCIAS pr ON u.id_provincia = pr.idProvincia
        LEFT JOIN MUNICIPIOS m ON u.id_municipio = m.idMunicipio
        WHERE u.nombre_usuario = '$user_identificado'";

$resultado = mysqli_query($conexion, $sql);
$datos = mysqli_fetch_assoc($resultado);

$nombre_actual    = $datos['nombre_usuario'] ?? '';
$email_actual     = $datos['email'] ?? '';
$fecha_nac        = $datos['fecha_nacimiento'] ?? '';
$nombre_real      = $datos['nombre'] ?? '';
$apellidos        = $datos['apellidos'] ?? '';
$id_deporte       = $datos['id_actividad_preferida'] ?? '';
$id_pais          = $datos['id_pais'] ?? '';
$id_provincia     = $datos['id_provincia'] ?? '';
$id_municipio     = $datos['id_municipio'] ?? '';
$foto_perfil      = $datos['id_imagen_perfil'] ?? null;

// Obtener URL de foto de perfil
$url_foto = null;
if ($foto_perfil) {
    $res_foto = mysqli_query($conexion, "SELECT ruta FROM imagenes WHERE id = $foto_perfil");
    $foto_data = mysqli_fetch_assoc($res_foto);
    $url_foto = $foto_data['ruta'] ?? null;
}

// Cargar opciones para selects
$tipos = mysqli_query($conexion, "SELECT * FROM tipos_actividad");
$paises = mysqli_query($conexion, "SELECT * FROM paises ORDER BY nombre");
$provincias = mysqli_query($conexion, "SELECT * FROM PROVINCIAS WHERE id_pais = '$id_pais' ORDER BY Provincia");
$municipios = mysqli_query($conexion, "SELECT * FROM MUNICIPIOS WHERE idProvincia = '$id_provincia' ORDER BY Municipio");

// Obtener actividades del usuario
$id_usuario = $datos['id'];
$sql_acts = "SELECT a.*, ta.nombre as tipo_actividad, r.archivo_gpx,
             (SELECT COUNT(*) FROM actividad_aplausos WHERE id_actividad = a.id) as total_aplausos
             FROM actividades a
             LEFT JOIN tipos_actividad ta ON a.id_tipo_actividad = ta.id
             LEFT JOIN rutas r ON a.id_ruta = r.id
             WHERE a.id_usuario = $id_usuario
             ORDER BY a.fecha_publicacion DESC";
$res_acts = mysqli_query($conexion, $sql_acts);
$actividades = mysqli_fetch_all($res_acts, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - SportRoute</title>
    <link rel="stylesheet" href="main.css">
    <script src="jquery-3.6.3.min.js"></script>
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <script src="leaflet/leaflet.js"></script>
    <script src="leaflet/gpx/gpx.js"></script>
</head>
<body>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
    <div class="alerta-exito">✅ Perfil actualizado correctamente.</div>
<?php endif; ?>

<nav class="navbar">
    <div class="container">
        <a href="principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="principal.php">Inicio</a></li>
            <li><a href="explorar.php">Explorar</a></li>
            <li><a href="amigos.php">Amigos</a></li>
            <li class="user-menu-item">
                <div class="user-dropdown">
                    <button class="user-avatar-btn">
                        <?php if ($url_foto): ?>
                            <img src="<?php echo $url_foto; ?>" style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($nombre_actual, 0, 1)); ?>
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
    <div class="activity-card perfil-amigo-card" style="margin-top:30px;">
        
        <!-- Avatar -->
        <div class="avatar-perfil-grande">
            <?php if ($url_foto): ?>
                <img src="<?php echo htmlspecialchars($url_foto); ?>" class="avatar-foto" onclick="abrirLightbox(this.src)">
            <?php else: ?>
                <div class="avatar-grande"><?php echo strtoupper(substr($nombre_actual, 0, 1)); ?></div>
            <?php endif; ?>
        </div>

        <h2><?php echo htmlspecialchars($nombre_real . ' ' . $apellidos); ?></h2>
        <p class="texto-gris">@<?php echo htmlspecialchars($nombre_actual); ?></p>

        <div class="perfil-amigo-datos">
            <?php if ($datos['municipio_nombre']): ?>
                <p class="texto-gris">📍 <?php echo htmlspecialchars($datos['municipio_nombre'] . ', ' . $datos['provincia_nombre']); ?></p>
            <?php endif; ?>
            <?php if ($datos['tipo_actividad_nombre']): ?>
                <p class="texto-gris">🏅 <?php echo htmlspecialchars($datos['tipo_actividad_nombre']); ?></p>
            <?php endif; ?>
            <p class="texto-gris">📅 Miembro desde <?php echo date('Y', strtotime($datos['fecha_registro'])); ?></p>
        </div>

        <div class="perfil-botones">
            <button onclick="abrirModal()" class="btn-active" style="font-weight: bold;">✏️ Editar perfil</button>
            <a href="baja_cuenta.php" class="btn-baja" 
                onclick="return confirm('¿Estás seguro de que quieres darte de baja? Esta acción cerrará tu cuenta.')">
                🚪 Darme de baja
            </a>
        </div>
    </div>

    <!-- Actividades -->
    <h3 class="seccion-titulo">🏃 Mis Actividades</h3>

    <?php if (!empty($actividades)): ?>
        <?php foreach($actividades as $act): ?>
            <?php $mapId = "mapa_" . $act['id']; 
                
                $res_comp = mysqli_query($conexion, "SELECT u.nombre_usuario, u.id FROM actividad_compañeros ac
                            JOIN usuarios u ON ac.id_usuario_companero = u.id
                            WHERE ac.id_actividad = {$act['id']}");
                $companeros_act = mysqli_fetch_all($res_comp, MYSQLI_ASSOC);

                $res_imgs = mysqli_query($conexion, "SELECT i.ruta, i.nombre FROM actividad_imagenes ai
                            JOIN imagenes i ON ai.id_imagen = i.id
                            WHERE ai.id_actividad = {$act['id']}");
                $imagenes_act = mysqli_fetch_all($res_imgs, MYSQLI_ASSOC);
            ?>
            <div class="activity-card">
                <div class="card-header">
                    <div class="user-avatar-small">
                         <div class="user-avatar-small">
                            <?php if ($url_foto): ?>
                                <img src="<?php echo htmlspecialchars($url_foto); ?>" class="avatar-foto">
                            <?php else: ?>
                                <?php echo strtoupper(substr($nombre_actual, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-meta">
                        <h4><?php echo htmlspecialchars($act['titulo']); ?></h4>
                        <span><?php echo htmlspecialchars($act['tipo_actividad']); ?> · 
                              <?php echo date('d/m/Y', strtotime($act['fecha_publicacion'])); ?>
                        </span>
                    </div>
                </div>

                <?php if ($act['archivo_gpx']): ?>
                    <div id="<?php echo $mapId; ?>" class="mapa-container" 
                         data-gpx="uploads/gpx/<?php echo $act['archivo_gpx']; ?>"></div>
                <?php endif; ?>

                <?php if (!empty($companeros_act)): ?>
                    <div class="actividad-seccion">
                        <h4>👥 Compañeros</h4>
                        <div class="companeros-row">
                            <?php foreach($companeros_act as $comp): ?>
                                <a href="perfil_amigo.php?id=<?php echo $comp['id']; ?>" class="friend-tag">
                                    👤 <?php echo htmlspecialchars($comp['nombre_usuario']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($imagenes_act)): ?>
                    <div class="actividad-seccion">
                        <h4>📸 Imágenes</h4>
                        <div class="imagenes-grid">
                            <?php foreach($imagenes_act as $img): ?>
                                <img src="<?php echo htmlspecialchars($img['ruta']); ?>"
                                    alt="<?php echo htmlspecialchars($img['nombre']); ?>"
                                    class="actividad-imagen"
                                    onclick="abrirLightbox(this.src)">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="route-stats">
                    <div class="stat"><strong>--</strong><span>KM</span></div>
                    <div class="stat"><strong>--</strong><span>TIEMPO</span></div>
                    <div class="stat"><strong><?php echo $act['total_aplausos']; ?></strong><span>APLAUSOS</span></div>
                </div>

                <div style="margin-top:10px;">
                    <a href="detalle_actividad.php?id=<?php echo $act['id']; ?>" class="btn-perfil">Ver detalle</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="texto-gris">Aún no has publicado actividades.</p>
    <?php endif; ?>

</div>

<!-- Modal editar perfil -->
<div class="modal-overlay" id="modal-editar">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="cerrarModal()">✕</button>
        <h2>✏️ Editar Perfil</h2>
        <p class="subtitle">Actualiza tu información personal</p>

        <form action="actualizar_perfil.php" method="POST" enctype="multipart/form-data">

            <label class="card-perfil-label">Foto de Perfil</label>
            <div class="foto-perfil-preview">
                <?php if ($url_foto): ?>
                    <img src="<?php echo htmlspecialchars($url_foto); ?>" class="avatar-foto" id="preview-foto">
                <?php else: ?>
                    <div class="avatar-grande" id="avatar-letra">
                        <?php echo strtoupper(substr($nombre_actual, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <input type="file" name="foto_perfil" id="input-foto-perfil" 
                       accept="image/*" style="display:none;"
                       onchange="previewFoto(this)">
                <button type="button" class="btn-secundario" 
                        onclick="document.getElementById('input-foto-perfil').click()">
                    📷 Cambiar foto
                </button>
            </div>

            <label class="card-perfil-label">Nombre de Usuario</label>
            <input type="text" name="nombre_usuario" class="card-perfil-input"
                   value="<?php echo htmlspecialchars($nombre_actual); ?>" required>

            <label class="card-perfil-label">Correo Electrónico</label>
            <input type="email" name="email" class="card-perfil-input"
                   value="<?php echo htmlspecialchars($email_actual); ?>" required>

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Nombre</label>
                    <input type="text" name="nombre" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($nombre_real); ?>">
                </div>
                <div class="col">
                    <label class="card-perfil-label">Apellidos</label>
                    <input type="text" name="apellidos" class="card-perfil-input"
                           value="<?php echo htmlspecialchars($apellidos); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="card-perfil-input"
                           value="<?php echo $fecha_nac; ?>">
                </div>
                <div class="col">
                    <label class="card-perfil-label">Tipo de Actividad</label>
                    <select name="id_actividad_preferida" class="card-perfil-input">
                        <?php 
                        mysqli_data_seek($tipos, 0);
                        while($tipo = mysqli_fetch_assoc($tipos)): ?>
                            <option value="<?php echo $tipo['id']; ?>"
                                <?php if($tipo['id'] == $id_deporte) echo 'selected'; ?>>
                                <?php echo $tipo['nombre']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label class="card-perfil-label">País</label>
            <select name="id_pais" id="modal-pais" class="card-perfil-input">
                <option value="">-- Selecciona país --</option>
                <?php 
                mysqli_data_seek($paises, 0);
                while($pais = mysqli_fetch_assoc($paises)): ?>
                    <option value="<?php echo $pais['id']; ?>"
                        <?php if($pais['id'] == $id_pais) echo 'selected'; ?>>
                        <?php echo $pais['nombre']; ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <div class="row">
                <div class="col">
                    <label class="card-perfil-label">Provincia</label>
                    <select name="id_provincia" id="modal-provincia" class="card-perfil-input">
                        <option value="">-- Selecciona provincia --</option>
                        <?php while($prov = mysqli_fetch_assoc($provincias)): ?>
                            <option value="<?php echo $prov['idProvincia']; ?>"
                                <?php if($prov['idProvincia'] == $id_provincia) echo 'selected'; ?>>
                                <?php echo $prov['Provincia']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col">
                    <label class="card-perfil-label">Localidad</label>
                    <select name="id_municipio" id="modal-municipio" class="card-perfil-input">
                        <option value="">-- Selecciona localidad --</option>
                        <?php while($mun = mysqli_fetch_assoc($municipios)): ?>
                            <option value="<?php echo $mun['idMunicipio']; ?>"
                                <?php if($mun['idMunicipio'] == $id_municipio) echo 'selected'; ?>>
                                <?php echo $mun['Municipio']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label class="card-perfil-label">Nueva Contraseña</label>
            <input type="password" name="nueva_pass" class="card-perfil-input"
                   placeholder="•••••••• (dejar vacío para no cambiar)">

            <div class="form-buttons" style="margin-top:25px;">
                <button type="button" onclick="cerrarModal()" class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-active">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modal-editar').classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('modal-editar').classList.remove('visible');
    document.body.style.overflow = '';
}

// Cerrar al hacer click fuera del modal
document.getElementById('modal-editar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

// Preview foto de perfil
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview-foto');
            const letra = document.getElementById('avatar-letra');
            if (preview) {
                preview.src = e.target.result;
            } else if (letra) {
                letra.outerHTML = `<img src="${e.target.result}" class="avatar-foto" id="preview-foto">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// AJAX País -> Provincia -> Municipio
$('#modal-pais').change(function() {
    const id_pais = $(this).val();
    $('#modal-provincia').html('<option value="">-- Selecciona provincia --</option>');
    $('#modal-municipio').html('<option value="">-- Selecciona localidad --</option>');
    if (id_pais) {
        $.post('get_provincias.php', { id_pais: id_pais }, function(data) {
            $('#modal-provincia').html(data);
        });
    }
});

$('#modal-provincia').change(function() {
    const id_provincia = $(this).val();
    $('#modal-municipio').html('<option value="">-- Selecciona localidad --</option>');
    if (id_provincia) {
        $.post('get_municipios.php', { id_provincia: id_provincia }, function(data) {
            $('#modal-municipio').html(data);
        });
    }
});
</script>

<?php if (!empty($actividades)): ?>
<script>
window.onload = function() {
    const contenedores = document.querySelectorAll('.mapa-container');
    contenedores.forEach(contenedor => {
        const mapId = contenedor.id;
        const rutaGpx = contenedor.dataset.gpx;
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
            }).addTo(map);
        }
    });
};
</script>
<?php endif; ?>

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