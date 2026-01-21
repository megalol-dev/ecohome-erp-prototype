<?php
// factura_cliente_pagar.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

if (!in_array($rol, ['admin', 'Gestion'], true)) {
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: facturas_pagos.php?err=' . urlencode('ID de factura no válido.'));
    exit;
}

$back  = trim($_GET['back'] ?? 'facturas_pagos');
$open  = trim($_GET['open'] ?? '');
$focus = trim($_GET['focus'] ?? '');

$backUrl = match ($back) {
    'facturas_pagos'   => 'facturas_pagos.php',
    'facturas_pagadas' => 'facturas_pagadas.php',
    'facturas'         => 'facturas.php',
    default            => 'facturas_pagos.php',
};

$pdo = getPDO();

try {
    $st = $pdo->prepare("UPDATE facturas_clientes SET estado = 'pagada' WHERE id = :id");
    $st->execute([':id' => $id]);

    $qs = 'ok=cliente_pagada';
    if ($open !== '')  $qs .= '&open=' . urlencode($open);
    if ($focus !== '') $qs .= '&focus=' . urlencode($focus);

    header('Location: ' . $backUrl . '?' . $qs);
    exit;

} catch (Throwable $e) {
    $qs = 'err=' . urlencode('Error al marcar como pagada: ' . $e->getMessage());
    if ($open !== '')  $qs .= '&open=' . urlencode($open);
    if ($focus !== '') $qs .= '&focus=' . urlencode($focus);

    header('Location: ' . $backUrl . '?' . $qs);
    exit;
}

