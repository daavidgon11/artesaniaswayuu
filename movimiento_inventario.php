<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_producto = filter_input(INPUT_POST, "id_producto", FILTER_VALIDATE_INT);
    $tipo = $_POST["tipo"] ?? "";
    $cantidad = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT);

    if (
        !$id_producto ||
        !in_array($tipo, ["entrada", "salida"], true) ||
        $cantidad === false ||
        $cantidad === null ||
        $cantidad <= 0
    ) {
        $mensaje = "Verifica los datos ingresados.";
        $tipoMensaje = "error";

    } else {

        try {
            $conexion->beginTransaction();

            // Bloquear el registro para evitar cambios simultáneos
            $consulta = $conexion->prepare(
                "SELECT stock_actual
                 FROM inventario
                 WHERE id_producto = :id
                 FOR UPDATE"
            );

            $consulta->execute([":id" => $id_producto]);
            $inventario = $consulta->fetch();

            if (!$inventario) {
                throw new Exception("No existe inventario para este producto.");
            }

            $stock = (int)$inventario["stock_actual"];

            if ($tipo === "entrada") {
                $nuevoStock = $stock + $cantidad;
            } else {
                if ($cantidad > $stock) {
                    throw new Exception("No hay suficientes existencias.");
                }

                $nuevoStock = $stock - $cantidad;
            }

            $actualizar = $conexion->prepare(
                "UPDATE inventario
                 SET stock_actual = :stock
                 WHERE id_producto = :id"
            );

            $actualizar->execute([
                ":stock" => $nuevoStock,
                ":id" => $id_producto
            ]);

            $conexion->commit();

            $mensaje = "Movimiento registrado correctamente. Nuevo stock: " . $nuevoStock;
            $tipoMensaje = "correcto";

        } catch (Exception $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            $mensaje = $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

// Consultar productos
$productos = $conexion->query(
    "SELECT id_producto, nombre
     FROM producto
     WHERE estado = 1
     ORDER BY nombre"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Movimiento de inventario</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f1ed;
            margin: 0;
            padding: 30px;
        }

        .contenedor {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        h1 {
            color: #753b24;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            background: #753b24;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .mensaje {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .correcto {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #753b24;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Movimiento de inventario</h1>

    <p>Registra entradas y salidas de productos artesanales.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Seleccionar producto:</label>

        <select name="id_producto" required>
            <option value="">Selecciona un producto</option>

            <?php foreach ($productos as $producto): ?>
                <option value="<?php echo $producto['id_producto']; ?>">
                    <?php echo htmlspecialchars($producto['nombre']); ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Tipo de movimiento:</label>

        <select name="tipo" required>
            <option value="">Selecciona el movimiento</option>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
        </select>

        <label>Cantidad:</label>

        <input
            type="number"
            name="cantidad"
            min="1"
            step="1"
            required
        >

        <button type="submit">
            Registrar movimiento
        </button>

    </form>

    <a class="volver" href="inventario.php">
        Volver al inventario
    </a>

</div>

</body>
</html>
