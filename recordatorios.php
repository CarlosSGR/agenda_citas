<?php
// recordatorios.php — ejecutar diario (cron) para enviar recordatorios
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

// Si usarás email:
$hasMailer = file_exists(__DIR__.'/mailer.php');
if ($hasMailer) { require_once __DIR__.'/mailer.php'; }

// === 1) Cargar configs ===
$cfgNeg = $conn->query("SELECT nombre, tz, whatsapp_cc FROM config_negocio WHERE id=1")->fetch_assoc();
$cfgRec = $conn->query("SELECT canal, hora, antelacion_dias, plantilla_whatsapp, plantilla_email FROM config_recordatorios WHERE id=1")->fetch_assoc();

$tz          = $cfgNeg['tz'] ?? 'America/Monterrey';
$cc          = preg_replace('/\D/','', $cfgNeg['whatsapp_cc'] ?? '52');
$canal       = $cfgRec['canal'] ?? 'whatsapp';
$horaEjec    = $cfgRec['hora'] ?? '09:00:00';
$antelacion  = (int)($cfgRec['antelacion_dias'] ?? 1);
$tplWA       = $cfgRec['plantilla_whatsapp'] ?: 'Hola {{cliente}}, te recordamos tu cita el {{fecha}} a las {{hora}} para {{servicio}}.';
$tplEmail    = $cfgRec['plantilla_email'] ?: '<p>Hola {{cliente}}, te recordamos tu cita el <strong>{{fecha}}</strong> a las <strong>{{hora}}</strong> para <em>{{servicio}}</em>.</p>';

date_default_timezone_set($tz);

// === 2) Calcular ventana de fechas ===
$target = (new DateTime('today'))->modify("+{$antelacion} day")->format('Y-m-d');

// === 3) Traer citas objetivo (solo pendientes o confirmadas, no canceladas/completadas) ===
$sql = "SELECT c.id, c.fecha, c.hora, c.servicio, c.estado, cl.nombre, cl.telefono, cl.email
        FROM citas c
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.fecha = ? AND c.estado IN ('pendiente','confirmada')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $target);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

// === 4) Helpers ===
function render_template($tpl, $data){
  $out = $tpl;
  foreach ($data as $k=>$v){
    $out = str_replace('{{'.$k.'}}', $v, $out);
  }
  return $out;
}
function hora_hhmm($hhmmss){
  return substr($hhmmss, 0, 5);
}
function fecha_linda($iso){
  $d = DateTime::createFromFormat('Y-m-d', $iso);
  return $d ? $d->format('d/m/Y') : $iso;
}

// === 5) Procesar envíos ===
$report = []; // para mostrar en navegador
foreach ($rows as $cita){
  $dataMsg = [
    'cliente'  => $cita['nombre'],
    'fecha'    => fecha_linda($cita['fecha']),
    'hora'     => hora_hhmm($cita['hora']),
    'servicio' => $cita['servicio']
  ];

  // WhatsApp
  if (in_array($canal, ['whatsapp','ambos'])) {
    $mensaje = render_template($tplWA, $dataMsg);
    $tel = $cc . preg_replace('/\D/','', $cita['telefono'] ?? '');

    // evitar duplicado
    $stmt = $conn->prepare("SELECT id FROM recordatorios_log WHERE cita_id=? AND canal='whatsapp'");
    $stmt->bind_param('i', $cita['id']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();

    if (!$exists && $tel) {
      // Aquí no enviamos automático (recomendado por ahora): generamos link wa.me
      $waLink = 'https://wa.me/'.$tel.'?text='.rawurlencode($mensaje);

      // Log como "enviado_ok=1" para no repetir (si prefieres, márcalo 0 y mándalo manualmente)
      $stmt = $conn->prepare("INSERT INTO recordatorios_log (cita_id, canal, destinatario, mensaje, enviado_ok) VALUES (?,?,?,?,1)");
      $stmt->bind_param('isss', $cita['id'], $ch='whatsapp', $tel, $mensaje);
      $stmt->execute();

      $report[] = ['cita'=>$cita['id'], 'canal'=>'whatsapp', 'dest'=>$tel, 'ok'=>true, 'link'=>$waLink];
    }
  }

  // Email
  if (in_array($canal, ['email','ambos'])) {
    $email = trim($cita['email'] ?? '');
    if ($email) {
      // evitar duplicado
      $stmt = $conn->prepare("SELECT id FROM recordatorios_log WHERE cita_id=? AND canal='email'");
      $stmt->bind_param('i', $cita['id']);
      $stmt->execute();
      $exists = $stmt->get_result()->fetch_assoc();

      if (!$exists){
        $subject = 'Recordatorio de cita';
        $html = render_template($tplEmail, $dataMsg);

        $ok = false; $err = null;
        if ($hasMailer){
          [$ok, $err] = send_mail($email, $subject, $html);
        } else {
          $ok = false;
          $err = 'PHPMailer no instalado';
        }

        $stmt = $conn->prepare("INSERT INTO recordatorios_log (cita_id, canal, destinatario, mensaje, enviado_ok, error) VALUES (?,?,?,?,?,?)");
        $enviado = $ok ? 1 : 0;
        $can = 'email';
        $stmt->bind_param('isssis', $cita['id'], $can, $email, $html, $enviado, $err);
        $stmt->execute();

        $report[] = ['cita'=>$cita['id'], 'canal'=>'email', 'dest'=>$email, 'ok'=>$ok, 'error'=>$err];
      }
    }
  }
}

// === 6) Salida: si lo corres en navegador, muestra resumen bonito; si es cron, no pasa nada ===
if (php_sapi_name() !== 'cli') {
  echo "<h3>Recordatorios para $target</h3>";
  if (!$rows) {
    echo "<p>No hay citas para recordar.</p>";
  } else {
    echo "<table border='1' cellpadding='6' cellspacing='0'><tr>
            <th>Cita</th><th>Canal</th><th>Destinatario</th><th>Estado</th><th>Acción</th></tr>";
    foreach ($report as $r){
      $accion = '';
      if ($r['canal']==='whatsapp' && !empty($r['link'])) {
        $accion = "<a target='_blank' href='{$r['link']}'>Abrir WhatsApp</a>";
      } elseif ($r['canal']==='email') {
        $accion = $r['ok'] ? 'Email enviado' : 'Fallo email: '.htmlspecialchars($r['error']);
      }
      echo "<tr>
              <td>{$r['cita']}</td>
              <td>{$r['canal']}</td>
              <td>".htmlspecialchars($r['dest'])."</td>
              <td>".($r['ok']?'OK':'PEND/ERR')."</td>
              <td>{$accion}</td>
            </tr>";
    }
    echo "</table>";
  }
}

$conn->close();
