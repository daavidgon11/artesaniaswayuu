<?php
/**
 * Conexión a MySQL.
 * En Azure App Service define estas variables en
 * Configuración > Variables de entorno (App settings).
 * Si no existen, usa los valores de desarrollo local (XAMPP).
 */

$host      = getenv('DB_HOST') ?: '127.0.0.1';
$puerto    = getenv('DB_PORT') ?: '3309';
$basedatos = getenv('DB_NAME') ?: 'artesanias_wayuu';
$usuario   = getenv('DB_USER') ?: 'root';
$password  = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Azure Database for MySQL exige TLS por defecto (DB_SSL=1 en App Service)
if (getenv('DB_SSL') === '1') {
    $opciones[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt';
    $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $conexion = new PDO(
        "mysql:host=$host;port=$puerto;dbname=$basedatos;charset=utf8mb4",
        $usuario,
        $password,
        $opciones
    );
} catch (PDOException $e) {
    error_log('Error de conexion a la BD: ' . $e->getMessage());
    http_response_code(500);
    die('No fue posible conectar con la base de datos.');
}
