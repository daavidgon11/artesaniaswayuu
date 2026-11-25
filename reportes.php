<?php
require_once 'config/conexion.php';

$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';

$condicionFecha = "";
$params = [];

if ($fechaInicio !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
    $condicionFecha .= " AND DATE(v.fecha_venta) >= :fecha_inicio";
    $params[':fecha_inicio'] = $fechaInicio;
}
if ($fechaFin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
    $condicionFecha .= " AND DATE(v.fecha_venta) <= :fecha_fin";
    $params[':fecha_fin'] = $fechaFin;
}

$resumen = ['total_ventas' => 0, 'ingresos' => 0, 'ticket_promedio' => 0];
$porMetodo = [];
$topProductos = [];
$porArtesano = [];

try {
    $sqlResumen = "SELECT COUNT(v.id_venta) AS total_ventas,
                          COALESCE(SUM(v.total), 0) AS ingresos,
                          COALESCE(AVG(v.total), 0) AS ticket_promedio
                   FROM venta v
                   WHERE 1=1 $condicionFecha";
    $stmtRes = $conexion->prepare($sqlResumen);
    $stmtRes->execute($params);
    $filaRes = $stmtRes->fetch();
    if ($filaRes) {
        $resumen = $filaRes;
    }

    $sqlMetodo = "SELECT v.metodo_pago,
                         COUNT(v.id_venta) AS cantidad,
                         COALESCE(SUM(v.total), 0) AS total
                  FROM venta v
                  WHERE 1=1 $condicionFecha
                  GROUP BY v.metodo_pago
                  ORDER BY total DESC";
    $stmtMet = $conexion->prepare($sqlMetodo);
    $stmtMet->execute($params);
    $porMetodo = $stmtMet->fetchAll();

    $sqlProd = "SELECT p.nombre AS producto,
                       c.nombre AS categoria,
                       SUM(dp.cantidad) AS unidades_vendidas,
                       SUM(dp.subtotal) AS total_generado
                FROM venta v
                INNER JOIN pedido ped ON v.id_pedido = ped.id_pedido
                INNER JOIN detalle_pedido dp ON ped.id_pedido = dp.id_pedido
                INNER JOIN producto p ON dp.id_producto = p.id_producto
                INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE 1=1 $condicionFecha
                GROUP BY p.id_producto, p.nombre, c.nombre
                ORDER BY unidades_vendidas DESC, total_generado DESC";
    $stmtProd = $conexion->prepare($sqlProd);
    $stmtProd->execute($params);
    $topProductos = $stmtProd->fetchAll();

    $sqlArt = "SELECT CONCAT(a.nombre, ' ', a.apellido) AS artesano,
                      COALESCE(a.comunidad, '-') AS comunidad,
                      SUM(dp.cantidad) AS piezas_vendidas,
                      SUM(dp.subtotal) AS total_ventas
               FROM venta v
               INNER JOIN pedido ped ON v.id_pedido = ped.id_pedido
               INNER JOIN detalle_pedido dp ON ped.id_pedido = dp.id_pedido
               INNER JOIN producto p ON dp.id_producto = p.id_producto
               INNER JOIN artesano a ON p.id_artesano = a.id_artesano
               WHERE 1=1 $condicionFecha
               GROUP BY a.id_artesano, a.nombre, a.apellido, a.comunidad
               ORDER BY total_ventas DESC";
    $stmtArt = $conexion->prepare($sqlArt);
    $stmtArt->execute($params);
    $porArtesano = $stmtArt->fetchAll();

} catch (PDOException $e) {
    error_log("Error al generar reportes: " . $e->getMessage());
}

$labelsMetodo = [];
$datosMetodo  = [];
foreach ($porMetodo as $m) {
    $labelsMetodo[] = ucfirst($m['metodo_pago']);
    $datosMetodo[]  = (float)$m['total'];
}

$labelsProd = [];
$datosProd  = [];
foreach ($topProductos as $p) {
    $labelsProd[] = $p['producto'];
    $datosProd[]  = (int)$p['unidades_vendidas'];
}

$labelsArt = [];
$datosArt  = [];
foreach ($porArtesano as $a) {
    $labelsArt[] = $a['artesano'];
    $datosArt[]  = (float)$a['total_ventas'];
}

$tituloPagina = 'Reportes - Artesanías Wayúu';
require_once 'includes/header.php';
?>
<style>
    .resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 30px; }
    .kpi-card { background: #fdfaf7; border: 1px solid #e6d8cc; border-left: 5px solid #753b24; padding: 18px; border-radius: 6px; }
    .kpi-card span { display: block; font-size: 13px; color: #666; margin-bottom: 6px; font-weight: bold; }
    .kpi-card strong { font-size: 24px; color: #753b24; }
    .graficas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 22px; margin-bottom: 35px; }
    .caja-grafica { background: #fff; border: 1px solid #e6d8cc; border-radius: 8px; padding: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
    .caja-grafica h4 { margin: 0 0 14px 0; color: #753b24; font-size: 15px; text-align: center; }
    .canvas-wrapper { position: relative; height: 250px; width: 100%; }
    .seccion-reporte { margin-top: 32px; }
    .seccion-reporte h3 { color: #753b24; border-bottom: 2px solid #f2ebe5; padding-bottom: 8px; margin-bottom: 14px; font-size: 18px; }
    @media print {
        header, nav, footer, .filtros, .no-print { display: none !important; }
        .contenedor { box-shadow: none; width: 100%; margin: 0; padding: 0; }
        .graficas-grid { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="contenedor">
    <div class="barra-superior">
        <h2>Reportes Comerciales y Estadísticas</h2>
        <button type="button" onclick="window.print()" class="btn btn-editar no-print">Imprimir reporte</button>
    </div>

    <form method="GET" action="reportes.php" class="filtros">
        <div>
            <label for="fecha_inicio" style="font-size: 13px; font-weight: bold; color: #753b24;">Desde:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
        </div>
        <div>
            <label for="fecha_fin" style="font-size: 13px; font-weight: bold; color: #753b24;">Hasta:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>">
        </div>
        <button type="submit" class="btn btn-buscar">Generar</button>
        <?php if ($fechaInicio !== '' || $fechaFin !== ''): ?>
            <a href="reportes.php" class="btn btn-limpiar">Limpiar filtro</a>
        <?php endif; ?>
    </form>

    <div class="resumen-grid">
        <div class="kpi-card"><span>Ventas Realizadas</span><strong><?= (int)$resumen['total_ventas'] ?></strong></div>
        <div class="kpi-card"><span>Ingresos Totales</span><strong style="color: #27ae60;">$<?= number_format((float)$resumen['ingresos'], 0, ',', '.') ?></strong></div>
        <div class="kpi-card"><span>Promedio por Venta</span><strong>$<?= number_format((float)$resumen['ticket_promedio'], 0, ',', '.') ?></strong></div>
    </div>

    <?php if (!empty($porMetodo) || !empty($topProductos) || !empty($porArtesano)): ?>
        <div class="graficas-grid">
            <div class="caja-grafica">
                <h4>Ingresos por Método de Pago (COP)</h4>
                <div class="canvas-wrapper"><canvas id="graficaMetodos"></canvas></div>
            </div>
            <div class="caja-grafica">
                <h4>Unidades Vendidas por Producto</h4>
                <div class="canvas-wrapper"><canvas id="graficaProductos"></canvas></div>
            </div>
            <div class="caja-grafica">
                <h4>Ingresos Generados por Artesano (COP)</h4>
                <div class="canvas-wrapper"><canvas id="graficaArtesanos"></canvas></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="seccion-reporte">
        <h3>1. Ventas por Método de Pago</h3>
        <table>
            <thead><tr><th>Método de Pago</th><th>Cantidad de Ventas</th><th>Total Recaudado</th></tr></thead>
            <tbody>
                <?php if (empty($porMetodo)): ?>
                    <tr><td colspan="3" style="text-align: center; padding: 15px;">Sin datos para el período seleccionado.</td></tr>
                <?php else: ?>
                    <?php foreach ($porMetodo as $m): ?>
                        <tr>
                            <td><?= ucfirst(htmlspecialchars($m['metodo_pago'])) ?></td>
                            <td><?= (int)$m['cantidad'] ?></td>
                            <td><strong>$<?= number_format((float)$m['total'], 0, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="seccion-reporte">
        <h3>2. Productos Más Vendidos</h3>
        <table>
            <thead><tr><th>Producto</th><th>Categoría</th><th>Unidades Vendidas</th><th>Total Generado</th></tr></thead>
            <tbody>
                <?php if (empty($topProductos)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 15px;">Sin datos para el período seleccionado.</td></tr>
                <?php else: ?>
                    <?php foreach ($topProductos as $tp): ?>
                        <tr>
                            <td><?= htmlspecialchars($tp['producto']) ?></td>
                            <td><?= htmlspecialchars($tp['categoria']) ?></td>
                            <td><?= (int)$tp['unidades_vendidas'] ?></td>
                            <td><strong>$<?= number_format((float)$tp['total_generado'], 0, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="seccion-reporte">
        <h3>3. Ventas por Artesano</h3>
        <table>
            <thead><tr><th>Artesano</th><th>Comunidad</th><th>Piezas Vendidas</th><th>Total Generado</th></tr></thead>
            <tbody>
                <?php if (empty($porArtesano)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 15px;">Sin datos para el período seleccionado.</td></tr>
                <?php else: ?>
                    <?php foreach ($porArtesano as $art): ?>
                        <tr>
                            <td><?= htmlspecialchars($art['artesano']) ?></td>
                            <td><?= htmlspecialchars($art['comunidad']) ?></td>
                            <td><?= (int)$art['piezas_vendidas'] ?></td>
                            <td><strong>$<?= number_format((float)$art['total_ventas'], 0, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
window.datosReportes = {
    labelsMetodo: <?= json_encode($labelsMetodo, JSON_UNESCAPED_UNICODE) ?>,
    datosMetodo:  <?= json_encode($datosMetodo) ?>,
    labelsProd:   <?= json_encode($labelsProd, JSON_UNESCAPED_UNICODE) ?>,
    datosProd:    <?= json_encode($datosProd) ?>,
    labelsArt:    <?= json_encode($labelsArt, JSON_UNESCAPED_UNICODE) ?>,
    datosArt:     <?= json_encode($datosArt) ?>
};
</script>

<?php require_once 'includes/footer.php'; ?>

