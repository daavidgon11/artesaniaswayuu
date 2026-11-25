<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Identificador de inventario no válido.");
}

$consulta = $conexion->prepare(
    "SELECT i.id_inventario, i.stock_minimo, p.nombre
     FROM inventario i
     INNER JOIN producto p ON i.id_producto = p.id_producto
     WHERE i.id_inventario = :id"
);

$consulta->execute([":id" => $id]);
$inventario = $consulta->fetch();

if (!$inventario) {
    die("Registro de inventario no encontrado.");
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $stock_minimo = filter_input(
        INPUT_POST,
        "stock_minimo",
        FILTER_VALIDATE_INT
    );

    if ($stock_minimo === false || $stock_minimo === null || $stock_minimo < 0) {
        $mensaje = "Ingresa una cantidad válida.";
    } else {

        $actualizar = $conexion->prepare(
            "UPDATE inventario
             SET stock_minimo = :stock
             WHERE id_inventario = :id"
        );

        $actualizar->execute([
            ":stock" => $stock_minimo,
            ":id" => $id
        ]);

        header("Location: inventario.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar stock mínimo</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f1ed;
            padding: 30px;
        }

        .contenedor {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        h1 {
            color: #753b24;
        }

        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            background: #753b24;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .mensaje {
            color: #c0392b;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #753b24;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Establecer stock mínimo</h1>

    <p>
        Producto:
        <strong><?php echo htmlspecialchars($inventario['nombre']); ?></strong>
    </p>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Cantidad mínima:</label>

        <input
            type="number"
            name="stock_minimo"
            min="0"
            step="1"
            value="<?php echo $inventario['stock_minimo']; ?>"
            required
        >

        <button type="submit">Guardar stock mínimo</button>

    </form>

    <a href="inventario.php">Volver al inventario</a>

</div>

</body>
</html>
