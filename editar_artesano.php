<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Identificador de artesano no válido.");
}

$consulta = $conexion->prepare(
    "SELECT * FROM artesano WHERE id_artesano = :id"
);
$consulta->execute([":id" => $id]);

$artesano = $consulta->fetch();

if (!$artesano) {
    die("El artesano no existe.");
}

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
        $nombre === "" || $apellido === "" ||
        $documento === "" || $telefono === "" ||
        $comunidad === "" || $direccion === ""
    ) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = "error";
    } else {
        try {
            $sql = "UPDATE artesano SET
                    nombre = :nombre,
                    apellido = :apellido,
                    documento = :documento,
                    telefono = :telefono,
                    comunidad = :comunidad,
                    direccion = :direccion
                    WHERE id_artesano = :id";

            $actualizar = $conexion->prepare($sql);

            $actualizar->execute([
                ":nombre" => $nombre,
                ":apellido" => $apellido,
                ":documento" => $documento,
                ":telefono" => $telefono,
                ":comunidad" => $comunidad,
                ":direccion" => $direccion,
                ":id" => $id
            ]);

            $mensaje = "Artesano actualizado correctamente.";
            $tipoMensaje = "correcto";

            $consulta->execute([":id" => $id]);
            $artesano = $consulta->fetch();

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $mensaje = "Ese documento ya pertenece a otro artesano.";
            } else {
                $mensaje = "Error al actualizar el artesano.";
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
    <title>Editar artesano</title>

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
            cursor: pointer;
        }

        .mensaje {
            padding: 12px;
            border-radius: 5px;
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
    <p>Actualización de información del artesano</p>
</header>

<main>
    <div class="formulario">

        <h2>Editar artesano</h2>

        <?php if ($mensaje !== ""): ?>
            <div class="mensaje <?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Nombre:</label>
            <input type="text" name="nombre"
                value="<?php echo htmlspecialchars($artesano["nombre"]); ?>"
                required>

            <label>Apellido:</label>
            <input type="text" name="apellido"
                value="<?php echo htmlspecialchars($artesano["apellido"]); ?>"
                required>

            <label>Documento:</label>
            <input type="text" name="documento"
                value="<?php echo htmlspecialchars($artesano["documento"]); ?>"
                required>

            <label>Teléfono:</label>
            <input type="text" name="telefono"
                value="<?php echo htmlspecialchars($artesano["telefono"]); ?>"
                required>

            <label>Comunidad:</label>
            <input type="text" name="comunidad"
                value="<?php echo htmlspecialchars($artesano["comunidad"]); ?>"
                required>

            <label>Dirección:</label>
            <input type="text" name="direccion"
                value="<?php echo htmlspecialchars($artesano["direccion"]); ?>"
                required>

            <button type="submit">Guardar cambios</button>

        </form>

        <a class="enlace" href="artesanos.php">
            Volver a la lista de artesanos
        </a>

    </div>
</main>

</body>
</html>
