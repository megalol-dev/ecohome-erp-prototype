<?php
// ver_factura_cliente.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit;
}

$rol = $_SESSION['user']['rol'] ?? '';
$rolesPuedenEntrar = ['admin', 'RRHH', 'Gestion', 'Directivos'];
if (!in_array($rol, $rolesPuedenEntrar, true)) {
    header('Location: ../../../dashboard.php');
    exit;
}

$back = $_GET['back'] ?? 'facturas';

// OJO: este archivo está en app/pages/facturas/
// Así que para volver a Facturas usamos rutas dentro del mismo módulo,
// y para volver a Informes, saltamos a ../informes/
$backUrl = match ($back) {
    'facturas_pagos'              => 'facturas_pagos.php',
    'facturas_crear'              => 'facturas_crear.php',
    'facturas_pagadas'            => 'facturas_pagadas.php',
    'facturas'                    => 'facturas.php',

    'informes_facturas_clientes'  => '../informes/informes_facturas_clientes.php',
    'informes_facturas_resumen'   => '../informes/informes_facturas_resumen.php',
    'informes_facturas'           => '../informes/informes_facturas.php',
    'informes'                    => '../informes/informes.php',

    default                       => 'facturas.php',
};

// ✅ Arrastrar open/focus para volver con acordeón abierto y scroll a la fila
$open  = trim($_GET['open'] ?? '');
$focus = trim($_GET['focus'] ?? '');

$queryBack = [];
if ($open !== '')  $queryBack['open']  = $open;
if ($focus !== '') $queryBack['focus'] = $focus;

$backUrlFinal = $backUrl;
if ($queryBack) {
    $backUrlFinal .= (str_contains($backUrlFinal, '?') ? '&' : '?') . http_build_query($queryBack);
}

// Helper: añadir err sin romper la query existente
$redirectWithErr = function (string $msg) use ($backUrlFinal): void {
    $sep = str_contains($backUrlFinal, '?') ? '&' : '?';
    header('Location: ' . $backUrlFinal . $sep . 'err=' . urlencode($msg));
    exit;
};

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
    $redirectWithErr('No existe imagen para esta factura.');
}

$path = (string)$fc['archivo_path'];

// Validación básica: esperamos algo como "uploads/archivo.png"
if (!str_starts_with($path, 'uploads/')) {
    $redirectWithErr('Ruta de archivo no válida.');
}

// RUTA WEB: desde app/pages/facturas/ hasta /uploads/... hay que subir 3 niveles
$webPath  = '../../../' . ltrim($path, '/');

// RUTA DISCO: desde este archivo hasta la raíz hay 3 niveles, y luego el path (uploads/...)
$diskPath = __DIR__ . '/../../../' . ltrim($path, '/');

if (!is_file($diskPath)) {
    $redirectWithErr('La imagen no se encuentra en el servidor.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Cliente #<?= (int)$fc['id'] ?> - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
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
            <img src="<?= htmlspecialchars($webPath) ?>" alt="Factura adjunta"
                 style="max-width:100%; border:1px solid #000; border-radius:6px;">
        </div>

        <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="link-btn" href="<?= htmlspecialchars($backUrlFinal) ?>">⬅ Volver</a>
            <a class="link-btn" href="<?= htmlspecialchars($webPath) ?>" target="_blank" rel="noopener">Abrir imagen</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Visualización de factura</p>
</footer>

</body>
</html>









