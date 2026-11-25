<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

// Obtener el ID del pedido
$id_pedido = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_pedido <= 0) {
    die("Pedido no válido.");
}

// Consultar información general del pedido
$sqlPedido = "SELECT p.id_pedido, p.fecha_pedido, p.estado, p.total,
                     c.nombre AS cliente,
                     c.documento,
                     c.telefono
              FROM pedido p
              INNER JOIN cliente c ON p.id_cliente = c.id_cliente
              WHERE p.id_pedido = ?";

$consultaPedido = $conexion->prepare($sqlPedido);
$consultaPedido->execute([$id_pedido]);
$pedido = $consultaPedido->fetch();

if (!$pedido) {
    die("El pedido no existe.");
}

// Consultar productos del pedido
$sqlDetalles = "SELECT pr.nombre AS producto,
                       dp.cantidad,
                       dp.precio_unitario,
                       dp.subtotal
                FROM detalle_pedido dp
                INNER JOIN producto pr ON dp.id_producto = pr.id_producto
                WHERE dp.id_pedido = ?";

$consultaDetalles = $conexion->prepare($sqlDetalles);
$consultaDetalles->execute([$id_pedido]);
$detalles = $consultaDetalles->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detalle del pedido</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .contenedor {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .informacion {
            background: #f7f7f7;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .informacion p {
            margin: 10px 0;
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
            font-size: 20px;
            font-weight: bold;
            margin-top: 25px;
        }

        .estado {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            background: #fff0c2;
            color: #8a6500;
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

    <h2>Detalle del pedido #<?= $pedido["id_pedido"] ?></h2>

    <div class="informacion">

        <p>
            <strong>Cliente:</strong>
            <?= htmlspecialchars($pedido["cliente"]) ?>
        </p>

        <p>
            <strong>Documento:</strong>
            <?= htmlspecialchars($pedido["documento"] ?? "No registrado") ?>
        </p>

        <p>
            <strong>Teléfono:</strong>
            <?= htmlspecialchars($pedido["telefono"] ?? "No registrado") ?>
        </p>

        <p>
            <strong>Fecha:</strong>
            <?= htmlspecialchars($pedido["fecha_pedido"]) ?>
        </p>

        <p>
            <strong>Estado:</strong>
            <span class="estado">
                <?= htmlspecialchars(ucfirst($pedido["estado"])) ?>
            </span>
        </p>

    </div>

    <h3>Productos del pedido</h3>

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

        </tbody>

    </table>

    <div class="total">
        Total del pedido:
        $<?= number_format($pedido["total"], 0, ',', '.') ?>
    </div>

    <a href="pedidos.php" class="volver">
        Volver a pedidos
    </a>

</div>

</body>
</html>
