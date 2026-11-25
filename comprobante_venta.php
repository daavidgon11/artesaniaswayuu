<?php
require_once 'verificar_sesion.php';

require_once "config/conexion.php";

// Obtener ID de venta
$id_venta = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_venta <= 0) {
    die("Número de venta no válido.");
}

// Consultar venta y cliente
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

    <title>Comprobante de venta</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eee;
            padding: 30px;
            color: #333;
        }

        .comprobante {
            max-width: 750px;
            margin: auto;
            background: white;
            padding: 35px;
            border-radius: 8px;
        }

        .encabezado {
            text-align: center;
            border-bottom: 2px solid #287a45;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            color: #287a45;
            margin-bottom: 5px;
        }

        .datos p {
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #287a45;
            color: white;
        }

        .total {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            margin-top: 25px;
        }

        .pie {
            text-align: center;
            margin-top: 35px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .botones {
            text-align: center;
            margin-top: 25px;
        }

        .boton {
            display: inline-block;
            padding: 12px 20px;
            background: #287a45;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        .volver {
            background: #555;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .comprobante {
                box-shadow: none;
                max-width: 100%;
                padding: 15px;
            }

            .botones {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="comprobante">

    <div class="encabezado">

        <h1>ARTESANÍAS WAYUU</h1>

        <p>Comprobante de venta</p>

        <h3>Venta #<?= $venta["id_venta"] ?></h3>

    </div>

    <div class="datos">

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
            <strong>Fecha:</strong>
            <?= htmlspecialchars($venta["fecha_venta"]) ?>
        </p>

        <p>
            <strong>Método de pago:</strong>
            <?= htmlspecialchars(ucfirst($venta["metodo_pago"])) ?>
        </p>

        <p>
            <strong>Pedido:</strong>
            #<?= $venta["id_pedido"] ?>
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

        </tbody>

    </table>

    <div class="total">

        Total pagado:
        $<?= number_format($venta["total"], 0, ',', '.') ?>

    </div>

    <div class="pie">

        <h3>¡Gracias por su compra!</h3>

        <p>Artesanías Wayuu - Productos artesanales de nuestra cultura.</p>

    </div>

    <div class="botones">

        <button class="boton" onclick="window.print()">
            Imprimir comprobante
        </button>

        <a href="ventas.php" class="boton volver">
            Volver a ventas
        </a>

    </div>

</div>

</body>
</html>
