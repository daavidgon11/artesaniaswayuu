<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

// Solo el administrador puede crear otros usuarios
if (($_SESSION['rol_usuario'] ?? '') !== 'admin') {
    header('Location: index.php?tipo=error&msg=' . urlencode('No tienes permisos para acceder a este módulo.'));
    exit;
}

$errores = [];
$nombre = '';
$correo = '';
$rol = 'vendedor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'vendedor';

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'Ingresa un correo electrónico válido.';
    if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if (!in_array($rol, ['admin', 'vendedor'], true)) $errores[] = 'Rol no válido.';

    if (empty($errores)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuario (nombre, correo, password, rol) VALUES (:nombre, :correo, :password, :rol)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':nombre'   => $nombre,
                ':correo'   => $correo,
                ':password' => $passwordHash,
                ':rol'      => $rol
            ]);

            header('Location: usuarios.php?tipo=exito&msg=' . urlencode("Usuario '$nombre' creado correctamente."));
            exit;
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe un usuario registrado con ese correo electrónico.';
            } else {
                $errores[] = 'Error interno al registrar el usuario.';
            }
        }
    }
}

$tituloPagina = 'Nuevo Usuario - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Registrar Nuevo Usuario del Sistema</h2>
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

    <form method="POST" action="agregar_usuario.php">
        <div class="form-grupo">
            <label for="nombre">Nombre completo *</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="correo">Correo electrónico (Usuario de acceso) *</label>
            <input type="email" id="correo" name="correo" maxlength="100" value="<?= htmlspecialchars($correo) ?>" required>
        </div>

        <div class="form-grupo">
            <label for="password">Contraseña (mínimo 6 caracteres) *</label>
            <input type="password" id="password" name="password" minlength="6" required>
        </div>

        <div class="form-grupo">
            <label for="rol">Rol en el sistema *</label>
            <select id="rol" name="rol" required>
                <option value="vendedor" <?= $rol === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>

        <button type="submit" class="btn btn-nuevo">Crear Usuario</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
