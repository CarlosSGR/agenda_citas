<?php
// export_pdf.php — Exporta citas a PDF (si hay Dompdf) o vista imprimible (fallback)
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

// Filtros
$fecha  = $_GET['fecha']  ?? '';
$estado = $_GET['estado'] ?? 'todas';
$buscar = trim($_GET['q'] ?? '');
$orden  = ($_GET['orden'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Query (igual a admin.php)
$sql = "SELECT 
          c.id,
          cl.nombre,
          cl.telefono,
          c.servicio,
          c.fecha,
          c.hora,
          c.estado,
          c.nota,
          c.confirmado_at,
          COALESCE(c.created_at, c.creado_en) AS creado_ts
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        WHERE 1=1";

$params = [];
$types  = '';

if ($fecha !== '') {
  $sql .= " AND c.fecha = ?";
  $params[] = $fecha;
  $types   .= 's';
}
if ($estado !== '' && $estado !== 'todas') {
  $sql .= " AND c.estado = ?";
  $params[] = $estado;
  $types   .= 's';
}
if ($buscar !== '') {
  $like = "%$buscar%";
  $sql .= " AND (cl.nombre LIKE ? OR cl.telefono LIKE ? OR c.servicio LIKE ?)";
  array_push($params, $like, $like, $like);
  $types .= 'sss';
}

$sql .= " ORDER BY c.fecha $orden, c.hora $orden";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$conn->close();

// HTML base
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<meta charset="utf-8">
<title>Export Citas</title>
<style>
  body{ font-family: Arial, Helvetica, sans-serif; color:#111; }
  .header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
  .title{ font-size:18px; font-weight:bold; }
  .filters{ font-size:12px; color:#555; }
  table{ width:100%; border-collapse:collapse; }
  th, td{ border:1px solid #ccc; padding:8px 10px; font-size:12px; }
  thead th{ background:#f3f4f6; text-align:left; }
  .muted{ color:#666; }
</style>
<body>
  <div class="header">
    <div class="title">Listado de citas</div>
    <div class="filters">
      <?php if($fecha)  echo "Fecha: <strong>$fecha</strong> &nbsp;"; ?>
      <?php if($estado && $estado!=='todas') echo "Estado: <strong>$estado</strong> &nbsp;"; ?>
      <?php if($buscar) echo "Buscar: <strong>".htmlspecialchars($buscar)."</strong>"; ?>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th><th>Cliente</th><th>Teléfono</th><th>Servicio</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Nota</th><th>Confirmado</th><th>Creado</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['nombre']) ?></td>
        <td><?= htmlspecialchars($r['telefono']) ?></td>
        <td><?= htmlspecialchars($r['servicio']) ?></td>
        <td><?= htmlspecialchars($r['fecha']) ?></td>
        <td><?= substr($r['hora'],0,5) ?></td>
        <td><?= ucfirst($r['estado']) ?></td>
        <td><?= htmlspecialchars((string)$r['nota']) ?></td>
        <td><?= $r['confirmado_at'] ? date('Y-m-d H:i', strtotime($r['confirmado_at'])) : '' ?></td>
        <td><?= $r['creado_ts'] ? date('Y-m-d H:i', strtotime($r['creado_ts'])) : '' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
<?php
$html = ob_get_clean();

// Si está Dompdf, genera PDF; si no, muestra HTML imprimible
$dompdfAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($dompdfAutoload)) {
  require_once $dompdfAutoload;
  $dompdf = new Dompdf\Dompdf([
    'chroot' => __DIR__,         // seguridad
    'isRemoteEnabled' => false
  ]);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'landscape');
  $dompdf->render();
  $dompdf->stream('citas_'.date('Ymd_His').'.pdf', ['Attachment' => true]);
  exit;
} else {
  // Fallback: vista imprimible (Ctrl+P -> Guardar como PDF)
  header('Content-Type: text/html; charset=utf-8');
  echo $html;
  exit;
}
