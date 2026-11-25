<?php
require_once 'config/conexion.php';

$busqueda = trim($_GET['buscar'] ?? '');
$filtroEstado = $_GET['estado'] ?? '';

$sql = "SELECT id_artesano, nombre, apellido, documento, telefono, comunidad, direccion, estado FROM artesano WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (nombre LIKE :busqueda OR apellido LIKE :busqueda OR documento LIKE :busqueda OR comunidad LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}
if ($filtroEstado === '1' || $filtroEstado === '0') {
    $sql .= " AND estado = :estado";
    $params[':estado'] = (int)$filtroEstado;
}
$sql .= " ORDER BY id_artesano DESC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $artesanos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar artesanos: " . $e->getMessage());
    $artesanos = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Artesanos - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Gestión de Artesanos</h2>
        <a href="agregar_artesano.php" class="btn btn-nuevo">+ Nuevo Artesano</a>
    </div>

    <?php if ($mensaje !== '') { ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php } ?>

    <form method="GET" action="artesanos.php" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar por nombre, documento o comunidad..." value="<?= htmlspecialchars($busqueda) ?>" style="min-width: 280px;">
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Activos</option>
            <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button type="submit" class="btn btn-buscar">Filtrar</button>
        <?php if ($busqueda !== '' || $filtroEstado !== '') { ?>
            <a href="artesanos.php" class="btn btn-limpiar">Limpiar</a>
        <?php } ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Comunidad</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($artesanos)) { ?>
                <tr><td colspan="8" style="text-align: center; padding: 20px;">No se encontraron artesanos registrados.</td></tr>
            <?php } else { ?>
                <?php foreach ($artesanos as $a) { ?>
                    <tr>
                        <td><?= (int)$a['id_artesano'] ?></td>
                        <td><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?></td>
                        <td><?= htmlspecialchars($a['documento']) ?></td>
                        <td><?= htmlspecialchars($a['telefono'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($a['comunidad'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($a['direccion'] ?? '-') ?></td>
                        <td>
                            <?php if ((int)$a['estado'] === 1) { ?>
                                <span class="badge badge-activo">Activo</span>
                            <?php } else { ?>
                                <span class="badge badge-inactivo">Inactivo</span>
                            <?php } ?>
                        </td>
                        <td class="acciones">
                            <a href="editar_artesano.php?id=<?= (int)$a['id_artesano'] ?>" class="btn btn-editar">Editar</a>
                            <?php if ((int)$a['estado'] === 1) { ?>
                                <form action="desactivar_artesano.php" method="POST" onsubmit="return confirm('¿Seguro que deseas desactivar este artesano?');">
                                    <input type="hidden" name="id" value="<?= (int)$a['id_artesano'] ?>">
                                    <button type="submit" class="btn btn-desactivar">Desactivar</button>
                                </form>
                            <?php } else { ?>
                                <form action="activar_artesano.php" method="POST" onsubmit="return confirm('¿Seguro que deseas activar este artesano?');">
                                    <input type="hidden" name="id" value="<?= (int)$a['id_artesano'] ?>">
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
