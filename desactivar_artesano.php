<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

// Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: artesanos.php");
    exit;
}

// Obtener el ID del artesano
$id = filter_input(INPUT_POST, "id_artesano", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: artesanos.php");
    exit;
}

try {
    // Cambiar el estado del artesano a inactivo
    $sql = "UPDATE artesano
            SET estado = 0
            WHERE id_artesano = :id";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([":id" => $id]);

    header("Location: artesanos.php");
    exit;

} catch (PDOException $e) {
    error_log("Error al desactivar el artesano: " . $e->getMessage());
    die("Error al desactivar el artesano. Intenta de nuevo.");
}
?>
