<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

// Obtener ID de la venta
$id_venta = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_venta <= 0) {
    die("Venta no válida.");
}

// Consultar información de la venta
$sqlVenta = "SELECT v.id_venta,
                    v.fecha_venta,
                    v.metodo_pago,
                    v.total,
                    p.id_pedido,
                    c.nombre AS cliente,
                    c.documento,
                    c.telefono
             FROM venta v
             INNER JOIN pedido p ON v.id_pedido = p.id_pedido
             INNER JOIN cliente c ON p.id_cliente = c.id_cliente
             WHERE v.id_venta = ?";

$consultaVenta = $conexion->prepare($sqlVenta);
$consultaVenta->execute([$id_venta]);
$venta = $consultaVenta->fetch();

if (!$venta) {
    die("La venta no existe.");
}

// Consultar productos vendidos
$sqlDetalles = "SELECT pr.nombre AS producto,
                       dp.cantidad,
                       dp.precio_unitario,
                       dp.subtotal
                FROM detalle_pedido dp
                INNER JOIN producto pr
                    ON dp.id_producto = pr.id_producto
                WHERE dp.id_pedido = ?";

$consultaDetalles = $conexion->prepare($sqlDetalles);
$consultaDetalles->execute([$venta["id_pedido"]]);
$detalles = $consultaDetalles->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detalle de venta</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
            color: #333;
        }

        .contenedor {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2 {
            color: #753b24;
        }

        .informacion {
            background: #f7f7f7;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .informacion p {
            margin: 12px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #287a45;
            color: white;
        }

        .total {
            text-align: right;
            font-size: 21px;
            font-weight: bold;
            margin-top: 25px;
        }

        .volver {
            display: inline-block;
            margin-top: 25px;
            padding: 11px 18px;
            background: #555;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .contenedor {
                padding: 12px;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h2>Detalle de venta #<?= $venta["id_venta"] ?></h2>

    <div class="informacion">

        <p>
            <strong>Cliente:</strong>
            <?= htmlspecialchars($venta["cliente"]) ?>
        </p>

        <p>
            <strong>Documento:</strong>
            <?= htmlspecialchars($venta["documento"] ?? "No registrado") ?>
        </p>

        <p>
            <strong>Teléfono:</strong>
            <?= htmlspecialchars($venta["telefono"] ?? "No registrado") ?>
        </p>

        <p>
            <strong>Pedido relacionado:</strong>
            #<?= $venta["id_pedido"] ?>
        </p>

        <p>
            <strong>Fecha de venta:</strong>
            <?= htmlspecialchars($venta["fecha_venta"]) ?>
        </p>

        <p>
            <strong>Método de pago:</strong>
            <?= htmlspecialchars(ucfirst($venta["metodo_pago"])) ?>
        </p>

    </div>

    <h3>Productos vendidos</h3>

    <table>

        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($detalles as $detalle): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($detalle["producto"]) ?>
                    </td>

                    <td>
                        <?= $detalle["cantidad"] ?>
                    </td>

                    <td>
                        $<?= number_format($detalle["precio_unitario"], 0, ',', '.') ?>
                    </td>

                    <td>
                        $<?= number_format($detalle["subtotal"], 0, ',', '.') ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (count($detalles) === 0): ?>

                <tr>
                    <td colspan="4">No hay productos registrados.</td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

    <div class="total">
        Total de venta:
        $<?= number_format($venta["total"], 0, ',', '.') ?>
    </div>

    <a href="ventas.php" class="volver">
        Volver a ventas
    </a>

</div>

</body>
</html>
