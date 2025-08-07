<!-- admin.php - Panel para ver citas agendadas con filtros -->
<?php
include 'db.php';
$busqueda = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$order = isset($_GET['orden']) ? $_GET['orden'] : 'ASC';
$sql = "SELECT c.id, cl.nombre, cl.telefono, c.servicio, c.fecha, c.hora, c.creado_en
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id";

if (!empty($busqueda)) {
    $sql .= " WHERE c.fecha = '" . $conn->real_escape_string($busqueda) . "'";
}

$sql .= " ORDER BY c.fecha $order, c.hora $order";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Citas</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; cursor: pointer; }
        form { margin-bottom: 20px; display: flex; gap: 10px; }
        input[type="date"] { padding: 5px; font-size: 16px; }
        button { padding: 6px 12px; font-size: 16px; }
    </style>
</head>
<body>
    <h2>Citas agendadas</h2>

    <form method="GET" action="">
        <input type="date" name="fecha" value="<?= htmlspecialchars($busqueda) ?>">
        <select name="orden">
            <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Ascendente</option>
            <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Descendente</option>
        </select>
        <button type="submit">Filtrar</button>
        <button type="button" onclick="window.location.href='admin.php'">Quitar filtros</button>
    </form>



    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Servicio</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Creado en</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['telefono']) ?></td>
                    <td><?= htmlspecialchars($row['servicio']) ?></td>
                    <td><?= $row['fecha'] ?></td>
                    <td><?= $row['hora'] ?></td>
                    <td><?= $row['creado_en'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
<?php $conn->close(); ?>