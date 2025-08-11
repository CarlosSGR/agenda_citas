<?php
require 'db.php'; // conexión a la BD

if (!isset($_POST['id'])) {
    header("Location: admin.php");
    exit;
}

$id = intval($_POST['id']);
$stmt = $pdo->prepare("UPDATE citas SET estado = 'ausente' WHERE id = ?");
$stmt->execute([$id]);

header("Location: admin.php?msg=ausente");
exit;
