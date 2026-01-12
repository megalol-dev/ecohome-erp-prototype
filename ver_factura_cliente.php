<?php
// ver_factura_cliente.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$rol = $_SESSION['user']['rol'] ?? '';
$rolesPuedenEntrar = ['admin', 'RRHH', 'Gestion', 'Directivos'];
if (!in_array($rol, $rolesPuedenEntrar, true)) {
    header('Location: dashboard.php');
    exit;
}

$back = $_GET['back'] ?? 'facturas';
$backUrl = match ($back) {
    'facturas_pagos'              => 'facturas_pagos.php',
    'facturas_crear'              => 'facturas_crear.php',
    'facturas_pagadas'            => 'facturas_pagadas.php',
    'facturas'                    => 'facturas.php',

    'informes_facturas_clientes'  => 'informes_facturas_clientes.php',
    'informes_facturas_resumen'   => 'informes_facturas_resumen.php',
    'informes_facturas'           => 'informes_facturas.php',
    'informes'                    => 'informes.php',

    default                       => 'facturas.php',
};

// ✅ NUEVO: arrastrar open/focus para volver con acordeón abierto y scroll a la fila
$open  = trim($_GET['open'] ?? '');
$focus = trim($_GET['focus'] ?? '');

$queryBack = [];
if ($open !== '')  $queryBack['open']  = $open;
if ($focus !== '') $queryBack['focus'] = $focus;

$backUrlFinal = $backUrl;
if ($queryBack) {
    $backUrlFinal .= (str_contains($backUrlFinal, '?') ? '&' : '?') . http_build_query($queryBack);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $backUrlFinal);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("
    SELECT id, cliente_nombre, concepto, archivo_path, total, creado_en
    FROM facturas_clientes
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$fc = $stmt->fetch();

if (!$fc || empty($fc['archivo_path'])) {
    header('Location: ' . $backUrlFinal . '?err=' . urlencode('No existe imagen para esta factura.'));
    exit;
}

$path = (string)$fc['archivo_path'];
if (!str_starts_with($path, 'uploads/')) {
    header('Location: ' . $backUrlFinal . '?err=' . urlencode('Ruta de archivo no válida.'));
    exit;
}

$absPath = __DIR__ . '/' . $path;
if (!file_exists($absPath)) {
    header('Location: ' . $backUrlFinal . '?err=' . urlencode('La imagen no se encuentra en el servidor.'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Cliente #<?= (int)$fc['id'] ?> - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Factura Cliente #<?= (int)$fc['id'] ?></h1>
    <p>
        <strong><?= htmlspecialchars($fc['cliente_nombre']) ?></strong>
        · <?= htmlspecialchars($fc['creado_en']) ?>
        · Total: <?= number_format((float)$fc['total'], 2) ?> €
    </p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width: 900px; text-align:left;">
        <h2><?= htmlspecialchars($fc['concepto']) ?></h2>

        <div style="margin-top:15px;">
            <img src="<?= htmlspecialchars($path) ?>" alt="Factura adjunta"
                 style="max-width:100%; border:1px solid #000; border-radius:6px;">
        </div>

        <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="link-btn" href="<?= htmlspecialchars($backUrlFinal) ?>">⬅ Volver</a>
            <a class="link-btn" href="<?= htmlspecialchars($path) ?>" target="_blank" rel="noopener">Abrir imagen</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Visualización de factura</p>
</footer>

</body>
</html>








