<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: productos.php?tipo=error&msg=' . urlencode('Método no permitido.'));
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: productos.php?tipo=error&msg=' . urlencode('ID de producto no válido.'));
    exit;
}

try {
    $sql = "UPDATE producto SET estado = 1 WHERE id_producto = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: productos.php?tipo=exito&msg=' . urlencode('Producto activado correctamente.'));
    exit;
} catch (PDOException $e) {
    error_log("Error al activar producto: " . $e->getMessage());
    header('Location: productos.php?tipo=error&msg=' . urlencode('No se pudo activar el producto.'));
    exit;
}