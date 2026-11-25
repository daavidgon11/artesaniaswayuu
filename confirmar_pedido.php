<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$mensaje = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_pedido = (int) ($_POST["id_pedido"] ?? 0);

    try {

        if ($id_pedido <= 0) {
            throw new Exception("El número del pedido no es válido.");
        }

        $conexion->beginTransaction();

        // Consultar y bloquear el pedido
        $sqlPedido = "SELECT id_pedido, estado
                      FROM pedido
                      WHERE id_pedido = ?
                      FOR UPDATE";

        $consultaPedido = $conexion->prepare($sqlPedido);
        $consultaPedido->execute([$id_pedido]);
        $pedido = $consultaPedido->fetch();

        if (!$pedido) {
            throw new Exception("El pedido no existe.");
        }

        if ($pedido["estado"] !== "pendiente") {
            throw new Exception("Este pedido ya fue procesado o no está pendiente.");
        }

        // Consultar los productos del pedido
        $sqlDetalles = "SELECT id_producto, cantidad
                        FROM detalle_pedido
                        WHERE id_pedido = ?";

        $consultaDetalles = $conexion->prepare($sqlDetalles);
        $consultaDetalles->execute([$id_pedido]);
        $detalles = $consultaDetalles->fetchAll();

        if (count($detalles) === 0) {
            throw new Exception("El pedido no contiene productos.");
        }

        // Preparar consultas de inventario
        $sqlInventario = "SELECT stock_actual
                          FROM inventario
                          WHERE id_producto = ?
                          FOR UPDATE";

        $consultaInventario = $conexion->prepare($sqlInventario);

        $sqlActualizar = "UPDATE inventario
                          SET stock_actual = stock_actual - ?,
                              fecha_actualizacion = CURRENT_TIMESTAMP
                          WHERE id_producto = ?";

        $consultaActualizar = $conexion->prepare($sqlActualizar);

        // Verificar y descontar cada producto
        foreach ($detalles as $detalle) {

            $id_producto = (int) $detalle["id_producto"];
            $cantidad = (int) $detalle["cantidad"];

            $consultaInventario->execute([$id_producto]);
            $inventario = $consultaInventario->fetch();

            if (!$inventario) {
                throw new Exception(
                    "No existe inventario para el producto ID " . $id_producto
                );
            }

            if ($cantidad <= 0 || $cantidad > $inventario["stock_actual"]) {
                throw new Exception(
                    "Inventario insuficiente para el producto ID " . $id_producto
                );
            }

            $consultaActualizar->execute([
                $cantidad,
                $id_producto
            ]);
        }

        // Cambiar estado del pedido
        $sqlConfirmar = "UPDATE pedido
                         SET estado = 'confirmado'
                         WHERE id_pedido = ?";

        $consultaConfirmar = $conexion->prepare($sqlConfirmar);
        $consultaConfirmar->execute([$id_pedido]);

        // Guardar todos los cambios
        $conexion->commit();

        $exito = true;
        $mensaje = "El pedido #" . $id_pedido .
                   " fue confirmado y el inventario se actualizó correctamente.";

    } catch (Exception $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        $mensaje = $e->getMessage();
    }

} else {

    $mensaje = "Solicitud no válida.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Confirmar pedido</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .contenedor {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }

        .mensaje {
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .exito {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            padding: 11px 18px;
            background: #287a45;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h2>Resultado de la confirmación</h2>

    <div class="mensaje <?= $exito ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>

    <a href="pedidos.php">Volver a pedidos</a>

</div>

</body>
</html>
