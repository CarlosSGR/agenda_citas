<?php
// acciones_cita.php – Acciones sobre citas vía POST (AJAX o form)
// Acciones soportadas: confirmar, cancelar, completar, reprogramar, nota

require_once __DIR__ . '/auth.php'; // asegura login
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id || $accion === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Parámetros faltantes']);
  exit;
}

try {
  switch ($accion) {
    case 'confirmar':
      $stmt = $conn->prepare("UPDATE citas SET estado='confirmada', confirmado_at=NOW() WHERE id=?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      echo json_encode(['ok'=>true]); 
      break;

    case 'cancelar':
      $stmt = $conn->prepare("UPDATE citas SET estado='cancelada' WHERE id=?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      echo json_encode(['ok'=>true]); 
      break;

    case 'completar':
      $stmt = $conn->prepare("UPDATE citas SET estado='completada' WHERE id=?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      echo json_encode(['ok'=>true]); 
      break;

    case 'reprogramar':
      $fecha = $_POST['fecha'] ?? '';
      $hora  = $_POST['hora']  ?? '';
      // normaliza HH:MM a HH:MM:SS
      if ($hora && strlen($hora) === 5) $hora .= ':00';

      if (!$fecha || !$hora) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'Fecha y hora son obligatorias']);
        break;
      }

      $stmt = $conn->prepare("UPDATE citas SET fecha=?, hora=? WHERE id=?");
      $stmt->bind_param('ssi', $fecha, $hora, $id);
      if (!$stmt->execute() && $conn->errno == 1062) {
        // por el UNIQUE(fecha,hora)
        echo json_encode(['ok'=>false,'msg'=>'Ese horario ya está ocupado.']);
        break;
      }
      echo json_encode(['ok'=>true]);
      break;

    case 'nota':
      $nota = $_POST['nota'] ?? '';
      $stmt = $conn->prepare("UPDATE citas SET nota=? WHERE id=?");
      $stmt->bind_param('si', $nota, $id);
      $stmt->execute();
      echo json_encode(['ok'=>true]);
      break;

    default:
      http_response_code(400);
      echo json_encode(['ok'=>false,'msg'=>'Acción no válida']);
  }

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error del servidor']);
}

$conn->close();
