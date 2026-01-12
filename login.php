<?php
// login.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: index.php?error=1');
    exit;
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare("
        SELECT id, nombre, apellido1, apellido2, correo, rol, password_hash, activo
        FROM trabajadores
        WHERE correo = :correo
        LIMIT 1
    ");
    $stmt->execute([':correo' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: index.php?error=1');
        exit;
    }

    if ((int)$user['activo'] !== 1) {
        header('Location: index.php?error=2');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        header('Location: index.php?error=1');
        exit;
    }

    // Login OK: guardamos sesión (sin el hash)
    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    header('Location: dashboard.php');
    exit;

} catch (Throwable $e) {
    // En un proyecto real, loguearías el error. Aquí redirigimos.
    header('Location: index.php?error=1');
    exit;
}
