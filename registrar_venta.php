<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$mensaje = "";
$exito = false;

// Consultar pedidos confirmados que todavía no tienen venta
$sqlPedidos = "SELECT p.id_pedido, c.nombre AS cliente,
                      p.total
               FROM pedido p
               INNER JOIN cliente c ON p.id_cliente = c.id_cliente
               LEFT JOIN venta v ON p.id_pedido = v.id_pedido
               WHERE p.estado = 'confirmado'
                 AND v.id_venta IS NULL
               ORDER BY p.id_pedido DESC";

$pedidos = $conexion->query($sqlPedidos)->fetchAll();


// Registrar venta
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_pedido = (int) ($_POST["id_pedido"] ?? 0);
    $metodo_pago = $_POST["metodo_pago"] ?? "";

    $metodosPermitidos = [
        "efectivo",
        "transferencia",
        "tarjeta",
        "otro"
    ];

    try {

        if ($id_pedido <= 0) {
            throw new Exception("Selecciona un pedido válido.");
        }

        if (!in_array($metodo_pago, $metodosPermitidos, true)) {
            throw new Exception("Selecciona un método de pago válido.");
        }

        $conexion->beginTransaction();

        // Consultar y bloquear el pedido
        $sqlPedido = "SELECT id_pedido, total, estado
                      FROM pedido
                      WHERE id_pedido = ?
                      FOR UPDATE";

        $consultaPedido = $conexion->prepare($sqlPedido);
        $consultaPedido->execute([$id_pedido]);
        $pedido = $consultaPedido->fetch();

        if (!$pedido) {
            throw new Exception("El pedido no existe.");
        }

        if ($pedido["estado"] !== "confirmado") {
            throw new Exception("Solo se pueden vender pedidos confirmados.");
        }

        // Verificar que no tenga una venta registrada
        $sqlVerificar = "SELECT id_venta
                         FROM venta
                         WHERE id_pedido = ?";

        $consultaVerificar = $conexion->prepare($sqlVerificar);
        $consultaVerificar->execute([$id_pedido]);

        if ($consultaVerificar->fetch()) {
            throw new Exception("Este pedido ya tiene una venta registrada.");
        }

        // Registrar venta
        $sqlVenta = "INSERT INTO venta
                     (id_pedido, metodo_pago, total)
                     VALUES (?, ?, ?)";

        $consultaVenta = $conexion->prepare($sqlVenta);
        $consultaVenta->execute([
            $id_pedido,
            $metodo_pago,
            $pedido["total"]
        ]);

        $conexion->commit();

        $exito = true;
        $mensaje = "La venta del pedido #" . $id_pedido .
                   " fue registrada correctamente.";

        // Actualizar lista de pedidos disponibles
        $sqlPedidos = "SELECT p.id_pedido, c.nombre AS cliente,
                              p.total
                       FROM pedido p
                       INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                       LEFT JOIN venta v ON p.id_pedido = v.id_pedido
                       WHERE p.estado = 'confirmado'
                         AND v.id_venta IS NULL
                       ORDER BY p.id_pedido DESC";

        $pedidos = $conexion->query($sqlPedidos)->fetchAll();

    } catch (Exception $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        $mensaje = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar venta</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .contenedor {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2 {
            color: #333;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button, .volver {
            display: inline-block;
            margin-top: 20px;
            padding: 11px 18px;
            border: none;
            border-radius: 5px;
            background: #287a45;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .volver {
            background: #555;
        }

        .mensaje {
            padding: 13px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .exito {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h2>Registrar nueva venta</h2>

    <?php if (!empty($mensaje)): ?>

        <div class="mensaje <?= $exito ? 'exito' : 'error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>

    <?php endif; ?>

    <?php if (count($pedidos) > 0): ?>

        <form method="POST" action="">

            <label>Seleccionar pedido confirmado:</label>

            <select name="id_pedido" required>

                <option value="">Seleccione un pedido</option>

                <?php foreach ($pedidos as $pedido): ?>

                    <option value="<?= $pedido['id_pedido'] ?>">
                        Pedido #<?= $pedido['id_pedido'] ?>
                        - <?= htmlspecialchars($pedido['cliente']) ?>
                        - $<?= number_format($pedido['total'], 0, ',', '.') ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label>Método de pago:</label>

            <select name="metodo_pago" required>

                <option value="">Seleccione el método de pago</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otro">Otro</option>

            </select>

            <button type="submit">
                Registrar venta
            </button>

            <a href="ventas.php" class="volver">
                Volver
            </a>

        </form>

    <?php else: ?>

        <p>
            No hay pedidos confirmados pendientes de registrar como venta.
        </p>

        <a href="ventas.php" class="volver">
            Ver ventas
        </a>

    <?php endif; ?>

</div>

</body>
</html>
