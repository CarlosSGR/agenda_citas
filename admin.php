<!-- admin.php - Panel para ver y gestionar citas -->
<?php
require_once 'auth.php';
require_once 'db.php';

// Filtros
$fecha = $_GET['fecha'] ?? '';
$estado = $_GET['estado'] ?? 'todas';
$buscar = trim($_GET['q'] ?? '');
$orden  = ($_GET['orden'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Query base
$sql = "SELECT 
          c.id,
          cl.nombre,
          cl.telefono,
          c.servicio,
          c.fecha,
          c.hora,
          c.estado,
          c.nota,
          c.confirmado_at,
          COALESCE(c.created_at, c.creado_en) AS creado_ts
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        WHERE 1=1";

// Aplica filtros
$params = [];
$types  = '';

if ($fecha !== '') {
  $sql .= " AND c.fecha = ?";
  $params[] = $fecha;
  $types   .= 's';
}
if ($estado !== '' && $estado !== 'todas') {
  $sql .= " AND c.estado = ?";
  $params[] = $estado;
  $types   .= 's';
}
if ($buscar !== '') {
  $like = "%$buscar%";
  $sql .= " AND (cl.nombre LIKE ? OR cl.telefono LIKE ? OR c.servicio LIKE ?)";
  array_push($params, $like, $like, $like);
  $types .= 'sss';
}

$sql .= " ORDER BY c.fecha $orden, c.hora $orden";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel • Mi Agenda</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
  <a class="btn-ghost" href="logout.php">Salir</a>

  <div class="appbar">
    <div class="brand">
      <img src="assets/img/logo.svg" alt="logo">
      <span>Mi Agenda</span>
    </div>
    <button class="theme-toggle" id="themeBtn" type="button" aria-label="Cambiar tema">🌙</button>
  </div>

  <div class="container">
    <h1 class="h1">Citas</h1>

    <form class="card" method="get" style="margin-bottom:1rem">
      <div class="filters">
        <div>
          <label>Fecha</label>
          <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" />
        </div>
        <div>
          <label>Estado</label>
          <select name="estado">
            <?php
              $opts = ['todas'=>'Todas','pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Completada'];
              foreach ($opts as $k=>$v) {
                $sel = $estado === $k ? 'selected' : '';
                echo "<option value=\"$k\" $sel>$v</option>";
              }
            ?>
          </select>
        </div>
        <div>
          <label>Buscar</label>
          <input type="text" name="q" placeholder="Cliente, teléfono o servicio" value="<?= htmlspecialchars($buscar) ?>" />
        </div>
        <div>
          <label>Orden</label>
          <select name="orden">
            <option value="ASC"  <?= $orden==='ASC'?'selected':''; ?>>Más próximas</option>
            <option value="DESC" <?= $orden==='DESC'?'selected':''; ?>>Más lejanas</option>
          </select>
        </div>
        <div>
          <button class="btn" type="submit">Aplicar filtros</button>
        </div>
      </div>
        <?php
        // Preserva filtros actuales en los enlaces de export
            $qs = http_build_query([
                'fecha'  => $_GET['fecha']  ?? '',
                'estado' => $_GET['estado'] ?? 'todas',
                'q'      => $_GET['q']      ?? '',
                'orden'  => $_GET['orden']  ?? 'ASC',
            ]);
        ?>
        <div class="card" style="margin-bottom:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <a class="btn" href="export_csv.php?<?= $qs ?>">Exportar CSV</a>
            <a class="btn" href="export_pdf.php?<?= $qs ?>">Exportar PDF</a>
            <span class="muted" style="font-size:.9rem">* Usa los filtros para exportar solo lo necesario.</span>
        </div>

    </form>

    <div class="table-wrap card">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Servicio</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estado</th>
            <th class="muted">Confirmado</th>
            <th class="muted">Creado</th>
            <th style="text-align:right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): 
            $wa = 'https://wa.me/52' . preg_replace('/\D/','',$row['telefono']) . '?text=' . rawurlencode(
              "Hola ".$row['nombre'].", te recordamos tu cita el ".$row['fecha']." a las ".substr($row['hora'],0,5)." por ".$row['servicio'].". Responde CONFIRMAR."
            );
          ?>
          <tr data-id="<?= $row['id'] ?>">
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><a class="muted" target="_blank" href="<?= $wa ?>"><?= htmlspecialchars($row['telefono']) ?></a></td>
            <td><?= htmlspecialchars($row['servicio']) ?></td>
            <td><?= htmlspecialchars($row['fecha']) ?></td>
            <td><?= substr($row['hora'],0,5) ?></td>
            <td>
              <span class="estado-pill estado-<?= htmlspecialchars($row['estado']) ?>">
                <?= ucfirst($row['estado']) ?>
              </span>
            </td>
            <td class="muted"><?= $row['confirmado_at'] ? date('Y-m-d H:i', strtotime($row['confirmado_at'])) : '—' ?></td>
            <td class="muted"><?= $row['creado_ts'] ? date('Y-m-d H:i', strtotime($row['creado_ts'])) : '—' ?></td>
            <td>
              <div class="actions">
                <?php if ($row['estado'] !== 'confirmada'): ?>
                  <button class="btn-xs js-accion" data-action="confirmar">Confirmar</button>
                <?php endif; ?>
                <?php if ($row['estado'] !== 'cancelada'): ?>
                  <button class="btn-xs js-accion" data-action="cancelar">Cancelar</button>
                <?php endif; ?>
                <?php if ($row['estado'] !== 'completada'): ?>
                  <button class="btn-xs js-accion" data-action="completar">Completar</button>
                <?php endif; ?>
                <button class="btn-xs js-reprogramar">Reprogramar</button>
                <button class="btn-xs js-nota">Nota</button>
                <button class="btn-xs js-recordar" data-id="<?= $cita['id'] ?>">Recordar ahora</button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <p class="muted" style="margin-top:.5rem">
      Tip: Haz clic en el teléfono para abrir WhatsApp con un mensaje prellenado.
    </p>
  </div>

  <script>
  // Tema persistente
  (function(){
    const saved = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
    const btn = document.getElementById('themeBtn');
    btn.textContent = saved === 'dark' ? '🌙' : '☀️';
    btn.addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', cur);
      localStorage.setItem('theme', cur);
      btn.textContent = cur === 'dark' ? '🌙' : '☀️';
    });
  })();

  function postAccion(id, accion, extra = {}) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('accion', accion);
    Object.entries(extra).forEach(([k,v])=> fd.append(k,v));

    return fetch('acciones_cita.php', { method: 'POST', body: fd })
      .then(r => r.json());
  }

  // Delegación de eventos
  document.addEventListener('click', async (e) => {
    const tr = e.target.closest('tr[data-id]');
    if (!tr) return;
    const id = tr.getAttribute('data-id');

    if (e.target.classList.contains('js-accion')) {
      const accion = e.target.dataset.action;
      const ok = confirm(`¿Seguro que quieres ${accion} la cita #${id}?`);
      if (!ok) return;

      const res = await postAccion(id, accion);
      if (!res.ok) return alert(res.msg || 'Error');

      location.reload();
    }

    if (e.target.classList.contains('js-reprogramar')) {
      const fecha = prompt('Nueva fecha (YYYY-MM-DD):', tr.children[4].textContent.trim());
      if (!fecha) return;
      const hora  = prompt('Nueva hora (HH:MM):', tr.children[5].textContent.trim());
      if (!hora) return;

      const res = await postAccion(id, 'reprogramar', { fecha, hora });
      if (!res.ok) return alert(res.msg || 'Error');
      location.reload();
    }

    if (e.target.classList.contains('js-nota')) {
      const nota = prompt('Escribe/edita la nota:');
      if (nota === null) return;
      const res = await postAccion(id, 'nota', { nota });
      if (!res.ok) return alert(res.msg || 'Error');
      alert('Nota guardada');
    }
  });

  document.addEventListener('click', async (e) => {
    const tr = e.target.closest('tr[data-id]');
    if (!tr) return;
    const id = tr.getAttribute('data-id');

    // --- Recordar ahora ---
    if (e.target.classList.contains('js-recordar')) {
      const ok = confirm(`¿Enviar recordatorio para la cita #${id}?`);
      if (!ok) return;

      const fd = new FormData();
      fd.append('id', id);

      try{
        const res = await fetch('recordar_ahora.php', { method:'POST', body:fd });
        const data = await res.json();
        if (!data.ok) return alert(data.msg || 'No se pudo recordar');

        // Si hay WhatsApp, abre la pestaña con el mensaje
        const wa = (data.acciones || []).find(a => a.canal === 'whatsapp' && a.link);
        if (wa && wa.link) window.open(wa.link, '_blank');

        // Si hay email, muestra resultado
        const em = (data.acciones || []).find(a => a.canal === 'email');
        if (em) alert(em.ok ? 'Email enviado' : ('Fallo email: ' + (em.error || '')));

      } catch(err){
        alert('Error de red');
      }
    }
  });
  </script>
</body>
</html>
<?php $conn->close(); ?>
