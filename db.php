<?php
// db.php - conexión básica + configuración estable
// (Fase 1: Estabilizar y medir)

mysqli_report(MYSQLI_REPORT_OFF); // Evita notices que rompan salida JSON

// --- Zona horaria de México ---
date_default_timezone_set('America/Mexico_City');

// --- Logging a archivo (para depurar en Fase 1) ---
ini_set('log_errors', '1');
ini_set('display_errors', '0'); // no mostrar en pantalla en producción/mvp
ini_set('error_log', __DIR__ . '/app_errors.log'); // se crea automáticamente

// --- Credenciales ---
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'agenda';

// --- Conexión ---
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    error_log('[DB] Conexión fallida: ' . $conn->connect_error);
    die('Error de conexión a base de datos');
}

// --- Charset ---
if (!$conn->set_charset('utf8mb4')) {
    error_log('[DB] Error set_charset: ' . $conn->error);
}

// (Opcional) Validación rápida de timezone/charset en logs
error_log('[BOOT] DB conectada, tz=' . date_default_timezone_get() . ', charset=' . $conn->character_set_name());
