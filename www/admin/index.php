<?php
session_start();
include('../db.php');
include('../admin/control.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - SportRoute</title>
    <link rel="stylesheet" href="../main.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../principal.php" class="logo">Sport<span>Route</span></a>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Admin</a></li>
            <li><a href="../principal.php">Ver web</a></li>
            <li class="user-menu-item">
                <div class="user-dropdown">
                    <button class="user-avatar-btn">
                        <?php if (!empty($url_foto)): ?>
                            <img src="<?php echo $url_foto; ?>" style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
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
        <h2>Panel de Administración</h2>
        <p>Bienvenido, <?php echo htmlspecialchars($nombre); ?></p>
    </div>

    <div class="grid-admin">
        <a href="usuarios.php" class="card-admin">
            <span class="card-admin-icono">👥</span>
            <h3>Gestión de Usuarios</h3>
            <p>Buscar, editar y dar de baja usuarios</p>
        </a>
        <a href="actividades.php" class="card-admin">
            <span class="card-admin-icono">🏃</span>
            <h3>Gestión de Actividades</h3>
            <p>Ver actividades y eliminar imágenes</p>
        </a>
        <a href="datos_auxiliares.php" class="card-admin">
            <span class="card-admin-icono">⚙️</span>
            <h3>Datos Auxiliares</h3>
            <p>Tipos de actividad, países, provincias y localidades</p>
        </a>
    </div>
</div>

</body>
</html>