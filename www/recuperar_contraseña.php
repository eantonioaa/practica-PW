<?php
session_start();
include('db.php');

$msg = '';

if (isset($_POST['recuperar'])) {
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    
    $res = mysqli_query($conexion, "SELECT * FROM usuarios WHERE email = '$email'");
    
    if (mysqli_num_rows($res) > 0) {
        $pass_temporal = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $pass_hash = password_hash($pass_temporal, PASSWORD_DEFAULT);
        
        mysqli_query($conexion, "UPDATE usuarios SET password = '$pass_hash' WHERE email = '$email'");
        
        $msg = "ok";
        $pass_mostrar = $pass_temporal;
    } else {
        $msg = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - SportRoute</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="card">
    <a href="index.php" class="logo" style="display:block; text-align:center; font-size:1.5rem; font-weight:bold; color:#2c3e50; text-decoration:none; margin-bottom:20px;">
        Sport<span style="color:#27ae60;">Route</span>
    </a>

    <h2>Recuperar Contraseña</h2>
    <p style="color:#888; font-size:0.9rem; margin-bottom:20px;">Introduce tu email y te daremos una contraseña temporal.</p>

    <?php if ($msg == 'ok'): ?>
        <div class="alerta-exito">
            ✅ Tu contraseña temporal es: <span class="pass-temporal"><?php echo $pass_mostrar; ?></span>
            <br><small>Cámbiala desde tu perfil una vez que entres.</small>
        </div>
        <a href="index.php" class="btn-principal">Ir al Login</a>
    <?php elseif ($msg == 'error'): ?>
        <div class="alerta-error">
            ❌ No existe ninguna cuenta con ese email.
        </div>
        <form method="POST">
            <label>Correo Electrónico</label>
            <input type="email" name="email" placeholder="ejemplo@gmail.com" required>
            <button type="submit" name="recuperar" class="btn-principal">Recuperar</button>
        </form>
        <a href="index.php" class="link-recuperar">← Volver al login</a>
    <?php else: ?>
        <form method="POST">
            <label>Correo Electrónico</label>
            <input type="email" name="email" placeholder="ejemplo@gmail.com" required>
            <button type="submit" name="recuperar" class="btn-principal">Recuperar</button>
        </form>
        <a href="index.php" class="link-recuperar">← Volver al login</a>
    <?php endif; ?>
</div>

</body>
</html>