<?php
// procesar_cita.php - Guarda la cita con cliente separado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';

    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $servicio = $_POST['servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    // Verificar si el cliente ya existe
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE telefono = ?");
    $stmt->bind_param("s", $telefono);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($cliente_id);
        $stmt->fetch();
    } else {
        $stmt->close();
        // Insertar nuevo cliente
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $telefono);
        $stmt->execute();
        $cliente_id = $stmt->insert_id;
    }
    $stmt->close();

    // Verificar si ya existe cita en esa fecha y hora
    $stmt = $conn->prepare("SELECT id FROM citas WHERE fecha = ? AND hora = ?");
    $stmt->bind_param("ss", $fecha, $hora);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Ya hay una cita agendada en esa fecha y hora.'); window.history.back();</script>";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Insertar la cita
    $stmt = $conn->prepare("INSERT INTO citas (cliente_id, servicio, fecha, hora) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $cliente_id, $servicio, $fecha, $hora);

    if ($stmt->execute()) {
        echo "<script>alert('Cita agendada con éxito'); window.location.href='formulario.php';</script>";
    } else {
        echo "<script>alert('Error al agendar la cita'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
