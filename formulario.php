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
        <input type="date" name="fecha" required>
        <input type="time" name="hora" required>
        <button type="submit">Agendar</button>
    </form>
</body>
</html>
