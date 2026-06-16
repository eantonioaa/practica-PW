<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: principal.php");
    exit();
}

$tipos = mysqli_query($conexion, "SELECT * FROM tipos_actividad");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SportRoute - Nueva Actividad</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.7.0/gpx.min.js"></script>
    <script src="jquery-3.6.3.min.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="principal.php">Volver al Inicio</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="card-nueva-actividad">
        <div class="header-actividad">
            <h2>🚀 Registrar Nueva Actividad</h2>
            <p class="subtitle">Rellena los datos de tu salida de hoy</p>
        </div>
        
        <form action="guardar_actividad.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col">
                    <label class="label-custom">Nombre de la actividad</label>
                    <input type="text" name="nombre_actividad" class="input-custom" placeholder="Ej: Subida al Veleta" required>
                </div>
                <div class="col">
                    <label class="label-custom">Tipo de deporte</label>
                    <select name="tipo_deporte" class="input-custom-select">
                        <?php while($tipo = mysqli_fetch_assoc($tipos)): ?>
                            <option value="<?php echo $tipo['id']; ?>">
                                <?php echo $tipo['nombre']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label class="label-custom">Ruta de la actividad</label>
            <div class="upload-gpx-container" style="margin-bottom: 10px;">
                <button type="button" class="btn-secundario" onclick="document.getElementById('gpx-file').click();">
                    📂 Cargar archivo GPX
                </button>
                <input type="file" name="gpx_file" id="gpx-file" accept=".gpx" style="display: none;" onchange="cargarGPXenMapa(this)">
                <span id="nombre-archivo-gpx" style="margin-left: 10px; color: #7f8c8d; font-size: 0.9rem;">No hay archivo seleccionado</span>
            </div>

            <div id="mapa-nuevo" style="height: 400px; border-radius: 15px; border: 2px solid #eee;"></div>

            <label class="label-custom">Imágenes de la aventura</label>
            <div class="photo-grid" id="galeria-fotos">
                <div class="photo-box add" onclick="document.getElementById('foto-archivo').click();">
                    <span id="plus-icon">+</span>
                    <div id="preview-container" style="width:100%; height:100%; display:none; border-radius:13px; background-size:cover;"></div>
                    <input type="file" name="fotos[]" id="foto-archivo" style="display: none;" accept="image/*" onchange="previewMultipleImages(this)" multiple>
                </div>
            </div>

            <label class="label-custom">¿Con quién has ido?</label>
            <div class="friends-row" id="amigos-seleccionados">
                <!-- amigos añadidos aparecen aquí -->
            </div>
            <div style="position:relative;">
                <input type="text" id="buscador-amigos" class="input-custom" 
                    placeholder="Buscar amigo..." autocomplete="off"
                    onkeydown="if(event.key==='Enter') event.preventDefault();">
                <div id="sugerencias-amigos" class="sugerencias-dropdown"></div>
            </div>
            <div id="amigos-hidden"></div>

            <div class="form-actions">
                <a href="principal.php" class="btn-cancelar">Cancelar</a>
                <button type="submit" class="btn-publicar">Publicar Ruta</button>
            </div>
        </form>
    </div>
</div>

<script>
    var map = L.map('mapa-nuevo').setView([35.889, -5.319], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    setTimeout(() => { map.invalidateSize(); }, 200);

    function previewMultipleImages(input) {
        if (input.files) {
            const botonAdd = document.querySelector('.photo-box.add');
            
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                
                reader.onload = function(e) {

                    const nuevoCuadro = document.createElement('div');
                    nuevoCuadro.className = 'photo-box';
                    nuevoCuadro.style.backgroundImage = `url(${e.target.result})`;
                    nuevoCuadro.style.backgroundSize = 'cover';
                    nuevoCuadro.style.backgroundPosition = 'center';
                    nuevoCuadro.innerHTML = ''; 
                    
                    const btnBorrar = document.createElement('button');
                    btnBorrar.innerHTML = '✕';
                    btnBorrar.className = 'btn-remove-photo';
                    btnBorrar.type = 'button';

                    btnBorrar.onclick = function() {
                        nuevoCuadro.remove(); 
                    };

                    nuevoCuadro.appendChild(btnBorrar);
                    botonAdd.before(nuevoCuadro);
                }
                
                reader.readAsDataURL(file);
            });
        }
    }

    let gpxLayer;

    function cargarGPXenMapa(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('nombre-archivo-gpx').innerText = file.name;

            const reader = new FileReader();
            reader.onload = function(e) {
                const gpxText = e.target.result;

                if (gpxLayer) {
                    map.removeLayer(gpxLayer);
                }

                gpxLayer = new L.GPX(gpxText, {
                    async: true,
                    marker_options: {
                        startIconUrl: 'leaflet/gpx/images/pin-icon-start.png',
                        endIconUrl: 'leaflet/gpx/images/pin-icon-end.png',
                        shadowUrl: 'leaflet/gpx/images/pin-shadow.png'
                    },
                    polyline_options: {
                        color: '#27ae60',
                        opacity: 0.75,
                        weight: 5,
                        lineCap: 'round'
                    }
                }).on('loaded', function(e) {
                    map.fitBounds(e.target.getBounds());
                    
                }).addTo(map);
            };

            reader.readAsText(file);
        }
    }
</script>

<script>
    let archivosAcumulados = new DataTransfer();

    function previewMultipleImages(input) {
        if (input.files) {
            const botonAdd = document.querySelector('.photo-box.add');

            Array.from(input.files).forEach(file => {
                archivosAcumulados.items.add(file);

                const reader = new FileReader();
                reader.onload = function(e) {
                    const nuevoCuadro = document.createElement('div');
                    nuevoCuadro.className = 'photo-box';
                    nuevoCuadro.style.backgroundImage = `url(${e.target.result})`;
                    nuevoCuadro.style.backgroundSize = 'cover';
                    nuevoCuadro.style.backgroundPosition = 'center';

                    const btnBorrar = document.createElement('button');
                    btnBorrar.innerHTML = '✕';
                    btnBorrar.className = 'btn-remove-photo';
                    btnBorrar.type = 'button';
                    btnBorrar.onclick = function() {
                        const index = Array.from(archivosAcumulados.files).findIndex(f => f.name === file.name);
                        if (index > -1) {
                            const nuevoDT = new DataTransfer();
                            Array.from(archivosAcumulados.files).forEach((f, i) => {
                                if (i !== index) nuevoDT.items.add(f);
                            });
                            archivosAcumulados = nuevoDT;
                            document.getElementById('foto-archivo').files = archivosAcumulados.files;
                        }
                        nuevoCuadro.remove();
                    };

                    nuevoCuadro.appendChild(btnBorrar);
                    botonAdd.before(nuevoCuadro);
                };
                reader.readAsDataURL(file);
            });

            input.files = archivosAcumulados.files;
        }
    }
</script>

<script>
    const amigosSeleccionados = [];

    $('#buscador-amigos').on('input', function() {
        const q = $(this).val();
        if (q.length < 1) {
            $('#sugerencias-amigos').hide();
            return;
        }

        $.get('get_amigos.php', { q: q }, function(data) {
            const dropdown = $('#sugerencias-amigos');
            dropdown.html('');

            if (data.length === 0) {
                dropdown.hide();
                return;
            }

            data.forEach(function(amigo) {
                if (!amigosSeleccionados.find(a => a.id === amigo.id)) {
                    dropdown.append(
                        `<div class="sugerencia-item" 
                            onclick="añadirAmigo(${amigo.id}, '${amigo.nombre_usuario}')">
                            👤 ${amigo.nombre_usuario}
                        </div>`
                    );
                }
            });

            dropdown.show();
        }, 'json');
    });

    function añadirAmigo(id, nombre) {
        if (amigosSeleccionados.find(a => a.id === id)) return;
        
        amigosSeleccionados.push({ id, nombre });

        // Añadir tag visual
        $('#amigos-seleccionados').append(
            `<span class="friend-tag" id="tag-${id}">
                👤 ${nombre}
                <span onclick="quitarAmigo(${id})" style="cursor:pointer;">✕</span>
            </span>`
        );

        // Añadir input hidden para el formulario
        $('#amigos-hidden').append(
            `<input type="hidden" name="companeros[]" value="${id}" id="hidden-${id}">`
        );

        $('#buscador-amigos').val('');
        $('#sugerencias-amigos').hide();
    }

    function quitarAmigo(id) {
        const index = amigosSeleccionados.findIndex(a => a.id === id);
        if (index > -1) amigosSeleccionados.splice(index, 1);
        $(`#tag-${id}`).remove();
        $(`#hidden-${id}`).remove();
    }

    // Cerrar dropdown al hacer click fuera
    $(document).click(function(e) {
        if (!$(e.target).closest('#buscador-amigos').length) {
            $('#sugerencias-amigos').hide();
        }
    });
</script>

</body>
</html>