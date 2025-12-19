<?php
declare(strict_types=1);

// Configuración de base de datos (ajusta según tu entorno)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = 'Clientes_db';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

function sanitize(?string $value, int $maxLength = 1000): string {
    return mb_substr(trim((string)$value), 0, $maxLength);
}

$usuario    = sanitize($_POST['usuario'] ?? '', 50);
$contrasena = trim((string)($_POST['contrasena'] ?? ''));
$situacion  = sanitize($_POST['situacion_empresarial'] ?? '', 10);
$finanzas   = sanitize($_POST['finanzas'] ?? '', 2000);
$balances   = sanitize($_POST['balances'] ?? '', 2000);
$indices    = sanitize($_POST['indices_propios'] ?? '', 2000);

$errors = [];
if ($usuario === '') { $errors[] = 'El usuario es obligatorio.'; }
if (strlen($contrasena) < 6) { $errors[] = 'La contraseña debe tener al menos 6 caracteres.'; }
if (!in_array($situacion, ['RI', 'PyME'], true)) { $errors[] = 'Selecciona la situación empresarial correcta.'; }

$status = 'success';
$message = 'Registro creado con éxito.';

if ($errors) {
    $status = 'error';
    $message = implode(' ', $errors);
} else {
    try {
        $pdo = new PDO(
            "mysql:host={$dbHost};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // Crear DB y tabla si no existen
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario VARCHAR(50) NOT NULL UNIQUE,
                contrasena VARCHAR(255) NOT NULL,
                situacion_empresarial ENUM('RI', 'PyME') NOT NULL,
                finanzas TEXT,
                balances TEXT,
                indices_propios TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $stmt = $pdo->prepare("
            INSERT INTO Usuarios (usuario, contrasena, situacion_empresarial, finanzas, balances, indices_propios)
            VALUES (:usuario, :contrasena, :situacion, :finanzas, :balances, :indices)
        ");

        $stmt->execute([
            ':usuario'    => $usuario,
            ':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
            ':situacion'  => $situacion,
            ':finanzas'   => $finanzas,
            ':balances'   => $balances,
            ':indices'    => $indices,
        ]);
    } catch (PDOException $e) {
        $status = 'error';

        // Error típico si el usuario ya existe (duplicate entry)
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            $message = 'Ese usuario ya existe. Probá con otro.';
        } else {
            $message = 'No se pudo registrar al usuario. Verifica la conexión a la base de datos.';
        }
    } catch (Throwable $e) {
        $status = 'error';
        $message = 'Ocurrió un error inesperado.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro | C.E.yD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #1A237E;
      --secondary: #F5F7FA;
      --accent: #4F8FF9;
      --card-bg: #fff;
      --text-dark: #222B45;
      --border-radius: 18px;
      --shadow: 0 4px 24px rgba(26,35,126,0.07);
    }
    body {
      font-family: 'Inter', Arial, sans-serif;
      background: var(--secondary);
      color: var(--text-dark);
      margin: 0;
      padding: 2.5rem 1rem;
    }
    .card-feedback {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      max-width: 560px;
      margin: 0 auto;
      padding: 2rem;
      text-align: center;
    }
    h1 { color: var(--primary); font-weight: 700; margin-bottom: 1rem; }
    p { color: #4F5D75; }
    .btn-primary {
      background: var(--accent);
      border: none;
      border-radius: 999px;
      font-weight: 700;
      padding: 0.75rem 1.5rem;
      margin-top: 1rem;
    }
    .btn-primary:hover { background: var(--primary); }
  </style>
</head>
<body>
  <div class="card-feedback">
    <h1><?php echo $status === 'success' ? 'Registro exitoso' : 'Ocurrió un problema'; ?></h1>
    <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="d-flex justify-content-center gap-2">
      <a class="btn btn-primary" href="register.html">Volver al registro</a>
      <a class="btn btn-outline-primary" href="index.html">Volver al inicio</a>
    </div>
  </div>
</body>
</html>
