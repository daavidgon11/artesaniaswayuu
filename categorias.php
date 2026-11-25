<?php
require_once 'config/conexion.php';

$sql = "SELECT c.id_categoria, c.nombre, c.descripcion, COUNT(p.id_producto) AS total_productos
        FROM categoria c
        LEFT JOIN producto p ON c.id_categoria = p.id_categoria
        GROUP BY c.id_categoria, c.nombre, c.descripcion
        ORDER BY c.id_categoria DESC";

try {
    $categorias = $conexion->query($sql)->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar categorías: " . $e->getMessage());
    $categorias = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Categorías - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Categorías de Productos</h2>
        <a href="agregar_categoria.php" class="btn btn-nuevo">+ Nueva Categoría</a>
    </div>

    <?php if ($mensaje !== '') { ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php } ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Productos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categorias)) { ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">No hay categorías registradas.</td></tr>
            <?php } else { ?>
                <?php foreach ($categorias as $cat) { ?>
                    <tr>
                        <td><?= (int)$cat['id_categoria'] ?></td>
                        <td><strong><?= htmlspecialchars($cat['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($cat['descripcion'] ?? '-') ?></td>
                        <td><?= (int)$cat['total_productos'] ?></td>
                        <td class="acciones">
                            <a href="editar_categoria.php?id=<?= (int)$cat['id_categoria'] ?>" class="btn btn-editar">Editar</a>
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
