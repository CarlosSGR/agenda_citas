<?php
if (isset($_GET['fecha'])) {
    include 'db.php';
    $fecha = $_GET['fecha'];

    $ocupadas = [];
    $query = $conn->prepare("SELECT hora FROM citas WHERE fecha = ?");
    $query->bind_param("s", $fecha);
    $query->execute();
    $result = $query->get_result();
    while ($row = $result->fetch_assoc()) {
        $ocupadas[] = substr($row['hora'], 0, 5); // convierte "13:00:00" en "13:00"
    }

    $inicio = strtotime("09:00");
    $fin = strtotime("18:00");
    $intervalo = 30 * 60;
    $disponibles = [];

    for ($i = $inicio; $i <= $fin; $i += $intervalo) {
        $hora = date("H:i", $i);
        if (!in_array($hora, $ocupadas)) {
            $disponibles[] = $hora;
        }
    }


    header('Content-Type: application/json');
    echo json_encode($disponibles);
    exit;
}
?>
