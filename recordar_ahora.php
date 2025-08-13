<?php
// recordar_ahora.php (cURL + rate-limit local + manejo 429)
// Responde JSON: { ok, msg?, acciones:[{canal, link, ok, http_code, resp}] }

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/db.php';

    // ====== CONFIG ======
    $CALLMEBOT_APIKEY   = '1134701';  // <-- pon tu API key
    $COUNTRY_CODE       = '52';              // México
    $MX_ADD_ONE         = false;              // usa 521 para MX si tu número lo requiere
    $MIN_INTERVAL_SECS  = 60;                // anti-spam por teléfono (segundos)

    // ====== INPUT ======
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    // ====== CITA + CLIENTE ======
    $sql = "SELECT c.id, c.servicio, c.fecha, c.hora, c.estado, c.nota,
                   cl.nombre AS cliente, cl.telefono
            FROM citas c
            JOIN clientes cl ON cl.id = c.cliente_id
            WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log('[recordar_ahora] prepare failed: '.$conn->error); echo json_encode(['ok'=>false,'msg'=>'Error interno (prep)']); exit; }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) { error_log('[recordar_ahora] execute failed: '.$stmt->error); echo json_encode(['ok'=>false,'msg'=>'Error interno (exec)']); exit; }
    $res  = $stmt->get_result();
    $cita = $res->fetch_assoc();
    $stmt->close();
    if (!$cita) { echo json_encode(['ok'=>false,'msg'=>'Cita no encontrada']); exit; }

    // ====== Normalizar teléfono (MX) ======
    $raw = preg_replace('/\D+/', '', (string)$cita['telefono']);
    if (strlen($raw) === 10) {
        $tel_int = $COUNTRY_CODE . ($MX_ADD_ONE ? '1' : '') . $raw; // 52(1?) + 10d
    } elseif (preg_match('/^52\d{10}$/', $raw) || preg_match('/^521\d{10}$/', $raw)) {
        $tel_int = $raw;
    } else {
        $tel_int = $raw; // último recurso
    }
    if (strlen($tel_int) < 11) { echo json_encode(['ok'=>false,'msg'=>'Teléfono del cliente inválido']); exit; }
    $phone_param = '+' . $tel_int;

    // ====== Rate limit local por teléfono ======
    $tmpDir = __DIR__ . '/tmp';
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
    $lock   = $tmpDir . '/ratelimit_' . preg_replace('/\D/','',$tel_int) . '.lock';
    $now    = time();
    if (is_file($lock)) {
        $last = (int)trim(@file_get_contents($lock));
        $wait = $MIN_INTERVAL_SECS - ($now - $last);
        if ($wait > 0) {
            echo json_encode(['ok'=>false, 'msg'=>"Has enviado hace poco. Vuelve a intentar en ~{$wait}s."]); exit;
        }
    }
    @file_put_contents($lock, (string)$now);

    // ====== Mensaje ======
    $fecha = $cita['fecha'];
    $hora  = substr($cita['hora'], 0, 5);
    $serv  = $cita['servicio'];
    $nom   = $cita['cliente'];
    $mensaje = "Hola $nom, te recordamos tu cita de \"$serv\" el $fecha a las $hora. "
             . "Si necesitas reprogramar, responde este mensaje. ¡Gracias!";

    // ====== URL CallMeBot ======
    $url = "https://api.callmebot.com/whatsapp.php?phone=" . urlencode($phone_param)
         . "&text=" . urlencode($mensaje)
         . "&apikey=" . urlencode($CALLMEBOT_APIKEY);

    // ====== cURL ======
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'AgendaCitas/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    // Detección de rate limit del proveedor
    $tooMany = false;
    $textResp = $resp ?: $err;
    if (stripos((string)$textResp, 'Too many requests') !== false || $http == 429) {
        $tooMany = true;
    }

    if ($tooMany) {
        echo json_encode([
            'ok' => false,
            'msg' => 'El proveedor limitó las solicitudes. Intenta de nuevo en 1–2 minutos.',
            'acciones' => [[
                'canal'     => 'whatsapp',
                'link'      => $url,
                'ok'        => false,
                'http_code' => $http,
                'resp'      => $textResp,
            ]]
        ]);
        exit;
    }

    $ok_envio = ($resp !== false) && ($http >= 200 && $http < 300);

    echo json_encode([
        'ok' => true,
        'acciones' => [[
            'canal'     => 'whatsapp',
            'link'      => $url,       // por si quieres abrir manual
            'ok'        => $ok_envio,
            'http_code' => $http,
            'resp'      => $textResp
        ]]
    ]);
    exit;

} catch (Throwable $e) {
    error_log('[recordar_ahora] exception: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); exit;
}
