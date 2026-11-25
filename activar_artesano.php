<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once "config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: artesanos.php");
    exit;
}

$id = filter_input(INPUT_POST, "id_artesano", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: artesanos.php");
    exit;
}

try {
    $sql = "UPDATE artesano
            SET estado = 1
            WHERE id_artesano = :id";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([":id" => $id]);

    header("Location: artesanos.php");
    exit;

} catch (PDOException $e) {
    error_log("Error al reactivar el artesano: " . $e->getMessage());
    die("Error al reactivar el artesano. Intenta de nuevo.");
}
?>
