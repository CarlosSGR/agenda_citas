<?php
// calendario.php
require_once __DIR__.'/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Calendario • Mi Agenda</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- FullCalendar (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales-all.global.min.js"></script>

  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .cal-wrap{max-width:1180px;margin:28px auto;padding:0 20px 28px}
    .cal-appbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .cal-title{font-size:1.6rem;font-weight:700}
    .fc .fc-toolbar-title{font-size:1rem}
    .fc .fc-button{border-radius:10px;padding:6px 10px;border:1px solid var(--border);background:#0e1530;color:var(--text)}
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:hover{background:#0b1224}
    .fc{--fc-page-bg-color: transparent; --fc-neutral-bg-color: rgba(255,255,255,.04); --fc-border-color: rgba(255,255,255,.08); --fc-list-event-hover-bg-color: rgba(255,255,255,.06);}
    .fc .fc-timegrid-slot{height: 1.9em}
    /* Colores por estado */
    .evt-pendiente{ background: #fff3cd !important; color:#7a5d00 !important; border:none!important}
    .evt-confirmada{ background:#d1e7dd !important; color:#0f5132 !important; border:none!important}
    .evt-cancelada{ background:#f8d7da !important; color:#842029 !important; border:none!important; text-decoration: line-through;}
    .evt-completada{ background:#dbeafe !important; color:#1e40af !important; border:none!important}
    /* Modal simple */
    .modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:16px}
    .modal.open{display:flex}
    .modal-card{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:16px;max-width:520px;width:100%}
    .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
    .btn{background: linear-gradient(90deg,var(--accent),#16a34a); color:white; border:none; padding:10px 14px; border-radius:12px; cursor:pointer; font-weight:600;}
    .btn-ghost{background:transparent;border:1px solid var(--border);padding:10px 14px;border-radius:12px;color:var(--text)}
  </style>
</head>
<body>
  <a class="btn-ghost" href="logout.php">Salir</a>

  <div class="cal-wrap">
    <div class="cal-appbar">
      <div class="cal-title">Calendario</div>
      <a class="btn" href="admin.php">Ir a listado</a>
    </div>

    <div id="calendar"></div>
  </div>

  <!-- Modal -->
  <div id="modal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-card">
      <div id="modalContent"></div>
      <div class="modal-actions">
        <button class="btn-ghost" id="closeBtn">Cerrar</button>
      </div>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.getElementById('calendar');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: 'es',
    initialView: 'timeGridWeek',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    nowIndicator: true,
    slotMinTime: '08:00:00',
    slotMaxTime: '20:00:00',
    selectable: false,
    editable: true,           // permite arrastrar
    eventOverlap: false,
    events: 'api_citas_json.php',
    eventClick(info){
      const p = info.event.extendedProps;
      const fecha = info.event.start.toLocaleDateString();
      const hora  = info.event.start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
      const html = `
        <h3 style="margin:0 0 8px 0">${p.cliente}</h3>
        <div class="muted" style="margin-bottom:8px">${p.servicio} · ${p.estado}</div>
        <div><strong>Fecha:</strong> ${fecha}</div>
        <div><strong>Hora:</strong> ${hora}</div>
        <div style="margin-top:10px">
          <a class="btn" href="admin.php?q=${encodeURIComponent(p.cliente)}" style="text-decoration:none">Ver en listado</a>
        </div>
      `;
      openModal(html);
    },
    async eventDrop(info){
      // al soltar, reprograma
      const id = info.event.id;
      const f = info.event.start;
      const fecha = f.toISOString().slice(0,10);
      const hora  = f.toTimeString().slice(0,5);

      const fd = new FormData();
      fd.append('accion','reprogramar');
      fd.append('id', id);
      fd.append('fecha', fecha);
      fd.append('hora', hora);

      try{
        const res = await fetch('acciones_cita.php',{method:'POST', body:fd});
        const data = await res.json();
        if(!data.ok){ alert(data.msg || 'No se pudo reprogramar'); info.revert(); }
      }catch(e){
        alert('Error de red'); info.revert();
      }
    }
  });

  calendar.render();

  // Modal básico
  const modal = document.getElementById('modal');
  const content = document.getElementById('modalContent');
  const closeBtn = document.getElementById('closeBtn');
  function openModal(html){ content.innerHTML = html; modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }
  function closeModal(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }
  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });
});
</script>
</body>
</html>
