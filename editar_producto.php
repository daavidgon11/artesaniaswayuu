<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: productos.php?tipo=error&msg=' . urlencode('ID de producto no válido.'));
    exit;
}

$errores = [];

try {
    $stmtProd = $conexion->prepare("SELECT * FROM producto WHERE id_producto = :id");
    $stmtProd->execute([':id' => $id]);
    $producto = $stmtProd->fetch();

    if (!$producto) {
        header('Location: productos.php?tipo=error&msg=' . urlencode('El producto no existe.'));
        exit;
    }

    $artesanos = $conexion->query("SELECT id_artesano, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM artesano ORDER BY nombre ASC")->fetchAll();
    $categorias = $conexion->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Error al cargar producto para editar: " . $e->getMessage());
    header('Location: productos.php?tipo=error&msg=' . urlencode('Error interno al consultar el producto.'));
    exit;
}

$id_artesano  = $producto['id_artesano'];
$id_categoria = $producto['id_categoria'];
$nombre       = $producto['nombre'];
$descripcion  = $producto['descripcion'] ?? '';
$material     = $producto['material'] ?? '';
$precio       = $producto['precio'];
$imagenActual = $producto['imagen'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_artesano  = $_POST['id_artesano'] ?? '';
    $id_categoria = $_POST['id_categoria'] ?? '';
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $material     = trim($_POST['material'] ?? '');
    $precio       = trim($_POST['precio'] ?? '');

    if (!ctype_digit((string)$id_artesano) || (int)$id_artesano <= 0) $errores[] = 'Selecciona un artesano válido.';
    if (!ctype_digit((string)$id_categoria) || (int)$id_categoria <= 0) $errores[] = 'Selecciona una categoría válida.';
    if ($nombre === '') $errores[] = 'El nombre del producto es obligatorio.';
    if (!is_numeric($precio) || (float)$precio < 0) $errores[] = 'El precio debe ser mayor o igual a 0.';

    $nuevaRutaImagen = $imagenActual;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'Error al subir la nueva fotografía.';
        } elseif ($_FILES['imagen']['size'] > 3 * 1024 * 1024) {
            $errores[] = 'La imagen no debe superar los 3 MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeReal = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
            finfo_close($finfo);

            $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($permitidos[$mimeReal])) {
                $errores[] = 'Solo se permiten imágenes JPG, PNG o WEBP.';
            } else {
                $dirDestino = __DIR__ . '/assets/img/productos';
                if (!is_dir($dirDestino)) {
                    mkdir($dirDestino, 0755, true);
                }
                $nombreArchivo = uniqid('prod_', true) . '.' . $permitidos[$mimeReal];
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dirDestino . '/' . $nombreArchivo)) {
                    $nuevaRutaImagen = 'assets/img/productos/' . $nombreArchivo;
                } else {
                    $errores[] = 'No se pudo guardar la nueva imagen.';
                }
            }
        }
    }

    if (empty($errores)) {
        try {
            $sqlUpd = "UPDATE producto 
                       SET id_artesano = :id_artesano, id_categoria = :id_categoria, nombre = :nombre, 
                           descripcion = :descripcion, material = :material, precio = :precio, imagen = :imagen 
                       WHERE id_producto = :id";
            $stmtUpd = $conexion->prepare($sqlUpd);
            $stmtUpd->execute([
                ':id_artesano'  => (int)$id_artesano,
                ':id_categoria' => (int)$id_categoria,
                ':nombre'       => $nombre,
                ':descripcion'  => $descripcion !== '' ? $descripcion : null,
                ':material'     => $material !== '' ? $material : null,
                ':precio'       => (float)$precio,
                ':imagen'       => $nuevaRutaImagen,
                ':id'           => $id
            ]);

            // Eliminar foto anterior si se subió una nueva
            if ($nuevaRutaImagen !== $imagenActual && $imagenActual && file_exists(__DIR__ . '/' . $imagenActual)) {
                unlink(__DIR__ . '/' . $imagenActual);
            }

            header('Location: productos.php?tipo=exito&msg=' . urlencode('Producto actualizado correctamente.'));
            exit;
        } catch (PDOException $e) {
            error_log("Error al actualizar producto: " . $e->getMessage());
            $errores[] = 'Error interno al actualizar el producto.';
        }
    }
}

$tituloPagina = 'Editar Producto - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Editar Producto #<?= (int)$id ?></h2>
        <a href="productos.php" class="btn btn-limpiar">← Volver</a>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alerta alerta-error">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errores as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="editar_producto.php?id=<?= (int)$id ?>" enctype="multipart/form-data">
        <div class="form-grupo">
            <label for="id_artesano">Artesano creador *</label>
            <select id="id_artesano" name="id_artesano" required>
                <?php foreach ($artesanos as $art): ?>
                    <option value="<?= (int)$art['id_artesano'] ?>" <?= (string)$id_artesano === (string)$art['id_artesano'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($art['nombre_completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grupo">
            <label for="id_categoria">Categoría *</label>
            <select id="id_categoria" name="id_categoria" required>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int)$cat['id_categoria'] ?>" <?= (string)$id_categoria === (string)$cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grupo">
            <label for="nombre">Nombre del producto *</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>

        <div class="form-grupo">
            <label>Fotografía actual</label>
            <?php if (!empty($imagenActual) && file_exists(__DIR__ . '/' . $imagenActual)): ?>
                <div style="margin-bottom: 10px;">
                    <img src="<?= htmlspecialchars($imagenActual) ?>" alt="Foto actual" style="max-width: 140px; border-radius: 6px; border: 1px solid #ccc;">
                </div>
            <?php else: ?>
                <p style="font-size: 13px; color: #777; margin: 0 0 8px 0;">Este producto aún no tiene fotografía.</p>
            <?php endif; ?>
            <label for="imagen">Cambiar fotografía (opcional - JPG, PNG o WEBP)</label>
            <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-grupo">
            <label for="material">Material</label>
            <input type="text" id="material" name="material" maxlength="80" value="<?= htmlspecialchars($material) ?>">
        </div>

        <div class="form-grupo">
            <label for="precio">Precio (COP) *</label>
            <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars((string)$precio) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <button type="submit" class="btn btn-nuevo">Guardar Cambios</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
