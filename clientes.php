<?php
require_once 'config/conexion.php';

$busqueda = trim($_GET['buscar'] ?? '');
$filtroEstado = $_GET['estado'] ?? '';

$sql = "SELECT id_cliente, nombre, documento, telefono, correo, direccion, estado FROM cliente WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (nombre LIKE :busqueda OR documento LIKE :busqueda OR correo LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}
if ($filtroEstado === '1' || $filtroEstado === '0') {
    $sql .= " AND estado = :estado";
    $params[':estado'] = (int)$filtroEstado;
}
$sql .= " ORDER BY id_cliente DESC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar clientes: " . $e->getMessage());
    $clientes = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Clientes - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Gestión de Clientes</h2>
        <a href="agregar_cliente.php" class="btn btn-nuevo">+ Nuevo Cliente</a>
    </div>

    <?php if ($mensaje !== '') { ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php } ?>

    <form method="GET" action="clientes.php" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar por nombre, documento o correo..." value="<?= htmlspecialchars($busqueda) ?>" style="min-width: 280px;">
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Activos</option>
            <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button type="submit" class="btn btn-buscar">Filtrar</button>
        <?php if ($busqueda !== '' || $filtroEstado !== '') { ?>
            <a href="clientes.php" class="btn btn-limpiar">Limpiar</a>
        <?php } ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clientes)) { ?>
                <tr><td colspan="8" style="text-align: center; padding: 20px;">No se encontraron clientes registrados.</td></tr>
            <?php } else { ?>
                <?php foreach ($clientes as $c) { ?>
                    <tr>
                        <td><?= (int)$c['id_cliente'] ?></td>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><?= htmlspecialchars($c['documento'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['telefono'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['correo'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['direccion'] ?? '-') ?></td>
                        <td>
                            <?php if ((int)$c['estado'] === 1) { ?>
                                <span class="badge badge-activo">Activo</span>
                            <?php } else { ?>
                                <span class="badge badge-inactivo">Inactivo</span>
                            <?php } ?>
                        </td>
                        <td class="acciones">
                            <a href="editar_cliente.php?id=<?= (int)$c['id_cliente'] ?>" class="btn btn-editar">Editar</a>
                            <?php if ((int)$c['estado'] === 1) { ?>
                                <form action="desactivar_cliente.php" method="POST" onsubmit="return confirm('¿Seguro que deseas desactivar este cliente?');">
                                    <input type="hidden" name="id" value="<?= (int)$c['id_cliente'] ?>">
                                    <button type="submit" class="btn btn-desactivar">Desactivar</button>
                                </form>
                            <?php } else { ?>
                                <form action="activar_cliente.php" method="POST" onsubmit="return confirm('¿Seguro que deseas activar este cliente?');">
                                    <input type="hidden" name="id" value="<?= (int)$c['id_cliente'] ?>">
                                    <button type="submit" class="btn btn-activar">Activar</button>
                                </form>
                            <?php } ?>
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
