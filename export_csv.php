<?php
// export_csv.php — Exporta citas a CSV con los filtros actuales (fecha, estado, q, orden)
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

// Filtros
$fecha  = $_GET['fecha']  ?? '';
$estado = $_GET['estado'] ?? 'todas';
$buscar = trim($_GET['q'] ?? '');
$orden  = ($_GET['orden'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Query base (idéntica a admin.php)
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

// Cabeceras para descarga
$fname = 'citas_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$fname);

// BOM para que Excel detecte UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
// Encabezados
fputcsv($out, ['ID','Cliente','Teléfono','Servicio','Fecha','Hora','Estado','Nota','Confirmado','Creado']);

// Filas
while ($r = $res->fetch_assoc()) {
  fputcsv($out, [
    $r['id'],
    $r['nombre'],
    $r['telefono'],
    $r['servicio'],
    $r['fecha'],
    substr($r['hora'],0,5),
    $r['estado'],
    $r['nota'],
    $r['confirmado_at'] ? date('Y-m-d H:i', strtotime($r['confirmado_at'])) : '',
    $r['creado_ts'] ? date('Y-m-d H:i', strtotime($r['creado_ts'])) : '',
  ]);
}
fclose($out);
$conn->close();
exit;
