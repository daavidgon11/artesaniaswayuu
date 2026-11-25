<?php
require_once 'config/conexion.php';

$errores = [];
$idClienteSeleccionado = $_POST['id_cliente'] ?? '';

try {
    $clientes = $conexion->query("SELECT id_cliente, nombre, documento FROM cliente WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();
    $productos = $conexion->query("
        SELECT p.id_producto, p.nombre, p.precio, COALESCE(i.stock_actual, 0) AS stock_actual
        FROM producto p
        LEFT JOIN inventario i ON p.id_producto = i.id_producto
        WHERE p.estado = 1 AND COALESCE(i.stock_actual, 0) > 0
        ORDER BY p.nombre ASC
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error al cargar datos para pedido: " . $e->getMessage());
    $clientes = [];
    $productos = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
    $idsProductos = $_POST['id_producto'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];

    if (!$idCliente || $idCliente <= 0) {
        $errores[] = 'Debes seleccionar un cliente activo.';
    }

    // Agrupar cantidades si el usuario seleccionó el mismo producto en varias filas
    $itemsAgrupados = [];
    if (is_array($idsProductos) && is_array($cantidades)) {
        for ($i = 0; $i < count($idsProductos); $i++) {
            $idProd = filter_var($idsProductos[$i], FILTER_VALIDATE_INT);
            $cant = filter_var($cantidades[$i], FILTER_VALIDATE_INT);

            if ($idProd && $idProd > 0 && $cant && $cant > 0) {
                if (!isset($itemsAgrupados[$idProd])) {
                    $itemsAgrupados[$idProd] = 0;
                }
                $itemsAgrupados[$idProd] += $cant;
            }
        }
    }

    if (empty($itemsAgrupados)) {
        $errores[] = 'Debes agregar al menos un producto con cantidad válida mayor a 0.';
    }

    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // 1. Verificar cliente activo
            $stmtCli = $conexion->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = :id AND estado = 1");
            $stmtCli->execute([':id' => $idCliente]);
            if (!$stmtCli->fetch()) {
                throw new Exception('El cliente seleccionado no existe o está inactivo.');
            }

            // 2. Validar stock con bloqueo de fila (FOR UPDATE) y calcular total real
            $totalPedido = 0;
            $detallesValidados = [];

            $stmtProdLock = $conexion->prepare("
                SELECT p.nombre, p.precio, p.estado, i.stock_actual
                FROM producto p
                INNER JOIN inventario i ON p.id_producto = i.id_producto
                WHERE p.id_producto = :id
                FOR UPDATE
            ");

            foreach ($itemsAgrupados as $idProd => $cantSolicitada) {
                $stmtProdLock->execute([':id' => $idProd]);
                $prodData = $stmtProdLock->fetch();

                if (!$prodData || (int)$prodData['estado'] !== 1) {
                    throw new Exception("Uno de los productos seleccionados ya no está disponible.");
                }
                if ((int)$prodData['stock_actual'] < $cantSolicitada) {
                    throw new Exception("Stock insuficiente para '{$prodData['nombre']}'. Disponible: {$prodData['stock_actual']}.");
                }

                $precioUnitario = (float)$prodData['precio'];
                $subtotal = $precioUnitario * $cantSolicitada;
                $totalPedido += $subtotal;

                $detallesValidados[] = [
                    'id_producto'     => $idProd,
                    'cantidad'        => $cantSolicitada,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal
                ];
            }

            // 3. Insertar cabecera del pedido
            $stmtPedido = $conexion->prepare("INSERT INTO pedido (id_cliente, estado, total) VALUES (:id_cliente, 'pendiente', :total)");
            $stmtPedido->execute([
                ':id_cliente' => $idCliente,
                ':total'      => $totalPedido
            ]);
            $idPedidoNuevo = (int)$conexion->lastInsertId();

            // 4. Insertar detalles y descontar inventario
            $stmtDetalle = $conexion->prepare("
                INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :subtotal)
            ");
            $stmtDescontar = $conexion->prepare("
                UPDATE inventario SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id_producto
            ");

            foreach ($detallesValidados as $det) {
                $stmtDetalle->execute([
                    ':id_pedido'       => $idPedidoNuevo,
                    ':id_producto'     => $det['id_producto'],
                    ':cantidad'        => $det['cantidad'],
                    ':precio_unitario' => $det['precio_unitario'],
                    ':subtotal'        => $det['subtotal']
                ]);

                $stmtDescontar->execute([
                    ':cantidad'    => $det['cantidad'],
                    ':id_producto' => $det['id_producto']
                ]);
            }

            $conexion->commit();
            header('Location: pedidos.php?tipo=exito&msg=' . urlencode("Pedido #$idPedidoNuevo registrado correctamente."));
            exit;

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error al crear pedido: " . $e->getMessage());
            $errores[] = $e instanceof PDOException ? 'Error interno de base de datos al registrar el pedido.' : $e->getMessage();
        }
    }
}

$tituloPagina = 'Nuevo Pedido - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Registrar Nuevo Pedido</h2>
        <a href="pedidos.php" class="btn btn-limpiar">← Volver</a>
    </div>

    <?php if (!empty($errores)) { ?>
        <div class="alerta alerta-error">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errores as $err) { ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <form method="POST" action="agregar_pedido.php">
        <div class="form-grupo">
            <label for="id_cliente">Cliente *</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">-- Selecciona un cliente --</option>
                <?php foreach ($clientes as $c) { ?>
                    <option value="<?= (int)$c['id_cliente'] ?>" <?= (string)$idClienteSeleccionado === (string)$c['id_cliente'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?> <?= $c['documento'] ? '(' . htmlspecialchars($c['documento']) . ')' : '' ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <h3 style="color: #753b24; margin-top: 25px;">Productos del Pedido</h3>
        <table id="tabla-items" style="margin-bottom: 15px;">
            <thead>
                <tr>
                    <th>Producto (Precio / Disponible)</th>
                    <th style="width: 150px;">Cantidad</th>
                    <th style="width: 110px;">Acción</th>
                </tr>
            </thead>
            <tbody id="contenedor-filas">
                <tr class="fila-item">
                    <td>
                        <select name="id_producto[]" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Selecciona un producto --</option>
                            <?php foreach ($productos as $p) { ?>
                                <option value="<?= (int)$p['id_producto'] ?>">
                                    <?= htmlspecialchars($p['nombre']) ?> — $<?= number_format((float)$p['precio'], 0, ',', '.') ?> (Stock: <?= (int)$p['stock_actual'] ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="cantidad[]" min="1" value="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    </td>
                    <td>
                        <button type="button" class="btn btn-desactivar" onclick="eliminarFila(this)">Quitar</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-bottom: 25px;">
            <button type="button" class="btn btn-editar" onclick="agregarFila()">+ Agregar otro producto</button>
        </div>

        <button type="submit" class="btn btn-nuevo">Guardar Pedido</button>
    </form>
</div>

<script>
function agregarFila() {
    const tbody = document.getElementById('contenedor-filas');
    const primeraFila = tbody.querySelector('.fila-item');
    const nuevaFila = primeraFila.cloneNode(true);
    nuevaFila.querySelector('select').value = '';
    nuevaFila.querySelector('input').value = 1;
    tbody.appendChild(nuevaFila);
}

function eliminarFila(boton) {
    const tbody = document.getElementById('contenedor-filas');
    if (tbody.querySelectorAll('.fila-item').length > 1) {
        boton.closest('tr').remove();
    } else {
        alert('El pedido debe tener al menos un producto.');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>


</body>
</html>



