<?php
// api_citas_json.php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

header('Content-Type: application/json; charset=utf-8');

// FullCalendar manda ?start=YYYY-MM-DD&end=YYYY-MM-DD
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
if (!$start || !$end) { echo json_encode([]); exit; }

$sql = "SELECT c.id, c.fecha, c.hora, c.servicio, c.estado,
               cl.nombre AS cliente, cl.telefono
        FROM citas c
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.fecha BETWEEN ? AND ?
        ORDER BY c.fecha, c.hora";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$events = [];
while ($r = $res->fetch_assoc()) {
  $startIso = $r['fecha'].'T'.substr($r['hora'],0,8);
  $events[] = [
    'id'    => (int)$r['id'],
    'title' => $r['cliente'].' — '.$r['servicio'],
    'start' => $startIso,
    'allDay'=> false,
    // para estilos según estado
    'classNames' => ['evt-'.$r['estado']],
    // info extra para el tooltip/modal
    'extendedProps' => [
      'estado'   => $r['estado'],
      'cliente'  => $r['cliente'],
      'telefono' => $r['telefono'],
      'servicio' => $r['servicio']
    ]
  ];
}
echo json_encode($events);
