<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$mensaje = "";
$tipoMensaje = "";

$nombre = "";
$documento = "";
$telefono = "";
$correo = "";
$direccion = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");

    if ($nombre === "") {
        $mensaje = "El nombre es obligatorio.";
        $tipoMensaje = "error";

    } elseif ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresa un correo electrónico válido.";
        $tipoMensaje = "error";

    } else {

        try {
            $sql = "INSERT INTO cliente
                    (nombre, documento, telefono, correo, direccion)
                    VALUES
                    (:nombre, :documento, :telefono, :correo, :direccion)";

            $consulta = $conexion->prepare($sql);

            $consulta->execute([
                ":nombre" => $nombre,
                ":documento" => $documento !== "" ? $documento : null,
                ":telefono" => $telefono !== "" ? $telefono : null,
                ":correo" => $correo !== "" ? $correo : null,
                ":direccion" => $direccion !== "" ? $direccion : null
            ]);

            header("Location: clientes.php?registro=correcto");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $mensaje = "El documento ingresado ya está registrado.";
            } else {
                $mensaje = "No fue posible registrar el cliente.";
            }

            $tipoMensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar cliente</title>

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

        input {
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

        button:hover {
            background: #542918;
        }

        .mensaje {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
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

    <h1>Registrar cliente</h1>

    <p>Ingresa los datos del nuevo cliente.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Nombre completo:</label>
        <input type="text" name="nombre"
               value="<?php echo htmlspecialchars($nombre); ?>"
               maxlength="100" required>

        <label>Documento:</label>
        <input type="text" name="documento"
               value="<?php echo htmlspecialchars($documento); ?>"
               maxlength="20">

        <label>Teléfono:</label>
        <input type="text" name="telefono"
               value="<?php echo htmlspecialchars($telefono); ?>"
               maxlength="20">

        <label>Correo electrónico:</label>
        <input type="email" name="correo"
               value="<?php echo htmlspecialchars($correo); ?>"
               maxlength="100">

        <label>Dirección:</label>
        <input type="text" name="direccion"
               value="<?php echo htmlspecialchars($direccion); ?>"
               maxlength="120">

        <button type="submit">Guardar cliente</button>

    </form>

    <a class="volver" href="clientes.php">
        Volver a clientes
    </a>

</div>

</body>
</html>
