<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    die("ID de cliente no válido.");
}

// Consultar cliente
$consulta = $conexion->prepare(
    "SELECT * FROM cliente WHERE id_cliente = ?"
);
$consulta->execute([$id]);
$cliente = $consulta->fetch();

if (!$cliente) {
    die("El cliente no existe.");
}

$error = "";

// Actualizar cliente
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");

    if ($nombre === "") {
        $error = "El nombre es obligatorio.";
    } elseif ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } else {
        try {
            $sql = "UPDATE cliente
                    SET nombre = ?,
                        documento = ?,
                        telefono = ?,
                        correo = ?,
                        direccion = ?
                    WHERE id_cliente = ?";

            $actualizar = $conexion->prepare($sql);

            $actualizar->execute([
                $nombre,
                $documento !== "" ? $documento : null,
                $telefono !== "" ? $telefono : null,
                $correo !== "" ? $correo : null,
                $direccion !== "" ? $direccion : null,
                $id
            ]);

            header("Location: clientes.php?actualizado=correcto");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == "23000") {
                $error = "Ese documento ya está registrado por otro cliente.";
            } else {
                $error = "Error al actualizar el cliente.";
            }
        }
    }

    // Mantener los datos escritos si ocurre un error
    $cliente["nombre"] = $nombre;
    $cliente["documento"] = $documento;
    $cliente["telefono"] = $telefono;
    $cliente["correo"] = $correo;
    $cliente["direccion"] = $direccion;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar cliente</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .contenedor {
            background: white;
            max-width: 500px;
            margin: auto;
            padding: 25px;
            border-radius: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0 15px;
            box-sizing: border-box;
        }

        button {
            background: #198754;
            color: white;
            padding: 12px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        a {
            display: inline-block;
            margin-top: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h2>Editar cliente</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Nombre:</label>
        <input type="text" name="nombre"
               value="<?php echo htmlspecialchars($cliente["nombre"] ?? ""); ?>"
               required>

        <label>Documento:</label>
        <input type="text" name="documento"
               value="<?php echo htmlspecialchars($cliente["documento"] ?? ""); ?>">

        <label>Teléfono:</label>
        <input type="text" name="telefono"
               value="<?php echo htmlspecialchars($cliente["telefono"] ?? ""); ?>">

        <label>Correo:</label>
        <input type="email" name="correo"
               value="<?php echo htmlspecialchars($cliente["correo"] ?? ""); ?>">

        <label>Dirección:</label>
        <input type="text" name="direccion"
               value="<?php echo htmlspecialchars($cliente["direccion"] ?? ""); ?>">

        <button type="submit">Actualizar cliente</button>

    </form>

    <a href="clientes.php">Volver a clientes</a>

</div>

</body>
</html>
