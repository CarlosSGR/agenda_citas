<?php
// procesar_cita.php - Guarda la cita en la base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';

    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $servicio = $_POST['servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    $stmt = $conn->prepare("INSERT INTO citas (nombre, telefono, servicio, fecha, hora) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $telefono, $servicio, $fecha, $hora);

    if ($stmt->execute()) {
        echo "<script>alert('Cita agendada con éxito'); window.location.href='formulario.php';</script>";
    } else {
        echo "<script>alert('Error al agendar la cita'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
