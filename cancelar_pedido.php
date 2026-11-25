<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pedidos.php?tipo=error&msg=' . urlencode('Método no permitido.'));
    exit;
}

$idPedido = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);

if (!$idPedido || $idPedido <= 0) {
    header('Location: pedidos.php?tipo=error&msg=' . urlencode('ID de pedido no válido.'));
    exit;
}

try {
    $conexion->beginTransaction();

    // 1. Bloquear el pedido para evitar modificaciones simultáneas
    $stmtPed = $conexion->prepare("SELECT id_pedido, estado FROM pedido WHERE id_pedido = :id FOR UPDATE");
    $stmtPed->execute([':id' => $idPedido]);
    $pedido = $stmtPed->fetch();

    if (!$pedido) {
        throw new Exception('El pedido no existe.');
    }

    if ($pedido['estado'] !== 'pendiente') {
        throw new Exception('Solo se pueden cancelar pedidos que estén en estado pendiente.');
    }

    // 2. Verificar que no tenga una venta ya registrada
    $stmtVenta = $conexion->prepare("SELECT id_venta FROM venta WHERE id_pedido = :id");
    $stmtVenta->execute([':id' => $idPedido]);
    if ($stmtVenta->fetch()) {
        throw new Exception('No se puede cancelar un pedido que ya fue facturado.');
    }

    // 3. Obtener los productos y cantidades del detalle del pedido
    $stmtDet = $conexion->prepare("SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = :id");
    $stmtDet->execute([':id' => $idPedido]);
    $detalles = $stmtDet->fetchAll();

    // 4. Devolver el stock a la tabla inventario
    $stmtDevolver = $conexion->prepare("UPDATE inventario SET stock_actual = stock_actual + :cantidad WHERE id_producto = :id_producto");

    foreach ($detalles as $item) {
        $stmtDevolver->execute([
            ':cantidad'    => (int)$item['cantidad'],
            ':id_producto' => (int)$item['id_producto']
        ]);
    }

    // 5. Cambiar el estado del pedido a 'cancelado'
    $stmtCancelar = $conexion->prepare("UPDATE pedido SET estado = 'cancelado' WHERE id_pedido = :id");
    $stmtCancelar->execute([':id' => $idPedido]);

    $conexion->commit();
    header('Location: pedidos.php?tipo=exito&msg=' . urlencode("Pedido #$idPedido cancelado y stock devuelto al inventario."));
    exit;

} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log("Error al cancelar pedido: " . $e->getMessage());
    $mensajeError = $e instanceof PDOException ? 'Error interno al cancelar el pedido.' : $e->getMessage();
    header('Location: pedidos.php?tipo=error&msg=' . urlencode($mensajeError));
    exit;
}
