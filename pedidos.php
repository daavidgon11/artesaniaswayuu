<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

$filtroEstado = $_GET['estado'] ?? '';
$sql = "SELECT p.id_pedido, p.fecha_pedido, p.estado, p.total, c.nombre AS cliente
        FROM pedido p
        INNER JOIN cliente c ON p.id_cliente = c.id_cliente
        WHERE 1=1";
$params = [];

if (in_array($filtroEstado, ['pendiente', 'confirmado', 'cancelado'], true)) {
    $sql .= " AND p.estado = :estado";
    $params[':estado'] = $filtroEstado;
}
$sql .= " ORDER BY p.id_pedido DESC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar pedidos: " . $e->getMessage());
    $pedidos = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Pedidos - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Gestión de Pedidos</h2>
        <a href="agregar_pedido.php" class="btn btn-nuevo">+ Nuevo Pedido</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="pedidos.php" class="filtros">
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="confirmado" <?= $filtroEstado === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
            <option value="cancelado" <?= $filtroEstado === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>
        <button type="submit" class="btn btn-buscar">Filtrar</button>
        <?php if ($filtroEstado !== ''): ?>
            <a href="pedidos.php" class="btn btn-limpiar">Limpiar</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 20px;">No se encontraron pedidos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($pedidos as $ped): ?>
                    <tr>
                        <td>#<?= (int)$ped['id_pedido'] ?></td>
                        <td><?= htmlspecialchars($ped['cliente']) ?></td>
                        <td><?= htmlspecialchars($ped['fecha_pedido']) ?></td>
                        <td>$<?= number_format((float)$ped['total'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($ped['estado']) ?>">
                                <?= ucfirst(htmlspecialchars($ped['estado'])) ?>
                            </span>
                        </td>
                        <td class="acciones">
                            <a href="detalle_pedido.php?id=<?= (int)$ped['id_pedido'] ?>" class="btn btn-ver">Detalle</a>
                            <?php if ($ped['estado'] === 'pendiente'): ?>
                                <form action="confirmar_pedido.php" method="POST" onsubmit="return confirm('¿Confirmar este pedido?');">
                                    <input type="hidden" name="id_pedido" value="<?= (int)$ped['id_pedido'] ?>">
                                    <button type="submit" class="btn btn-activar">Confirmar</button>
                                </form>

                                <form action="cancelar_pedido.php" method="POST" onsubmit="return confirm('¿Seguro que deseas cancelar este pedido? Las unidades volverán al inventario.');">
                                    <input type="hidden" name="id_pedido" value="<?= (int)$ped['id_pedido'] ?>">
                                    <button type="submit" class="btn btn-desactivar">Cancelar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>


