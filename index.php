<?php
require_once 'config/conexion.php';

$totalProductos = 0;
$productosBajoStock = 0;
$pedidosPendientes = 0;
$ventasTotales = 0;

try {
    // 1. Total de productos activos
    $stmtProd = $conexion->query("SELECT COUNT(*) FROM producto WHERE estado = 1");
    $totalProductos = (int)$stmtProd->fetchColumn();

    // 2. Productos con stock actual menor o igual al mínimo
    $stmtStock = $conexion->query("
        SELECT COUNT(*) 
        FROM inventario i
        INNER JOIN producto p ON i.id_producto = p.id_producto
        WHERE p.estado = 1 AND i.stock_actual <= i.stock_minimo
    ");
    $productosBajoStock = (int)$stmtStock->fetchColumn();

    // 3. Pedidos pendientes por confirmar
    $stmtPed = $conexion->query("SELECT COUNT(*) FROM pedido WHERE estado = 'pendiente'");
    $pedidosPendientes = (int)$stmtPed->fetchColumn();

    // 4. Total recaudado en ventas
    $stmtVentas = $conexion->query("SELECT COALESCE(SUM(total), 0) FROM venta");
    $ventasTotales = (float)$stmtVentas->fetchColumn();

} catch (PDOException $e) {
    error_log("Error al cargar métricas del inicio: " . $e->getMessage());
}

$tituloPagina = 'Inicio - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<style>
    .bienvenida {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        border-left: 5px solid #753b24;
    }
    .bienvenida h2 {
        margin: 0 0 8px 0;
        color: #753b24;
        font-size: 22px;
    }
    .bienvenida p {
        margin: 0;
        color: #555;
        font-size: 15px;
        line-height: 1.5;
    }
    .grid-tarjetas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }
    .tarjeta {
        background: #fff;
        padding: 22px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 5px solid #d9822b;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .tarjeta.alerta-stock {
        border-left-color: #c0392b;
    }
    .tarjeta.pedidos {
        border-left-color: #2980b9;
    }
    .tarjeta.ventas {
        border-left-color: #27ae60;
    }
    .tarjeta h3 {
        margin: 0 0 10px 0;
        font-size: 16px;
        color: #333;
    }
    .tarjeta .numero {
        font-size: 32px;
        font-weight: bold;
        color: #753b24;
        margin: 5px 0;
    }
    .tarjeta.alerta-stock .numero {
        color: #c0392b;
    }
    .tarjeta.ventas .numero {
        color: #27ae60;
        font-size: 26px;
    }
    .tarjeta p {
        margin: 0 0 12px 0;
        font-size: 13px;
        color: #666;
    }
    .tarjeta a {
        font-size: 13px;
        font-weight: bold;
        color: #753b24;
        text-decoration: none;
    }
    .tarjeta a:hover {
        text-decoration: underline;
    }
</style>

<div style="width: 92%; max-width: 1200px; margin: 30px auto; flex: 1;">
    <div class="bienvenida">
        <h2>Bienvenido a nuestra plataforma</h2>
        <p>Este sistema permite gestionar la información de los artesanos, productos, clientes, pedidos, inventario y ventas de las artesanías de la cultura Wayúu.</p>
    </div>

    <div class="grid-tarjetas">
        <div class="tarjeta">
            <div>
                <h3>Productos registrados</h3>
                <div class="numero"><?= $totalProductos ?></div>
                <p>Productos disponibles en la base de datos.</p>
            </div>
            <a href="productos.php">Ver catálogo →</a>
        </div>

        <div class="tarjeta alerta-stock">
            <div>
                <h3>Alertas de bajo stock</h3>
                <div class="numero"><?= $productosBajoStock ?></div>
                <p>Productos en el límite o por debajo del stock mínimo.</p>
            </div>
            <a href="inventario.php?bajo_stock=1">Revisar inventario →</a>
        </div>

        <div class="tarjeta pedidos">
            <div>
                <h3>Pedidos pendientes</h3>
                <div class="numero"><?= $pedidosPendientes ?></div>
                <p>Pedidos esperando confirmación o despacho.</p>
            </div>
            <a href="pedidos.php?estado=pendiente">Gestionar pedidos →</a>
        </div>

        <div class="tarjeta ventas">
            <div>
                <h3>Ingresos por ventas</h3>
                <div class="numero">$<?= number_format($ventasTotales, 0, ',', '.') ?></div>
                <p>Total acumulado en ventas confirmadas.</p>
            </div>
            <a href="ventas.php">Ver historial de ventas →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>


</body>
</html>
