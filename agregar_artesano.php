<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $comunidad = trim($_POST["comunidad"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");

    if (
        $nombre === "" ||
        $apellido === "" ||
        $documento === "" ||
        $telefono === "" ||
        $comunidad === "" ||
        $direccion === ""
    ) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = "error";

    } else {
        try {
            $sql = "INSERT INTO artesano
                    (nombre, apellido, documento, telefono, comunidad, direccion)
                    VALUES
                    (:nombre, :apellido, :documento, :telefono, :comunidad, :direccion)";

            $consulta = $conexion->prepare($sql);

            $consulta->execute([
                ":nombre" => $nombre,
                ":apellido" => $apellido,
                ":documento" => $documento,
                ":telefono" => $telefono,
                ":comunidad" => $comunidad,
                ":direccion" => $direccion
            ]);

            $mensaje = "Artesano registrado correctamente.";
            $tipoMensaje = "correcto";

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $mensaje = "El documento ingresado ya está registrado.";
            } else {
                $mensaje = "Error al registrar: " . $e->getMessage();
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
    <title>Registrar artesano</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            color: #333;
        }

        header {
            background: #753b24;
            color: white;
            text-align: center;
            padding: 22px;
        }

        main {
            width: 90%;
            max-width: 650px;
            margin: 30px auto;
        }

        .formulario {
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            color: #753b24;
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            background: #753b24;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #4b291d;
        }

        .mensaje {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .correcto {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .enlace {
            display: inline-block;
            margin-top: 20px;
            color: #753b24;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

<header>
    <h1>ARTESANÍAS WAYÚU</h1>
    <p>Registro de nuevos artesanos</p>
</header>

<main>
    <div class="formulario">

        <h2>Registrar artesano</h2>

        <?php if ($mensaje !== ""): ?>
            <div class="mensaje <?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label>Nombre:</label>
            <input type="text" name="nombre" required>

            <label>Apellido:</label>
            <input type="text" name="apellido" required>

            <label>Documento:</label>
            <input type="text" name="documento" required>

            <label>Teléfono:</label>
            <input type="text" name="telefono" required>

            <label>Comunidad:</label>
            <input type="text" name="comunidad" required>

            <label>Dirección:</label>
            <input type="text" name="direccion" required>

            <button type="submit">Guardar artesano</button>

        </form>

        <a class="enlace" href="artesanos.php">
            Volver a la lista de artesanos
        </a>

    </div>
</main>

</body>
</html>
