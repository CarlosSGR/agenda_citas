<?php
// login.php
ob_start();                   // evita "headers already sent"
session_start();
require_once 'db.php';

$error = '';

// si ya estás logueado, entra directo
if (!empty($_SESSION['admin_logged_in'])) {
  header('Location: admin.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $conn->prepare("SELECT id, username, name, password_hash, is_active
                          FROM admins WHERE username = ? LIMIT 1");
  $stmt->bind_param("s", $usuario);
  $stmt->execute();
  $res  = $stmt->get_result();
  $user = $res->fetch_assoc();

  if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id']   = (int)$user['id'];
    $_SESSION['admin_name'] = $user['name'];
    header('Location: admin.php');   // 🚀
    exit;
  } else {
    $error = 'Usuario o contraseña incorrectos';
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
  <div class="wrap" style="max-width:420px">
    <h2>Iniciar sesión</h2>

    <?php if ($error): ?>
      <p class="muted" style="color:#ef4444;margin-top:0"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="login.php" autocomplete="off">
      <input name="usuario" placeholder="Usuario" required>
      <input name="password" type="password" placeholder="Contraseña" required>
      <button class="btn-primary" type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
<?php ob_end_flush(); ?>
