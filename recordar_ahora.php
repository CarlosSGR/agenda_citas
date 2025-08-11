<?php
// recordar_ahora.php — Dispara recordatorio inmediato para UNA cita (WhatsApp y/o Email)
// Requiere: auth.php, db.php, y (opcional) mailer.php si quieres enviar email real.

require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

$hasMailer = file_exists(__DIR__.'/mailer.php');
if ($hasMailer) require_once __DIR__.'/mailer.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID faltante']); exit; }

// Configs (con defaults por si aún no creas tablas de config)
$cfgNeg = ['tz'=>'America/Monterrey','whatsapp_cc'=>'52'];
$cfgRec = ['canal'=>'whatsapp','plantilla_whatsapp'=>'Hola {{cliente}}, te recordamos tu cita el {{fecha}} a las {{hora}} para {{servicio}}.','plantilla_email'=>'<p>Hola {{cliente}}, te recordamos tu cita el <strong>{{fecha}}</strong> a las <strong>{{hora}}</strong> para <em>{{servicio}}</em>.</p>'];

if ($conn->query("SHOW TABLES LIKE 'config_negocio'")->num_rows){
  $tmp = $conn->query("SELECT tz, whatsapp_cc FROM config_negocio WHERE id=1")->fetch_assoc();
  if ($tmp) $cfgNeg = array_merge($cfgNeg, $tmp);
}
if ($conn->query("SHOW TABLES LIKE 'config_recordatorios'")->num_rows){
  $tmp = $conn->query("SELECT canal, plantilla_whatsapp, plantilla_email FROM config_recordatorios WHERE id=1")->fetch_assoc();
  if ($tmp) $cfgRec = array_merge($cfgRec, $tmp);
}
date_default_timezone_set($cfgNeg['tz'] ?? 'America/Monterrey');

$cc = preg_replace('/\D/','', $cfgNeg['whatsapp_cc'] ?? '52');
$canal = $cfgRec['canal'] ?? 'whatsapp';
$tplWA = $cfgRec['plantilla_whatsapp'] ?: 'Hola {{cliente}}, te recordamos tu cita el {{fecha}} a las {{hora}} para {{servicio}}.';
$tplEM = $cfgRec['plantilla_email'] ?: '<p>Hola {{cliente}}, te recordamos tu cita el <strong>{{fecha}}</strong> a las <strong>{{hora}}</strong> para <em>{{servicio}}</em>.</p>';

// Carga cita
$sql = "SELECT c.id, c.fecha, c.hora, c.servicio, c.estado,
               cl.nombre, cl.telefono, cl.email
        FROM citas c JOIN clientes cl ON cl.id=c.cliente_id
        WHERE c.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$id);
$stmt->execute();
$cita = $stmt->get_result()->fetch_assoc();

if (!$cita) { echo json_encode(['ok'=>false,'msg'=>'Cita no encontrada']); exit; }
if (!in_array($cita['estado'], ['pendiente','confirmada'])) {
  echo json_encode(['ok'=>false,'msg'=>'Solo se recuerdan citas pendientes/confirmadas']); exit;
}

// Helpers
function hhmm($t){ return substr($t,0,5); }
function fefmt($d){ $x = DateTime::createFromFormat('Y-m-d',$d); return $x? $x->format('d/m/Y') : $d; }
function tpl($tpl,$data){ foreach($data as $k=>$v){ $tpl = str_replace('{{'.$k.'}}',$v,$tpl);} return $tpl; }

$data = [
  'cliente'  => $cita['nombre'],
  'fecha'    => fefmt($cita['fecha']),
  'hora'     => hhmm($cita['hora']),
  'servicio' => $cita['servicio']
];

$out = ['ok'=>true, 'acciones'=>[]];

// Asegurar tabla de log si no existe (no truena si ya existe)
$conn->query("CREATE TABLE IF NOT EXISTS recordatorios_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cita_id INT NOT NULL,
  canal ENUM('whatsapp','email') NOT NULL,
  destinatario VARCHAR(190) NOT NULL,
  mensaje TEXT NOT NULL,
  enviado_ok TINYINT(1) NOT NULL DEFAULT 0,
  error TEXT NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cita_canal (cita_id, canal),
  INDEX idx_cita (cita_id)
)");

// WhatsApp
if (in_array($canal, ['whatsapp','ambos'])) {
  $msg = tpl($tplWA, $data);
  $tel = $cc . preg_replace('/\D/','', $cita['telefono'] ?? '');
  if ($tel) {
    // registra o ignora si ya existe registro de WA para esta cita
    $q = $conn->prepare("SELECT id FROM recordatorios_log WHERE cita_id=? AND canal='whatsapp'");
    $q->bind_param('i',$id); $q->execute();
    if (!$q->get_result()->fetch_assoc()) {
      $ins = $conn->prepare("INSERT INTO recordatorios_log (cita_id, canal, destinatario, mensaje, enviado_ok) VALUES (?,?,?,?,1)");
      $can='whatsapp'; $ins->bind_param('isss', $id, $can, $tel, $msg); $ins->execute();
    }
    $out['acciones'][] = ['canal'=>'whatsapp','link'=>'https://wa.me/'.$tel.'?text='.rawurlencode($msg)];
  }
}

// Email
if (in_array($canal, ['email','ambos'])) {
  $email = trim($cita['email'] ?? '');
  if ($email) {
    $msg = tpl($tplEM, $data);
    $ok=false; $err=null;
    if ($hasMailer) { [$ok,$err] = send_mail($email, 'Recordatorio de cita', $msg); }
    $ins = $conn->prepare("INSERT INTO recordatorios_log (cita_id, canal, destinatario, mensaje, enviado_ok, error) VALUES (?,?,?,?,?,?)");
    $can='email'; $enviado=$ok?1:0; $ins->bind_param('isssis', $id,$can,$email,$msg,$enviado,$err); $ins->execute();
    $out['acciones'][] = ['canal'=>'email','ok'=>$ok,'error'=>$err];
  }
}

echo json_encode($out);
