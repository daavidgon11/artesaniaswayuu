<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
require_once 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clientes.php?tipo=error&msg=' . urlencode('Método no permitido.'));
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: clientes.php?tipo=error&msg=' . urlencode('ID de cliente no válido.'));
    exit;
}

try {
    $sql = "UPDATE cliente SET estado = 0 WHERE id_cliente = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: clientes.php?tipo=exito&msg=' . urlencode('Cliente desactivado correctamente.'));
    exit;
} catch (PDOException $e) {
    error_log("Error al desactivar cliente: " . $e->getMessage());
    header('Location: clientes.php?tipo=error&msg=' . urlencode('No se pudo desactivar el cliente.'));
    exit;
}

