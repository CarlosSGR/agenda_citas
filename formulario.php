<!-- formulario.php (campos más grandes y menos espacio vertical) -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agendar Cita</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/formulario.css?v=2025-08-13">

  <style>
    /* Ajustes visuales */
    .form-control, .form-select, .date-btn, #btn-submit {
      height: 50px !important;
      font-size: 1rem !important;
      padding: 0.5rem 0.75rem !important;
    }
    .row.g-3 {
      --bs-gutter-y: 0.75rem; /* menos espacio vertical */
    }
    .mb-3 { margin-bottom: 0.75rem !important; }
    .title h2 { color: var(--text) !important; }
    header .btn, .btn.w-auto { width: auto !important; }
  </style>

  <script>
    async function cargarHorasDisponibles() {
      const fecha = document.querySelector('[name="fecha"]').value;
      const horaSelect = document.querySelector('[name="hora"]');
      const horaWrap = document.getElementById('hora-wrap');
      const submitBtn = document.getElementById('btn-submit');
      if (!fecha) return;

      horaSelect.style.display = 'none';
      const loader = horaWrap.querySelector('.loader');
      if (loader) loader.style.display = 'block';
      if (submitBtn) submitBtn.disabled = true;

      try {
        const res = await fetch(`horas_disponibles.php?fecha=${encodeURIComponent(fecha)}`);
        const horas = await res.json();
        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
        (horas || []).forEach(h => {
          const opt = document.createElement('option');
          opt.value = h; opt.textContent = h;
          horaSelect.appendChild(opt);
        });
      } catch(e) {
        console.error(e);
        showToast('No se pudieron cargar las horas. Intenta de nuevo.');
      } finally {
        if (loader) loader.style.display = 'none';
        horaSelect.style.display = 'block';
        if (submitBtn) submitBtn.disabled = false;
      }
    }

    function showToast(msg){
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(()=> t.classList.remove('show'), 2500);
    }

    function markValid(el, ok){
      el.classList.toggle('is-valid', ok);
      el.classList.toggle('is-invalid', !ok);
    }
    function validateLive(){
      const n = document.querySelector('[name="nombre"]');
      const t = document.querySelector('[name="telefono"]');
      if (n) markValid(n, n.value.trim().length >= 2);
      if (t) markValid(t, /^\d{10}$/.test(t.value));
    }
    function validarSubmit(){
      const req = ['nombre','telefono','servicio','fecha','hora'];
      for (const n of req){
        const el = document.querySelector(`[name="${n}"]`);
        if(!el || !el.value){ showToast('Completa todos los campos'); return false; }
      }
      return true;
    }

    function openPicker(){
      const input = document.getElementById('fecha');
      if (!input) return;
      if (input.showPicker) input.showPicker();
      else { input.focus(); input.click(); }
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      const f = document.getElementById('fecha');
      if (f) f.addEventListener('keydown', e => e.preventDefault());

      const btn = document.querySelector('.date-btn');
      function updateBtn(){
        if(!f || !btn) return;
        if(!f.value){ btn.textContent = '📅 Elegir fecha'; return; }
        const d = new Date(f.value + 'T00:00:00');
        const legible = d.toLocaleDateString('es-MX',{weekday:'short', day:'2-digit', month:'short', year:'numeric'});
        btn.textContent = `📅 ${legible}`;
      }
      if (f) f.addEventListener('change', updateBtn);
      updateBtn();

      document.addEventListener('input', (e)=>{
        if (e.target.name === 'nombre' || e.target.name === 'telefono') validateLive();
      });

      const p = new URLSearchParams(location.search);
      const status = p.get('status');
      if (status) {
        const popup = document.getElementById('popup');
        const msg = p.get('msg');
        if (status === 'ok') {
          popup.classList.add('success');
          popup.textContent = '✅ Cita agendada con éxito';
        } else {
          popup.classList.add('error');
          popup.textContent = '❌ ' + (msg ? msg : 'Ocurrió un error');
        }
        popup.classList.add('show');
        setTimeout(()=> popup.classList.add('hide'), 3000);
        const url = new URL(location); url.search = ''; history.replaceState({}, '', url);
      }

      const saved = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', saved);
      const themeBtn = document.getElementById('themeBtn');
      if (themeBtn) themeBtn.textContent = saved==='dark'?'🌙':'☀️';
      themeBtn?.addEventListener('click', ()=>{
        const root = document.documentElement;
        const next = root.getAttribute('data-theme')==='light' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        themeBtn.textContent = next==='dark'?'🌙':'☀️';
      });
    });
  </script>
</head>

<body class="py-4">
  <!-- Header -->
  <header class="container mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <img src="assets/img/logo.svg" alt="logo" style="height:24px">
        <span class="fw-semibold">Mi Agenda</span>
      </div>
      <button class="btn btn-sm btn-outline-light w-auto" id="themeBtn" type="button" aria-label="Cambiar tema">🌙</button>
    </div>
  </header>

  <div id="popup" class="popup"></div>

  <main class="container">
    <div class="card p-3 p-md-4 mx-auto" style="max-width: 680px;">
      <div class="title mb-2">
        <h2 class="m-0 fw-bold">Agendar una cita</h2>
        <span class="hintf">Completa los campos y elige una hora disponible</span>
      </div>

      <form action="procesar_cita.php" method="POST" onsubmit="return validarSubmit()" novalidate>
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input class="form-control" type="text" name="nombre" placeholder="Tu nombre"
                 required minlength="2" maxlength="100" autocomplete="name" />
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Teléfono</label>
            <input class="form-control" type="tel" name="telefono" placeholder="Tu teléfono" required
                   inputmode="numeric" pattern="\d{10}" maxlength="10" autocomplete="tel"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" />
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Servicio</label>
            <select class="form-select" name="servicio" required>
              <option value="">Selecciona un servicio</option>
              <option value="Corte de cabello">Corte de cabello</option>
              <option value="Limpieza facial">Limpieza facial</option>
              <option value="Consulta general">Consulta general</option>
            </select>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12 col-md-6">
            <label class="form-label">Fecha</label>
            <div class="d-flex gap-2 align-items-stretch date-group">
              <input id="fecha" type="date" name="fecha" required onchange="cargarHorasDisponibles()" style="display:none" />
              <button type="button" class="btn date-btn flex-grow-1" onclick="openPicker()" title="Elegir fecha">📅 Elegir fecha</button>
            </div>
            <div class="hint mt-1">Toca el botón para abrir el calendario</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Hora</label>
            <div id="hora-wrap">
              <div class="loader" style="display:none"></div>
              <select class="form-select" name="hora" required>
                <option value="">Selecciona una hora</option>
              </select>
            </div>
          </div>
        </div>

        <div class="d-grid mt-3">
          <button id="btn-submit" class="btn btn-success btn-lg" type="submit">Agendar</button>
        </div>
        <div class="hint mt-2">* Solo se muestran horarios disponibles para la fecha elegida.</div>
      </form>
    </div>
  </main>

  <div id="toast" class="toast"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>
</body>
</html>
