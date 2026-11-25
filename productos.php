<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

$busqueda = trim($_GET['buscar'] ?? '');
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

try {
    $categorias = $conexion->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre ASC")->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.material, p.precio, p.imagen, p.estado,
               c.nombre AS categoria, CONCAT(a.nombre, ' ', a.apellido) AS artesano,
               COALESCE(i.stock_actual, 0) AS stock_actual
        FROM producto p
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria
        INNER JOIN artesano a ON p.id_artesano = a.id_artesano
        LEFT JOIN inventario i ON p.id_producto = i.id_producto
        WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE :busqueda OR p.material LIKE :busqueda OR a.nombre LIKE :busqueda OR a.apellido LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}
if ($filtroCategoria !== '' && ctype_digit((string)$filtroCategoria)) {
    $sql .= " AND p.id_categoria = :categoria";
    $params[':categoria'] = (int)$filtroCategoria;
}
if ($filtroEstado === '1' || $filtroEstado === '0') {
    $sql .= " AND p.estado = :estado";
    $params[':estado'] = (int)$filtroEstado;
}
$sql .= " ORDER BY p.id_producto DESC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar productos: " . $e->getMessage());
    $productos = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Productos - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Catálogo de Productos</h2>
        <a href="agregar_producto.php" class="btn btn-nuevo">+ Nuevo Producto</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="productos.php" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar producto, material o artesano..." value="<?= htmlspecialchars($busqueda) ?>" style="min-width: 260px;">
        <select name="categoria">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int)$cat['id_categoria'] ?>" <?= (string)$filtroCategoria === (string)$cat['id_categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Activos</option>
            <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button type="submit" class="btn btn-buscar">Filtrar</button>
        <?php if ($busqueda !== '' || $filtroCategoria !== '' || $filtroEstado !== ''): ?>
            <a href="productos.php" class="btn btn-limpiar">Limpiar</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>Foto</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Artesano</th>
                <th>Material</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($productos)): ?>
                <tr><td colspan="9" style="text-align: center; padding: 20px;">No se encontraron productos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($productos as $p): ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['imagen']) && file_exists(__DIR__ . '/' . $p['imagen'])): ?>
                                <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" style="width: 55px; height: 55px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                            <?php else: ?>
                                <div style="width: 55px; height: 55px; background: #f2ebe5; color: #753b24; font-size: 11px; display: flex; align-items: center; justify-content: center; border-radius: 6px; text-align: center;">Sin foto</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                            <?php if (!empty($p['descripcion'])): ?>
                                <div style="font-size: 12px; color: #666; margin-top: 4px; max-width: 240px;">
                                    <?= htmlspecialchars($p['descripcion']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['categoria']) ?></td>
                        <td><?= htmlspecialchars($p['artesano']) ?></td>
                        <td><?= htmlspecialchars($p['material'] ?? '-') ?></td>
                        <td>$<?= number_format((float)$p['precio'], 0, ',', '.') ?></td>
                        <td><?= (int)$p['stock_actual'] ?></td>
                        <td>
                            <?php if ((int)$p['estado'] === 1): ?>
                                <span class="badge badge-activo">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="acciones">
                            <a href="editar_producto.php?id=<?= (int)$p['id_producto'] ?>" class="btn btn-editar">Editar</a>
                            <?php if ((int)$p['estado'] === 1): ?>
                                <form action="desactivar_producto.php" method="POST" onsubmit="return confirm('¿Seguro que deseas desactivar este producto?');">
                                    <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
                                    <button type="submit" class="btn btn-desactivar">Desactivar</button>
                                </form>
                            <?php else: ?>
                                <form action="activar_producto.php" method="POST" onsubmit="return confirm('¿Seguro que deseas activar este producto?');">
                                    <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
                                    <button type="submit" class="btn btn-activar">Activar</button>
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
