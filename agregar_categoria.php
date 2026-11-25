<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($nombre === "") {
        $mensaje = "El nombre de la categoría es obligatorio.";
        $tipoMensaje = "error";
    } else {
        try {
            $sql = "INSERT INTO categoria (nombre, descripcion)
                    VALUES (:nombre, :descripcion)";

            $consulta = $conexion->prepare($sql);

            $consulta->execute([
                ":nombre" => $nombre,
                ":descripcion" => $descripcion
            ]);

            $mensaje = "Categoría registrada correctamente.";
            $tipoMensaje = "correcto";

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $mensaje = "Esta categoría ya está registrada.";
            } else {
                $mensaje = "Error al registrar la categoría.";
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
    <title>Registrar categoría</title>

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
            max-width: 600px;
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
            margin-top: 18px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-family: Arial;
        }

        textarea {
            resize: vertical;
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
            font-size: 16px;
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
    <p>Registro de categorías</p>
</header>

<main>

    <div class="formulario">

        <h2>Nueva categoría</h2>

        <?php if ($mensaje !== ""): ?>
            <div class="mensaje <?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Nombre de la categoría:</label>
            <input type="text" name="nombre"
                   maxlength="60" required>

            <label>Descripción:</label>
            <textarea name="descripcion"
                      rows="4"
                      maxlength="200"></textarea>

            <button type="submit">
                Guardar categoría
            </button>

        </form>

        <a class="enlace" href="categorias.php">
            Volver a categorías
        </a>

    </div>

</main>

</body>
</html>
