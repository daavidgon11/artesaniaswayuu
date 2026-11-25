<?php require_once __DIR__ . '/verificar_sesion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Artesanías Wayúu') ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>

<header>
    <h1>ARTESANÍAS WAYÚU</h1>
    <p>Plataforma de comercialización de productos artesanales | Bienvenido, <strong><?= htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario') ?></strong></p>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="artesanos.php">Artesanos</a>
        <a href="categorias.php">Categorías</a>
        <a href="productos.php">Productos</a>
        <a href="clientes.php">Clientes</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="ventas.php">Ventas</a>
        <a href="inventario.php">Gestión de inventario</a>
        <a href="movimiento_inventario.php">Movimientos de inventario</a>
        <a href="reportes.php">Reportes</a>

        <?php if (($_SESSION['rol_usuario'] ?? '') === 'admin'): ?>
        <a href="usuarios.php">Usuarios</a>
        <?php endif; ?>

        <a href="tienda.php" target="_blank">Ver tienda</a>
        <a href="logout.php" style="color: #ffcccc;">Cerrar sesión</a>
    </nav>
</header>