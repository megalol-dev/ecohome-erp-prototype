<?php
// usuarios_delete.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$rolSesion = $_SESSION['user']['rol'] ?? '';
if (!in_array($rolSesion, ['admin', 'RRHH'], true)) {
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

// (Opcional) evitar que borren su propio usuario
if (!empty($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $id) {
    header('Location: usuarios.php');
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("DELETE FROM trabajadores WHERE id = :id");
$stmt->execute([':id' => $id]);

header('Location: usuarios.php');
exit;
