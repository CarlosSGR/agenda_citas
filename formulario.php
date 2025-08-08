<!-- formulario.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agendar Cita</title>
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/formulario.css">
  <script>
    // ====== HORAS DISPONIBLES (AJAX) ======
    async function cargarHorasDisponibles() {
      const fecha = document.querySelector('[name="fecha"]').value;
      const horaSelect = document.querySelector('[name="hora"]');
      const horaWrap = document.getElementById('hora-wrap');
      const submitBtn = document.getElementById('btn-submit');
      if (!fecha) return;

      horaSelect.style.display = 'none';
      horaWrap.querySelector('.loader').style.display = 'block';
      submitBtn.disabled = true;

      try {
        const res = await fetch(`horas_disponibles.php?fecha=${fecha}`);
        const horas = await res.json();
        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
        horas.forEach(h => {
          const opt = document.createElement('option');
          opt.value = h; opt.textContent = h;
          horaSelect.appendChild(opt);
        });
      } catch(e) {
        console.error(e);
        showToast('No se pudieron cargar las horas. Intenta de nuevo.');
      } finally {
        horaWrap.querySelector('.loader').style.display = 'none';
        horaSelect.style.display = 'block';
        submitBtn.disabled = false;
      }
    }

    // ====== TOASTS (para validación/errores ligeros) ======
    function showToast(msg){
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(()=> t.classList.remove('show'), 2500);
    }

    // ====== VALIDACIÓN FRONT ======
    function markValid(el, ok){
      el.classList.toggle('is-valid', ok);
      el.classList.toggle('is-invalid', !ok);
    }
    function validateLive(){
      const n = document.querySelector('[name="nombre"]');
      const t = document.querySelector('[name="telefono"]');
      markValid(n, n.value.trim().length >= 2);
      markValid(t, /^\d{10}$/.test(t.value));
    }
    function validarSubmit(){
      const req = ['nombre','telefono','servicio','fecha','hora'];
      for (const n of req){
        const el = document.querySelector(`[name="${n}"]`);
        if(!el.value){ showToast('Completa todos los campos'); return false; }
      }
      return true;
    }

    // ====== DATEPICKER (input oculto + botón) ======
    function openPicker(){
      const input = document.getElementById('fecha');
      if (input.showPicker) input.showPicker();
      else { input.focus(); input.click(); }
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      // evitar escribir manual en fecha (aunque esté oculto)
      const f = document.getElementById('fecha');
      f.addEventListener('keydown', e => e.preventDefault());

      // actualizar texto del botón de fecha según selección
      const btn = document.querySelector('.date-btn');
      function updateBtn(){
        if(!f.value){ btn.textContent = '📅 Elegir fecha'; return; }
        const d = new Date(f.value + 'T00:00:00');
        const legible = d.toLocaleDateString('es-MX',{weekday:'short', day:'2-digit', month:'short', year:'numeric'});
        btn.textContent = `📅 ${legible}`;
      }
      f.addEventListener('change', updateBtn);
      updateBtn();

      // validación en vivo
      document.addEventListener('input', (e)=>{
        if (e.target.name === 'nombre' || e.target.name === 'telefono') validateLive();
      });

      // mostrar popup bonito según query (?status=ok|error&msg=...)
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
        // limpiar la URL sin recargar
        const url = new URL(location); url.search = ''; history.replaceState({}, '', url);
      }
    });
  </script>
</head>

<body>
  <!-- Header con logo + toggle -->
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

  <!-- Popup bonito (éxito/error) -->
  <div id="popup" class="popup"></div>

  <!-- Card del formulario -->
  <div class="card">
    <div class="title">
      <h2 style="margin:0">Agendar una cita</h2>
      <span class="hintf">Completa los campos y elige una hora disponible</span>
    </div>

    <form action="procesar_cita.php" method="POST" onsubmit="return validarSubmit()">
      <div>
        <label>Nombre</label>
        <input type="text" name="nombre" placeholder="Tu nombre" required minlength="2" maxlength="100" autocomplete="name" />
      </div>

      <div class="row">
        <div>
          <label>Teléfono</label>
          <input type="tel" name="telefono" placeholder="Tu teléfono" required
                 inputmode="numeric" pattern="\d{10}" maxlength="10" autocomplete="tel"
                 oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" />
        </div>
        <div>
          <label>Servicio</label>
          <select name="servicio" required>
            <option value="">Selecciona un servicio</option>
            <option value="Corte de cabello">Corte de cabello</option>
            <option value="Limpieza facial">Limpieza facial</option>
            <option value="Consulta general">Consulta general</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div>
          <label>Fecha</label>
          <div class="date-group">
            <input id="fecha" type="date" name="fecha" required onchange="cargarHorasDisponibles()" style="display:none" />
            <button type="button" class="date-btn" onclick="openPicker()" title="Elegir fecha">📅 Elegir fecha</button>
          </div>
          <div class="hint">Toca el botón para abrir el calendario</div>
        </div>

        <div>
          <label>Hora</label>
          <div id="hora-wrap">
            <div class="loader" style="display:none"></div>
            <select name="hora" required style="display:block">
              <option value="">Selecciona una hora</option>
            </select>
          </div>
        </div>
      </div>

      <button id="btn-submit" type="submit">Agendar</button>
      <div class="hint">* Solo se muestran horarios disponibles para la fecha elegida.</div>
    </form>
  </div>

  <!-- Toast inferior para mensajes ligeros -->
  <div id="toast" class="toast"></div>

  <!-- Estilos rápidos del popup (puedes moverlos a formulario.css si prefieres) -->
  <style>
    .popup{
      position: fixed; top: 20px; right: 20px; z-index: 9999;
      padding: 12px 16px; border-radius: 10px; font-weight: 700; color: #fff;
      opacity: 0; transform: translateY(-8px); transition: opacity .25s, transform .25s;
      background: #111;
      border: 1px solid rgba(148,163,184,.25);
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }
    .popup.show{ opacity:1; transform: translateY(0); }
    .popup.success{ background: linear-gradient(90deg, #22c55e, #16a34a); }
    .popup.error{ background: #ef4444; }
    .popup.hide{ opacity:0; }
  </style>
</body>
</html>
