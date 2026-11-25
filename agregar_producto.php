<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

$errores = [];
$id_artesano = '';
$id_categoria = '';
$nombre = '';
$descripcion = '';
$material = '';
$precio = '';
$stock_inicial = '0';
$stock_minimo = '1';

try {
    $artesanos = $conexion->query("SELECT id_artesano, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM artesano WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();
    $categorias = $conexion->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Error al cargar listas para producto: " . $e->getMessage());
    $artesanos = [];
    $categorias = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_artesano = $_POST['id_artesano'] ?? '';
    $id_categoria = $_POST['id_categoria'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $stock_inicial = trim($_POST['stock_inicial'] ?? '0');
    $stock_minimo = trim($_POST['stock_minimo'] ?? '1');

    if (!ctype_digit((string)$id_artesano) || (int)$id_artesano <= 0) $errores[] = 'Selecciona un artesano válido.';
    if (!ctype_digit((string)$id_categoria) || (int)$id_categoria <= 0) $errores[] = 'Selecciona una categoría válida.';
    if ($nombre === '') $errores[] = 'El nombre del producto es obligatorio.';
    if (!is_numeric($precio) || (float)$precio < 0) $errores[] = 'El precio debe ser un número mayor o igual a 0.';
    if (!ctype_digit((string)$stock_inicial) || (int)$stock_inicial < 0) $errores[] = 'El stock inicial debe ser un entero positivo.';
    if (!ctype_digit((string)$stock_minimo) || (int)$stock_minimo < 0) $errores[] = 'El stock mínimo debe ser un entero positivo.';

    // Validación de fotografía opcional
    $rutaImagenBD = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'Ocurrió un error al subir la fotografía.';
        } else {
            $tamanoMax = 3 * 1024 * 1024; // 3 MB
            if ($_FILES['imagen']['size'] > $tamanoMax) {
                $errores[] = 'La imagen no debe superar los 3 MB.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeReal = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
                finfo_close($finfo);

                $extensionesPermitidas = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($extensionesPermitidas[$mimeReal])) {
                    $errores[] = 'Formato de imagen no válido. Solo se permiten JPG, PNG o WEBP.';
                } else {
                    $directorioDestino = __DIR__ . '/assets/img/productos';
                    if (!is_dir($directorioDestino)) {
                        mkdir($directorioDestino, 0755, true);
                    }
                    $nombreArchivo = uniqid('prod_', true) . '.' . $extensionesPermitidas[$mimeReal];
                    $rutaFisica = $directorioDestino . '/' . $nombreArchivo;

                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFisica)) {
                        $rutaImagenBD = 'assets/img/productos/' . $nombreArchivo;
                    } else {
                        $errores[] = 'No se pudo guardar la imagen en el servidor.';
                    }
                }
            }
        }
    }

    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sqlProd = "INSERT INTO producto (id_artesano, id_categoria, nombre, descripcion, material, precio, imagen) 
                        VALUES (:id_artesano, :id_categoria, :nombre, :descripcion, :material, :precio, :imagen)";
            $stmtProd = $conexion->prepare($sqlProd);
            $stmtProd->execute([
                ':id_artesano'  => (int)$id_artesano,
                ':id_categoria' => (int)$id_categoria,
                ':nombre'       => $nombre,
                ':descripcion'  => $descripcion !== '' ? $descripcion : null,
                ':material'     => $material !== '' ? $material : null,
                ':precio'       => (float)$precio,
                ':imagen'       => $rutaImagenBD
            ]);

            $nuevoIdProducto = (int)$conexion->lastInsertId();

            $sqlInv = "INSERT INTO inventario (id_producto, stock_actual, stock_minimo) 
                       VALUES (:id_producto, :stock_actual, :stock_minimo)";
            $stmtInv = $conexion->prepare($sqlInv);
            $stmtInv->execute([
                ':id_producto'  => $nuevoIdProducto,
                ':stock_actual' => (int)$stock_inicial,
                ':stock_minimo' => (int)$stock_minimo
            ]);

            $conexion->commit();
            header('Location: productos.php?tipo=exito&msg=' . urlencode('Producto registrado correctamente.'));
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            if ($rutaImagenBD && file_exists(__DIR__ . '/' . $rutaImagenBD)) {
                unlink(__DIR__ . '/' . $rutaImagenBD);
            }
            error_log("Error al registrar producto: " . $e->getMessage());
            $errores[] = 'Ocurrió un error interno al guardar el producto.';
        }
    }
}

$tituloPagina = 'Nuevo Producto - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Registrar Nuevo Producto</h2>
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

    <form method="POST" action="agregar_producto.php" enctype="multipart/form-data">
        <div class="form-grupo">
            <label for="id_artesano">Artesano creador *</label>
            <select id="id_artesano" name="id_artesano" required>
                <option value="">-- Selecciona un artesano --</option>
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
                <option value="">-- Selecciona una categoría --</option>
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
            <label for="imagen">Fotografía del producto (JPG, PNG o WEBP - Máx. 3 MB)</label>
            <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-grupo">
            <label for="material">Material</label>
            <input type="text" id="material" name="material" maxlength="80" value="<?= htmlspecialchars($material) ?>">
        </div>

        <div class="form-grupo">
            <label for="precio">Precio (COP) *</label>
            <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars($precio) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="stock_inicial">Stock inicial *</label>
            <input type="number" id="stock_inicial" name="stock_inicial" min="0" value="<?= htmlspecialchars($stock_inicial) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="stock_minimo">Stock mínimo de alerta *</label>
            <input type="number" id="stock_minimo" name="stock_minimo" min="0" value="<?= htmlspecialchars($stock_minimo) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <button type="submit" class="btn btn-nuevo">Guardar Producto</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>