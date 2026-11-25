<?php
require_once 'config/conexion.php';

$sql = "SELECT v.id_venta, v.id_pedido, v.fecha_venta, v.metodo_pago, v.total, c.nombre AS cliente
        FROM venta v
        INNER JOIN pedido p ON v.id_pedido = p.id_pedido
        INNER JOIN cliente c ON p.id_cliente = c.id_cliente
        ORDER BY v.id_venta DESC";

try {
    $ventas = $conexion->query($sql)->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar ventas: " . $e->getMessage());
    $ventas = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Ventas - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Registro de Ventas</h2>
        <a href="registrar_venta.php" class="btn btn-nuevo">+ Registrar Venta</a>
    </div>

    <?php if ($mensaje !== '') { ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php } ?>

    <table>
        <thead>
            <tr>
                <th>ID Venta</th>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Fecha Venta</th>
                <th>Método de Pago</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ventas)) { ?>
                <tr><td colspan="7" style="text-align: center; padding: 20px;">No se encontraron ventas registradas.</td></tr>
            <?php } else { ?>
                <?php foreach ($ventas as $v) { ?>
                    <tr>
                        <td>#<?= (int)$v['id_venta'] ?></td>
                        <td>#<?= (int)$v['id_pedido'] ?></td>
                        <td><?= htmlspecialchars($v['cliente']) ?></td>
                        <td><?= htmlspecialchars($v['fecha_venta']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($v['metodo_pago'])) ?></td>
                        <td>$<?= number_format((float)$v['total'], 0, ',', '.') ?></td>
                        <td class="acciones">
                            <a href="detalle_venta.php?id=<?= (int)$v['id_venta'] ?>" class="btn btn-ver">Detalle</a>
                            <a href="comprobante_venta.php?id=<?= (int)$v['id_venta'] ?>" class="btn btn-editar" target="_blank">Comprobante</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>


</body>
</html>



