<!-- admin.php - Panel para ver y gestionar citas (filtros limpios + tabla apilada en móvil) -->
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$fecha  = $_GET['fecha']  ?? '';
$estado = $_GET['estado'] ?? 'todas';
$buscar = trim($_GET['q']  ?? '');
$orden  = ($_GET['orden'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

$sql = "SELECT 
          c.id,
          cl.nombre,
          cl.telefono,
          c.servicio,
          c.fecha,
          c.hora,
          c.estado,
          c.nota
        FROM citas c
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE 1=1";

$args = []; $types = '';

if ($fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $sql .= " AND c.fecha = ?"; $args[] = $fecha; $types .= 's'; }
$estadosValidos = ['todas','pendiente','confirmada','cancelada','completada'];
if (!in_array($estado, $estadosValidos)) $estado = 'todas';
if ($estado !== 'todas') { $sql .= " AND c.estado = ?"; $args[] = $estado; $types .= 's'; }
if ($buscar !== '') {
  $sql .= " AND (cl.nombre LIKE CONCAT('%',?,'%') 
            OR cl.telefono LIKE CONCAT('%',?,'%') 
            OR c.servicio LIKE CONCAT('%',?,'%'))";
  $args[] = $buscar; $types .= 's';
  $args[] = $buscar; $types .= 's';
  $args[] = $buscar; $types .= 's';
}
$sql .= " ORDER BY c.fecha $orden, c.hora $orden";

$prep_ok = true;
$stmt = $conn->prepare($sql);
if (!$stmt) { $prep_ok = false; error_log('[admin] prepare failed: '.$conn->error); }
else {
  if ($types) $stmt->bind_param($types, ...$args);
  if (!$stmt->execute()) { $prep_ok = false; error_log('[admin] execute failed: '.$stmt->error); }
}
$res = $prep_ok ? $stmt->get_result() : null;

$qs = http_build_query([
  'fecha'=>$fecha,'estado'=>$estado,'q'=>$buscar,'orden'=>$orden,
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Citas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/admin.css?v=2025-08-13-01">
</head>
<body>

  <!-- Header -->
  <header class="container mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <img src="assets/img/logo.svg" alt="logo" style="height:24px">
        <strong>Mi Agenda — Admin</strong>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-success" href="formulario.php">+ Nueva cita</a>
        <a class="btn btn-sm btn-outline-light" href="logout.php">Salir</a>
      </div>
    </div>
  </header>

  <main class="container">
    <!-- Filtros -->
    <div class="card p-3 p-md-4 mb-3">
      <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center">
        <h2 class="page-title m-0">Citas</h2>
        <div class="d-flex gap-2 order-0 order-md-1 export-actions">
          <a class="btn btn-success btn-sm" href="export_csv.php?<?= $qs ?>">Exportar CSV</a>
          <a class="btn btn-success btn-sm" href="export_pdf.php?<?= $qs ?>">Exportar PDF</a>
        </div>

      </div>

      <form class="mt-3" method="GET" action="admin.php">
        <div class="row g-3 filters">
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label">Fecha</label>
            <input class="form-control" type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
              <?php
                $opts = ['todas'=>'Todas','pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Completada'];
                foreach ($opts as $k=>$v) {
                  $sel = $estado === $k ? 'selected' : '';
                  echo "<option value=\"$k\" $sel>$v</option>";
                }
              ?>
            </select>
          </div>
          <div class="col-12 col-sm-8 col-lg-4">
            <label class="form-label">Buscar</label>
            <input class="form-control" type="text" name="q" placeholder="Cliente, teléfono o servicio" value="<?= htmlspecialchars($buscar) ?>">
          </div>
          <div class="col-6 col-sm-4 col-lg-2">
            <label class="form-label">Orden</label>
            <select class="form-select" name="orden">
              <option value="ASC"  <?= $orden==='ASC'?'selected':''; ?>>Más próximas</option>
              <option value="DESC" <?= $orden==='DESC'?'selected':''; ?>>Más lejanas</option>
            </select>
          </div>
          <div class="col-12">
            <button class="btn btn-primary w-100 w-sm-auto" type="submit">Aplicar filtros</button>
          </div>
        </div>
      </form>
    </div>

    <?php if (!$prep_ok): ?>
      <div class="alert alert-danger">Ocurrió un problema al cargar las citas. Revisa el log.</div>
    <?php else: ?>

    <!-- Tabla -->
    <div class="card p-2 p-md-3">
      <div class="table-responsive">
        <table class="table align-middle table-hover admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Teléfono</th>
              <th>Servicio</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($row = $res->fetch_assoc()): ?>
            <tr data-id="<?= (int)$row['id'] ?>">
              <td data-label="ID" class="text-muted">#<?= (int)$row['id'] ?></td>
              <td data-label="Cliente">
                <div class="fw-semibold"><?= htmlspecialchars($row['nombre']) ?></div>
                <?php if (!empty($row['nota'])): ?>
                  <div class="small text-secondary">📝 <?= htmlspecialchars($row['nota']) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Teléfono"><?= htmlspecialchars($row['telefono']) ?></td>
              <td data-label="Servicio"><?= htmlspecialchars($row['servicio']) ?></td>
              <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
              <td data-label="Hora"><?= substr($row['hora'],0,5) ?></td>
              <td data-label="Estado">
                <span class="estado-pill estado-<?= htmlspecialchars($row['estado']) ?>">
                  <?= ucfirst($row['estado']) ?>
                </span>
              </td>
              <td data-label="Acciones">
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
                  <button class="btn-xs js-recordar">Recordar ahora</button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>
  </main>

  <script>
    async function postAccion(id, accion, extra = {}) {
      const fd = new FormData();
      fd.append('id', id);
      fd.append('accion', accion);
      Object.entries(extra).forEach(([k,v]) => fd.append(k, v));
      const res = await fetch('acciones_cita.php', { method:'POST', body: fd });
      return await res.json();
    }

    document.addEventListener('click', async (e) => {
      const tr = e.target.closest('tr[data-id]');
      if (!tr) return;
      const id = tr.getAttribute('data-id');

      if (e.target.classList.contains('js-accion')) {
        const accion = e.target.getAttribute('data-action');
        const ok = confirm(`¿Seguro que deseas ${accion} la cita #${id}?`);
        if (!ok) return;
        const res = await postAccion(id, accion);
        if (!res.ok) return alert(res.msg || 'No se pudo completar la acción');
        location.reload();
        return;
      }

      if (e.target.classList.contains('js-reprogramar')) {
        const fecha = prompt('Nueva fecha (YYYY-MM-DD):'); if (!fecha) return;
        const hora  = prompt('Nueva hora (HH:MM):');      if (!hora)  return;
        const res = await postAccion(id, 'reprogramar', { fecha, hora });
        if (!res.ok) return alert(res.msg || 'No se pudo reprogramar');
        location.reload();
        return;
      }

      if (e.target.classList.contains('js-nota')) {
        const nota = prompt('Escribe/edita la nota:');
        if (nota === null) return;
        const res = await postAccion(id, 'nota', { nota });
        if (!res.ok) return alert(res.msg || 'No se pudo guardar la nota');
        alert('Nota guardada'); 
        location.reload();
        return;
      }

      if (e.target.classList.contains('js-recordar')) {
        const ok = confirm(`¿Enviar recordatorio para la cita #${id}?`);
        if (!ok) return;
        try{
          const fd = new FormData();
          fd.append('id', id);
          const r = await fetch('recordar_ahora.php', { method:'POST', body:fd });
          const data = await r.json();
          if (!data.ok) return alert(data.msg || 'No se pudo enviar');
          const wa = (data.acciones || []).find(a => a.canal === 'whatsapp' && a.link);
          if (wa && wa.link) window.open(wa.link, '_blank');
          const em = (data.acciones || []).find(a => a.canal === 'email');
          if (em) alert(em.ok ? 'Email enviado' : ('Fallo email: ' + (em.error || '')));
        } catch(err){ alert('Error de red'); }
        return;
      }
    });
  </script>

</body>
</html>
<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) { $stmt->close(); }
$conn->close();
?>
