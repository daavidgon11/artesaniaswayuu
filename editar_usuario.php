<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

if (($_SESSION['rol_usuario'] ?? '') !== 'admin') {
    header('Location: index.php?tipo=error&msg=' . urlencode('Acceso denegado: solo administradores.'));
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: usuarios.php?tipo=error&msg=' . urlencode('ID de usuario no válido.'));
    exit;
}

$errores = [];

try {
    $stmt = $conexion->prepare("SELECT id_usuario, nombre, correo, rol FROM usuario WHERE id_usuario = :id");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        header('Location: usuarios.php?tipo=error&msg=' . urlencode('El usuario no existe.'));
        exit;
    }
} catch (PDOException $e) {
    error_log("Error al cargar usuario: " . $e->getMessage());
    header('Location: usuarios.php?tipo=error&msg=' . urlencode('Error interno al consultar usuario.'));
    exit;
}

$nombre = $usuario['nombre'];
$correo = $usuario['correo'];
$rol    = $usuario['rol'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol    = $_POST['rol'] ?? 'vendedor';
    $nuevaClave = $_POST['password'] ?? '';

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo electrónico no es válido.';
    if (!in_array($rol, ['admin', 'vendedor'], true)) $errores[] = 'Rol seleccionado no válido.';
    if ($nuevaClave !== '' && strlen($nuevaClave) < 6) {
        $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    }

    if (empty($errores)) {
        try {
            if ($nuevaClave !== '') {
                $hash = password_hash($nuevaClave, PASSWORD_DEFAULT);
                $sql = "UPDATE usuario SET nombre = :nombre, correo = :correo, rol = :rol, password = :pass WHERE id_usuario = :id";
                $params = [':nombre' => $nombre, ':correo' => $correo, ':rol' => $rol, ':pass' => $hash, ':id' => $id];
            } else {
                $sql = "UPDATE usuario SET nombre = :nombre, correo = :correo, rol = :rol WHERE id_usuario = :id";
                $params = [':nombre' => $nombre, ':correo' => $correo, ':rol' => $rol, ':id' => $id];
            }

            $stmtUpd = $conexion->prepare($sql);
            $stmtUpd->execute($params);

            // Si el admin editó su propio nombre, actualizar la sesión
            if ($id === (int)$_SESSION['id_usuario']) {
                $_SESSION['nombre_usuario'] = $nombre;
                $_SESSION['rol_usuario'] = $rol;
            }

            header('Location: usuarios.php?tipo=exito&msg=' . urlencode('Usuario actualizado correctamente.'));
            exit;
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errores[] = 'Ese correo ya está siendo utilizado por otro usuario.';
            } else {
                $errores[] = 'Error interno al guardar los cambios.';
            }
        }
    }
}

$tituloPagina = 'Editar Usuario - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Editar Usuario #<?= (int)$id ?></h2>
        <a href="usuarios.php" class="btn btn-limpiar">← Volver</a>
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

    <form method="POST" action="editar_usuario.php?id=<?= (int)$id ?>">
        <div class="form-grupo">
            <label for="nombre">Nombre completo *</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="correo">Correo electrónico *</label>
            <input type="email" id="correo" name="correo" maxlength="100" value="<?= htmlspecialchars($correo) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="rol">Rol en el sistema *</label>
            <select id="rol" name="rol" required>
                <option value="vendedor" <?= $rol === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>

        <div class="form-grupo">
            <label for="password">Nueva contraseña (dejar en blanco para mantener la actual)</label>
            <input type="password" id="password" name="password" minlength="6" placeholder="Mínimo 6 caracteres...">
        </div>

        <button type="submit" class="btn btn-nuevo">Guardar Cambios</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
