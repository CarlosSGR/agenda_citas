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
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/admin.css">

  <script>
    function toggleSort(){
      const url = new URL(window.location);
      const now = (url.searchParams.get('orden')||'ASC').toUpperCase();
      const next = now === 'ASC' ? 'DESC' : 'ASC';
      url.searchParams.set('orden', next);
      window.location = url.toString();
    }
  </script>
</head>

<body>
    <div class="appbar">
        <div class="brand">
            <img src="assets/img/logo.svg" alt="logo">
            <span>Mi Agenda</span>
        </div>
        <button class="theme-toggle" id="themeBtn" type="button" aria-label="Cambiar tema">🌙</button>
    </div>
    <script>
    // Tema persistente
    (function(){
        const saved = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
        const btn = document.getElementById('themeBtn');
        if(btn) btn.textContent = saved==='dark'?'🌙':'☀️';
    })();
    document.getElementById('themeBtn')?.addEventListener('click', ()=>{
        const root = document.documentElement;
        const next = root.getAttribute('data-theme')==='light' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        document.getElementById('themeBtn').textContent = next==='dark'?'🌙':'☀️';
    });
    </script>

    <div class="wrap">
    <h2>Citas agendadas</h2>

    <form method="GET" action="">
        <input type="date" name="fecha" value="<?= htmlspecialchars($busqueda) ?>">
        <select name="orden">
            <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Ascendente</option>
            <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Descendente</option>
        </select>
        <button type="submit" class="btn-primary">Filtrar</button>
        <button type="button" class="btn-ghost" onclick="window.location.href='admin.php'">Quitar filtros</button>
    </form>




    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Servicio</th>
                <th class="th-sortable" onclick="toggleSort()">Fecha</th>
                <th class="th-sortable" onclick="toggleSort()">Hora</th>
                <th>Creado en</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><a class="muted" href="https://wa.me/52<?= htmlspecialchars($row['telefono']) ?>" target="_blank"><?= htmlspecialchars($row['telefono']) ?></a></td>
                    <td><?= htmlspecialchars($row['servicio']) ?></td>
                    <td><?= $row['fecha'] ?></td>
                    <td><?= substr($row['hora'],0,5) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($row['creado_en'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>