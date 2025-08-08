<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';

    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $servicio = trim($_POST['servicio'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $hora  = $_POST['hora'] ?? '';

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) { $msg='Nombre inválido'; }
    elseif (!preg_match('/^\d{10}$/', $telefono)) { $msg='Teléfono inválido'; }
    elseif ($servicio === '' || mb_strlen($servicio) > 100) { $msg='Servicio inválido'; }
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $msg='Fecha inválida'; }
    elseif (!preg_match('/^\d{2}:\d{2}$/', $hora)) { $msg='Hora inválida'; }
    else {
        $hoy = (new DateTime('today'))->format('Y-m-d');
        if ($fecha < $hoy) { $msg='No puedes agendar en fechas pasadas'; }
        else {
            function generarHoras($inicio='09:00', $fin='18:00', $stepMin=30){
                $arr=[]; $t=strtotime($inicio); $end=strtotime($fin);
                while($t <= $end){ $arr[] = date('H:i',$t); $t += $stepMin*60; }
                return $arr;
            }
            if (!in_array($hora, generarHoras())) { $msg='Hora no permitida'; }
            else {
                // Verificar cliente
                $stmt = $conn->prepare("SELECT id FROM clientes WHERE telefono = ?");
                $stmt->bind_param("s", $telefono);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($cliente_id);
                    $stmt->fetch();
                } else {
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
                    $stmt->bind_param("ss", $nombre, $telefono);
                    $stmt->execute();
                    $cliente_id = $stmt->insert_id;
                }
                $stmt->close();

                // Verificar cita duplicada
                $stmt = $conn->prepare("SELECT id FROM citas WHERE fecha = ? AND hora = ?");
                $stmt->bind_param("ss", $fecha, $hora);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $msg = 'Ya hay una cita agendada en esa fecha y hora';
                } else {
                    $stmt->close();
                    // Insertar cita
                    $stmt = $conn->prepare("INSERT INTO citas (cliente_id, servicio, fecha, hora) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $cliente_id, $servicio, $fecha, $hora);
                    if ($stmt->execute()) {
                        // Enviar WhatsApp
                        $jefeNumero = '5218331553040';
                        $apikey = '1134701';
                        $mensaje = "Nueva cita agendada:%0A" .
                                  "Cliente: $nombre%0A" .
                                  "Tel: $telefono%0A" .
                                  "Servicio: $servicio%0A" .
                                  "Fecha: $fecha%0A" .
                                  "Hora: $hora%0A" .
                                  "WhatsApp: https://wa.me/52$telefono";
                        $url = "https://api.callmebot.com/whatsapp.php?phone=$jefeNumero&text=" . urlencode($mensaje) . "&apikey=$apikey";
                        file_get_contents($url);

                        // Mensaje éxito
                        header("Location: formulario.php?status=ok");
                        exit;
                    } else {
                        $msg = 'Error al agendar la cita';
                    }
                }
            }
        }
    }

    if (isset($msg)) {
        header("Location: formulario.php?status=error&msg=" . urlencode($msg));
        exit;
    }
}
?>
