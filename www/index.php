<?php
session_start();
include('db.php');

if (isset($_SESSION['usuario_id'])) {
    header("Location: principal.php");
    exit();
}

$tipos = mysqli_query($conexion, "SELECT * FROM tipos_actividad");
$paises = mysqli_query($conexion, "SELECT * FROM paises ORDER BY nombre");
?>

<html>
<head>
    <meta charset="UTF-8">
    <title> SportRoute </title>
    <link rel="stylesheet" type="text/css" href="login.css" />
    <script src="jquery-3.6.3.min.js"></script>
</head>

<body>
<div class="card">
    <div class="tab-container">
        <button id="btn-login" class="tab active" onclick="mostrarLogin()">Entrar</button>
        <button id="btn-register" class="tab" onclick="mostrarRegistro()">Registrarse</button>
    </div>

    <form id="form-login" action="login.php" method="POST">
        <h2>Bienvenido de nuevo</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'baja'): ?>
            <div class="alerta-error" style="margin-bottom:15px;">
                ❌ Esta cuenta ha sido dada de baja. Contacta con el administrador.
            </div>
        <?php endif; ?>

        <label>Correo Electrónico</label>
        <input type="email" name="email_usuario" placeholder="ejemplo@gmail.com" required>
        <input type="password" name="pass_usuario" placeholder="••••••••" required>   
        <button type="submit" class="btn-principal">Iniciar Sesión</button>
        <a href="recuperar_contraseña.php" class="link-recuperar">¿Olvidaste tu contraseña?</a>
    </form>

    <form id="form-register" action="registro.php" method="POST" class="hidden">
        <h2>Crea tu perfil</h2>
        <label>Nombre de Usuario</label>
        <input type="text" name="nombre_usuario" placeholder="Ej: Ciclista_Pro" required>
        
        <div class="row">
            <div class="col">
                <label>Nombre</label>
                <input type="text" name="nombre" placeholder="Ej: Juan" required>
            </div>
            <div class="col">
                <label>Apellidos</label>
                <input type="text" name="apellidos" placeholder="Ej: García" required>
            </div>
        </div>

        <label>Correo Electrónico</label>
        <input type="email" name="email_usuario" placeholder="ejemplo@gmail.com" required>

        <div class="row">
            <div class="col">
                <label>Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" required>
            </div>
            <div class="col">
                <label>Deporte Favorito</label>
                <select name="deporte_favorito">
                    <?php while($tipo = mysqli_fetch_assoc($tipos)): ?>
                        <option value="<?php echo $tipo['id']; ?>">
                            <?php echo $tipo['nombre']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <label>País</label>
        <select name="id_pais" id="reg-pais" required>
            <?php while($pais = mysqli_fetch_assoc($paises)): ?>
                <option value="<?php echo $pais['id']; ?>">
                    <?php echo $pais['nombre']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <div class="row">
            <div class="col">
                <label>Provincia</label>
                <select name="id_provincia" id="reg-provincia">
                    <option value="">-- Selecciona provincia --</option>
                </select>
            </div>
            <div class="col">
                <label>Localidad</label>
                <select name="id_municipio" id="reg-municipio">
                    <option value="">-- Selecciona localidad --</option>
                </select>
            </div>
        </div>
        
        <label>Contraseña</label>
        <input type="password" name="pass" id="reg-pass" placeholder="Mínimo 8 caract. (Letras y Números)" 
                minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" 
                title="La contraseña debe tener al menos 8 caracteres, incluyendo letras y números" required>
        
        <button type="submit" class="btn-principal">Registrarme</button>
    </form>
</div>

<script>
$(document).ready(function() {
    const formLogin = document.getElementById('form-login');
    const formRegister = document.getElementById('form-register');
    const btnLogin = document.getElementById('btn-login');
    const btnRegister = document.getElementById('btn-register');

    window.mostrarRegistro = function() {
        formLogin.classList.add('hidden');      
        formRegister.classList.remove('hidden'); 
        btnRegister.classList.add('active');    
        btnLogin.classList.remove('active');  
    }

    window.mostrarLogin = function() {
        formLogin.classList.remove('hidden');
        formRegister.classList.add('hidden');
        btnLogin.classList.add('active');
        btnRegister.classList.remove('active');
    }

    $('#reg-pais').change(function() {
        const id_pais = $(this).val();
        $('#reg-provincia').html('<option value="">-- Selecciona provincia --</option>');
        $('#reg-municipio').html('<option value="">-- Selecciona localidad --</option>');
        
        if (id_pais) {
            $.post('get_provincias.php', { id_pais: id_pais }, function(data) {
                $('#reg-provincia').html(data);
            });
        }
    });

    $('#reg-provincia').change(function() {
        const id_provincia = $(this).val();
        $('#reg-municipio').html('<option value="">-- Selecciona localidad --</option>');
        
        if (id_provincia) {
            $.post('get_municipios.php', { id_provincia: id_provincia }, function(data) {
                $('#reg-municipio').html(data);
            });
        }
    });
});
</script>

</body>
</html>