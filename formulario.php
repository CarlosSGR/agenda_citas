<!-- formulario.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Agendar Cita</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/formulario.css">

  <script>
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
        console.error(e); showToast('No se pudieron cargar las horas. Intenta de nuevo.');
      } finally {
        horaWrap.querySelector('.loader').style.display = 'none';
        horaSelect.style.display = 'block';
        submitBtn.disabled = false;
      }
    }
    function showToast(msg){
      const t = document.getElementById('toast'); t.textContent = msg; t.classList.add('show');
      setTimeout(()=> t.classList.remove('show'), 2500);
    }
    function validarSubmit(){
      const req = ['nombre','telefono','servicio','fecha','hora'];
      for (const n of req){ const el = document.querySelector(`[name="${n}"]`); if(!el.value){ showToast('Completa todos los campos'); return false;} }
      return true;
    }
    function openPicker(){
      const input = document.getElementById('fecha');
      if (input.showPicker) input.showPicker(); else { input.focus(); input.click(); }
    }
    document.addEventListener('DOMContentLoaded', ()=>{
      const f = document.getElementById('fecha');
      f.addEventListener('keydown', e => e.preventDefault()); // sin input manual
    });

    function openPicker() {
        document.getElementById('fecha').showPicker();
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

  <div class="card">
    <div class="title">
      <h2 style="margin:0">Agendar una cita</h2>
      <span class="hintf">Completa los campos y elige una hora disponible</span>
    </div>

    <form action="procesar_cita.php" method="POST" onsubmit="return validarSubmit()">
      <div>
        <label>Nombre</label>
        <input type="text" name="nombre" placeholder="Tu nombre" required />
      </div>

      <div class="row">
        <div>
          <label>Teléfono</label>
          <input type="tel" name="telefono" placeholder="Tu teléfono" required />
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
                <input id="fecha" type="date" name="fecha" required onchange="cargarHorasDisponibles()" style="display:none"/>
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

  <div id="toast" class="toast"></div>
</body>
</html>
