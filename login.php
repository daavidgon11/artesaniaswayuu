<?php
session_start();
require_once 'config/conexion.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            $sql = "SELECT id_usuario, nombre, password, rol, estado FROM usuario WHERE correo = :correo LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch();

            if ($usuario && (int)$usuario['estado'] === 1 && password_verify($password, $usuario['password'])) {
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];
                $_SESSION['rol_usuario'] = $usuario['rol'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Correo o contraseña incorrectos, o usuario inactivo.';
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error interno al intentar iniciar sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Artesanías Wayúu</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        .login-box {
            max-width: 400px;
            margin: 70px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-top: 5px solid #753b24;
        }
        .login-box h2 {
            text-align: center;
            color: #753b24;
            margin-top: 0;
        }
    </style>
</head>
<body>

<header style="padding-bottom: 20px;">
    <h1>ARTESANÍAS WAYÚU</h1>
    <p style="margin-bottom: 0;">Acceso al sistema de gestión</p>
</header>

<div class="login-box">
    <h2>Iniciar Sesión</h2>

    <?php if ($error !== ''): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-grupo">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required autofocus>
        </div>

        <div class="form-grupo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-buscar" style="width: 100%; padding: 10px; font-size: 15px;">Ingresar</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
