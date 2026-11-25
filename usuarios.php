<?php
require_once 'includes/verificar_sesion.php';
require_once 'config/conexion.php';

if (($_SESSION['rol_usuario'] ?? '') !== 'admin') {
    header('Location: index.php?tipo=error&msg=' . urlencode('Acceso denegado: solo administradores.'));
    exit;
}

// Procesar activación / desactivación por POST en el mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id_usuario'])) {
    $idTarget = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $accion = $_POST['accion'];

    if ($idTarget && $idTarget > 0) {
        if ($idTarget === (int)$_SESSION['id_usuario'] && $accion === 'desactivar') {
            header('Location: usuarios.php?tipo=error&msg=' . urlencode('No puedes desactivar tu propio usuario en sesión.'));
            exit;
        }

        $nuevoEstado = ($accion === 'activar') ? 1 : 0;
        try {
            $stmtUpd = $conexion->prepare("UPDATE usuario SET estado = :estado WHERE id_usuario = :id");
            $stmtUpd->execute([':estado' => $nuevoEstado, ':id' => $idTarget]);
            $texto = $nuevoEstado === 1 ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';
            header('Location: usuarios.php?tipo=exito&msg=' . urlencode($texto));
            exit;
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de usuario: " . $e->getMessage());
            header('Location: usuarios.php?tipo=error&msg=' . urlencode('No se pudo actualizar el estado del usuario.'));
            exit;
        }
    }
}

try {
    $usuarios = $conexion->query("SELECT id_usuario, nombre, correo, rol, estado FROM usuario ORDER BY id_usuario DESC")->fetchAll();
} catch (PDOException $e) {
    error_log("Error al listar usuarios: " . $e->getMessage());
    $usuarios = [];
}

$mensaje = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$tituloPagina = 'Usuarios - Artesanías Wayúu';
require_once 'includes/header.php';
?>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Gestión de Usuarios del Sistema</h2>
        <a href="agregar_usuario.php" class="btn btn-nuevo">+ Nuevo Usuario</a>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta <?= $tipo === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo de Acceso</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 20px;">No hay usuarios registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= (int)$u['id_usuario'] ?></td>
                        <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($u['correo']) ?></td>
                        <td><?= $u['rol'] === 'admin' ? 'Administrador' : 'Vendedor' ?></td>
                        <td>
                            <?php if ((int)$u['estado'] === 1): ?>
                                <span class="badge badge-activo">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="acciones">
                            <a href="editar_usuario.php?id=<?= (int)$u['id_usuario'] ?>" class="btn btn-editar">Editar / Clave</a>

                            <?php if ((int)$u['id_usuario'] !== (int)$_SESSION['id_usuario']): ?>
                                <?php if ((int)$u['estado'] === 1): ?>
                                    <form method="POST" action="usuarios.php" onsubmit="return confirm('¿Seguro que deseas desactivar este usuario?');">
                                        <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                        <input type="hidden" name="accion" value="desactivar">
                                        <button type="submit" class="btn btn-desactivar">Desactivar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="usuarios.php" onsubmit="return confirm('¿Seguro que deseas activar este usuario?');">
                                        <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                        <input type="hidden" name="accion" value="activar">
                                        <button type="submit" class="btn btn-activar">Activar</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
