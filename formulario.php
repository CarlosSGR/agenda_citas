<!-- formulario.php - Formulario para agendar cita -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        form { max-width: 400px; margin: auto; display: flex; flex-direction: column; gap: 10px; }
        input, select, button { padding: 10px; font-size: 16px; }
    </style>
    <script>
    async function cargarHorasDisponibles() {
        
        const fecha = document.querySelector('[name="fecha"]').value;
        const horaSelect = document.querySelector('[name="hora"]');

        if (!fecha) return;

        const response = await fetch(`horas_disponibles.php?fecha=${fecha}`);
        const horas = await response.json();

        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
        horas.forEach(hora => {
            const option = document.createElement('option');
            option.value = hora;
            option.textContent = hora;
            horaSelect.appendChild(option);
        });

    }
    </script>
</head>
<body>
    <h2>Agendar una cita</h2>
    <form action="procesar_cita.php" method="POST">
        <input type="text" name="nombre" placeholder="Tu nombre" required>
        <input type="tel" name="telefono" placeholder="Tu teléfono" required>
        <select name="servicio" required>
            <option value="">Selecciona un servicio</option>
            <option value="Corte de cabello">Corte de cabello</option>
            <option value="Limpieza facial">Limpieza facial</option>
            <option value="Consulta general">Consulta general</option>
        </select>
        <input type="date" name="fecha" required onkeydown="return false" onchange="cargarHorasDisponibles()">
        <select name="hora" required>
            <option value="">Selecciona una hora</option>
        </select>
        <button type="submit">Agendar</button>
    </form>
</body>
</html>