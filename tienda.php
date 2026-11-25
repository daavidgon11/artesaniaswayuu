<?php
require_once 'config/conexion.php';

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['realizar_pedido'])) {
    $idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    $cantidad   = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $nombreCli  = trim($_POST['nombre_cliente'] ?? '');
    $docCli     = trim($_POST['documento_cliente'] ?? '');
    $telCli     = trim($_POST['telefono_cliente'] ?? '');
    $dirCli     = trim($_POST['direccion_cliente'] ?? '');

    if (!$idProducto || $idProducto <= 0 || !$cantidad || $cantidad <= 0) {
        $mensaje = 'Selecciona una cantidad válida.';
        $tipoMensaje = 'error';
    } elseif ($nombreCli === '' || $docCli === '' || $telCli === '') {
        $mensaje = 'Por favor completa tu nombre, documento y teléfono para coordinar la entrega.';
        $tipoMensaje = 'error';
    } else {
        try {
            $conexion->beginTransaction();

            $stmtProd = $conexion->prepare("
                SELECT p.nombre, p.precio, p.estado, i.stock_actual
                FROM producto p
                INNER JOIN inventario i ON p.id_producto = i.id_producto
                WHERE p.id_producto = :id
                FOR UPDATE
            ");
            $stmtProd->execute([':id' => $idProducto]);
            $prod = $stmtProd->fetch();

            if (!$prod || (int)$prod['estado'] !== 1) {
                throw new Exception('Esta artesanía ya no está disponible.');
            }
            if ((int)$prod['stock_actual'] < $cantidad) {
                throw new Exception("Lo sentimos, solo quedan {$prod['stock_actual']} unidades disponibles de '{$prod['nombre']}'.");
            }

            $stmtCli = $conexion->prepare("SELECT id_cliente FROM cliente WHERE documento = :doc LIMIT 1");
            $stmtCli->execute([':doc' => $docCli]);
            $clienteExistente = $stmtCli->fetch();

            if ($clienteExistente) {
                $idCliente = (int)$clienteExistente['id_cliente'];
                $stmtUpdCli = $conexion->prepare("UPDATE cliente SET telefono = :tel, direccion = COALESCE(NULLIF(:dir, ''), direccion), estado = 1 WHERE id_cliente = :id");
                $stmtUpdCli->execute([':tel' => $telCli, ':dir' => $dirCli, ':id' => $idCliente]);
            } else {
                $stmtNewCli = $conexion->prepare("INSERT INTO cliente (nombre, documento, telefono, direccion, estado) VALUES (:nom, :doc, :tel, :dir, 1)");
                $stmtNewCli->execute([
                    ':nom' => $nombreCli,
                    ':doc' => $docCli,
                    ':tel' => $telCli,
                    ':dir' => $dirCli !== '' ? $dirCli : null
                ]);
                $idCliente = (int)$conexion->lastInsertId();
            }

            $precioUnit = (float)$prod['precio'];
            $totalPedido = $precioUnit * $cantidad;

            $stmtPed = $conexion->prepare("INSERT INTO pedido (id_cliente, estado, total) VALUES (:id_cli, 'pendiente', :total)");
            $stmtPed->execute([':id_cli' => $idCliente, ':total' => $totalPedido]);
            $idPedido = (int)$conexion->lastInsertId();

            $stmtDet = $conexion->prepare("
                INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                VALUES (:id_ped, :id_prod, :cant, :precio, :sub)
            ");
            $stmtDet->execute([
                ':id_ped'  => $idPedido,
                ':id_prod' => $idProducto,
                ':cant'    => $cantidad,
                ':precio'  => $precioUnit,
                ':sub'     => $totalPedido
            ]);

            $stmtStock = $conexion->prepare("UPDATE inventario SET stock_actual = stock_actual - :cant WHERE id_producto = :id_prod");
            $stmtStock->execute([':cant' => $cantidad, ':id_prod' => $idProducto]);

            $conexion->commit();
            $mensaje = "¡Gracias, $nombreCli! Tu pedido #$idPedido por '{$prod['nombre']}' ha sido recibido. Un asesor se comunicará pronto contigo.";
            $tipoMensaje = 'exito';

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en pedido público: " . $e->getMessage());
            $mensaje = $e instanceof PDOException ? 'Ocurrió un error al procesar tu solicitud.' : $e->getMessage();
            $tipoMensaje = 'error';
        }
    }
}

$catFiltro = $_GET['categoria'] ?? '';
$busqueda  = trim($_GET['buscar'] ?? '');

try {
    $categorias = $conexion->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre ASC")->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.material, p.precio, p.imagen,
               c.nombre AS categoria, CONCAT(a.nombre, ' ', a.apellido) AS artesano, a.comunidad,
               COALESCE(i.stock_actual, 0) AS stock_actual
        FROM producto p
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria
        INNER JOIN artesano a ON p.id_artesano = a.id_artesano
        LEFT JOIN inventario i ON p.id_producto = i.id_producto
        WHERE p.estado = 1 AND COALESCE(i.stock_actual, 0) > 0";
$params = [];

if ($catFiltro !== '' && ctype_digit((string)$catFiltro)) {
    $sql .= " AND p.id_categoria = :cat";
    $params[':cat'] = (int)$catFiltro;
}
if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE :busq OR p.descripcion LIKE :busq OR p.material LIKE :busq)";
    $params[':busq'] = "%$busqueda%";
}
$sql .= " ORDER BY p.id_producto DESC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Oficial - Artesanías Wayúu</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        .barra-tienda { background-color: #5c2d1b; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .barra-tienda a { color: #fff; text-decoration: none; font-weight: bold; font-size: 14px; margin-right: 15px; }
        .barra-tienda a:hover { text-decoration: underline; }
        .btn-admin-acceso { background-color: #d9822b; padding: 6px 14px; border-radius: 4px; margin-right: 0 !important; }
        .catalogo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 24px; margin-top: 25px; }
        .card-producto { background: #fff; border: 1px solid #e6d8cc; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.06); display: flex; flex-direction: column; transition: transform 0.2s; }
        .card-producto:hover { transform: translateY(-4px); }
        .card-img { width: 100%; height: 220px; object-fit: cover; background-color: #f2ebe5; }
        .card-sin-img { width: 100%; height: 220px; background-color: #f2ebe5; color: #753b24; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .card-categoria { font-size: 12px; text-transform: uppercase; color: #d9822b; font-weight: bold; letter-spacing: 0.5px; }
        .card-titulo { margin: 6px 0 8px 0; font-size: 18px; color: #753b24; }
        .card-desc { font-size: 13px; color: #555; line-height: 1.4; margin-bottom: 12px; }
        .card-artesano { font-size: 12px; color: #666; background: #fdfaf7; padding: 8px; border-radius: 5px; margin-bottom: 14px; border-left: 3px solid #753b24; }
        .card-precio { font-size: 22px; font-weight: bold; color: #287a45; margin-bottom: 12px; }
        .modal-fondo { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
        .modal-caja { background: #fff; width: 90%; max-width: 460px; padding: 25px; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); border-top: 5px solid #753b24; }
    </style>
</head>
<body>

<header style="padding-bottom: 20px;">
    <h1>ARTESANÍAS WAYÚU</h1>
    <p style="margin-bottom: 0;">Tejidos tradicionales hechos a mano desde La Guajira</p>
</header>

<div class="barra-tienda">
    <div>
        <a href="tienda.php">Todas las artesanías</a>
        <?php foreach ($categorias as $cat): ?>
            <a href="tienda.php?categoria=<?= (int)$cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></a>
        <?php endforeach; ?>
    </div>
    <div>
        <a href="login.php" class="btn-admin-acceso">Acceso Empleados</a>
    </div>
</div>

<div class="contenedor" style="background: transparent; box-shadow: none; padding-top: 10px;">
    <?php if ($mensaje !== ''): ?>
        <div class="alerta <?= $tipoMensaje === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="tienda.php" class="filtros" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
        <input type="text" name="buscar" placeholder="Buscar mochilas, chinchorros, materiales..." value="<?= htmlspecialchars($busqueda) ?>" style="flex: 1; min-width: 240px;">
        <select name="categoria">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int)$cat['id_categoria'] ?>" <?= (string)$catFiltro === (string)$cat['id_categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-buscar">Buscar</button>
        <?php if ($busqueda !== '' || $catFiltro !== ''): ?>
            <a href="tienda.php" class="btn btn-limpiar">Ver todo</a>
        <?php endif; ?>
    </form>

    <?php if (empty($productos)): ?>
        <div style="background: #fff; padding: 40px; text-align: center; border-radius: 8px; margin-top: 20px;">
            <h3 style="color: #753b24;">No encontramos artesanías disponibles con ese filtro.</h3>
            <a href="tienda.php" class="btn btn-buscar">Ver catálogo completo</a>
        </div>
    <?php else: ?>
        <div class="catalogo-grid">
            <?php foreach ($productos as $p): ?>
                <div class="card-producto">
                    <?php if (!empty($p['imagen']) && file_exists(__DIR__ . '/' . $p['imagen'])): ?>
                        <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="card-img">
                    <?php else: ?>
                        <div class="card-sin-img">Artesanía Wayúu</div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div>
                            <span class="card-categoria"><?= htmlspecialchars($p['categoria']) ?></span>
                            <h3 class="card-titulo"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <p class="card-desc"><?= htmlspecialchars($p['descripcion'] ?? 'Pieza artesanal tejida a mano con técnicas tradicionales.') ?></p>
                            <div class="card-artesano">
                                <strong>Tejido por:</strong> <?= htmlspecialchars($p['artesano']) ?><br>
                                <?php if (!empty($p['comunidad'])): ?>
                                    <strong>Comunidad:</strong> <?= htmlspecialchars($p['comunidad']) ?><br>
                                <?php endif; ?>
                                <strong>Material:</strong> <?= htmlspecialchars($p['material'] ?? 'Hilo tradicional') ?>
                            </div>
                        </div>

                        <div>
                            <div class="card-precio">$<?= number_format((float)$p['precio'], 0, ',', '.') ?> <span style="font-size: 12px; color: #666; font-weight: normal;">COP</span></div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 10px;">Disponibles: <strong><?= (int)$p['stock_actual'] ?> unidades</strong></div>
                            <button type="button" class="btn btn-nuevo" style="width: 100%; padding: 10px;"
                                onclick="abrirModalPedido(<?= (int)$p['id_producto'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= (int)$p['stock_actual'] ?>)">
                                Solicitar Pedido
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="modalPedido" class="modal-fondo">
    <div class="modal-caja">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 id="modalTitulo" style="margin: 0; color: #753b24;">Solicitar Artesanía</h3>
            <button type="button" onclick="cerrarModalPedido()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
        </div>

        <form method="POST" action="tienda.php">
            <input type="hidden" name="realizar_pedido" value="1">
            <input type="hidden" name="id_producto" id="modalIdProducto">

            <div class="form-grupo">
                <label for="modalCantidad">Cantidad *</label>
                <input type="number" id="modalCantidad" name="cantidad" min="1" value="1" required>
            </div>
            <div class="form-grupo">
                <label for="nombre_cliente">Tu nombre completo *</label>
                <input type="text" id="nombre_cliente" name="nombre_cliente" maxlength="100" required>
            </div>
            <div class="form-grupo">
                <label for="documento_cliente">Número de cédula / documento *</label>
                <input type="text" id="documento_cliente" name="documento_cliente" maxlength="20" required>
            </div>
            <div class="form-grupo">
                <label for="telefono_cliente">Teléfono / WhatsApp *</label>
                <input type="text" id="telefono_cliente" name="telefono_cliente" maxlength="20" required>
            </div>
            <div class="form-grupo">
                <label for="direccion_cliente">Ciudad y dirección de envío</label>
                <input type="text" id="direccion_cliente" name="direccion_cliente" maxlength="120" placeholder="Ej. Maicao / Riohacha, Calle 10 # 5-20">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-nuevo" style="flex: 1; padding: 10px;">Confirmar Pedido</button>
                <button type="button" onclick="cerrarModalPedido()" class="btn btn-limpiar">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalPedido(id, nombre, stockMax) {
    document.getElementById('modalIdProducto').value = id;
    document.getElementById('modalTitulo').textContent = 'Pedir: ' + nombre;
    const inputCant = document.getElementById('modalCantidad');
    inputCant.value = 1;
    inputCant.max = stockMax;
    document.getElementById('modalPedido').style.display = 'flex';
}
function cerrarModalPedido() {
    document.getElementById('modalPedido').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>

