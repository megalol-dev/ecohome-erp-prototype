<?php
// pedido_ver.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

$rolesPuedenVer = ['admin', 'RRHH', 'Directivos', 'Logistica', 'Gestion'];
if (!in_array($rol, $rolesPuedenVer, true)) {
    header('Location: ../../../dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: pedidos.php');
    exit;
}

$back = $_GET['back'] ?? 'pedidos';
$backUrl = match ($back) {
    // volver a FACTURAS (carpeta hermana)
    'facturas'                    => '../facturas/facturas.php',
    'facturas_pagos'              => '../facturas/facturas_pagos.php',
    'facturas_crear'              => '../facturas/facturas_crear.php',
    'facturas_pagadas'            => '../facturas/facturas_pagadas.php',

    // volver a INFORMES (carpeta hermana)
    'informes'                    => '../informes/informes.php',
    'informes_facturas'           => '../informes/informes_facturas.php',
    'informes_facturas_empresa'   => '../informes/informes_facturas_empresa.php',

    // volver a PEDIDOS (misma carpeta)
    default                       => 'pedidos.php',
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

$pdo = getPDO();

$pedido = $pdo->prepare("
    SELECT p.id, p.creado_en, p.total, t.correo, t.rol
    FROM pedidos p
    JOIN trabajadores t ON t.id = p.trabajador_id
    WHERE p.id = :id
");
$pedido->execute([':id' => $id]);
$p = $pedido->fetch();

if (!$p) {
    header('Location: ' . $backUrl . '?err=' . urlencode('Pedido no encontrado.'));
    exit;
}

$items = $pdo->prepare("
    SELECT i.cantidad, i.precio_unitario, i.subtotal, m.nombre
    FROM pedido_items i
    JOIN materiales m ON m.id = i.material_id
    WHERE i.pedido_id = :pid
    ORDER BY m.nombre
");
$items->execute([':pid' => $id]);
$lineas = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #<?= (int)$p['id'] ?> - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Detalle del pedido #<?= (int)$p['id'] ?></h1>
    <p>Fecha: <strong><?= htmlspecialchars($p['creado_en']) ?></strong> · Hecho por: <strong><?= htmlspecialchars($p['correo']) ?></strong></p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width: 900px; text-align:left;">
        <h2>Líneas del pedido</h2>

        <div style="overflow:auto;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lineas as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['nombre']) ?></td>
                        <td><?= (int)$l['cantidad'] ?></td>
                        <td><?= number_format((float)$l['precio_unitario'], 2) ?> €</td>
                        <td><?= number_format((float)$l['subtotal'], 2) ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-top:15px;">Total: <?= number_format((float)$p['total'], 2) ?> €</h3>

        <div style="margin-top:15px;">
            <a class="link-btn" href="<?= htmlspecialchars($backUrlFinal) ?>">⬅ Volver</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Detalle de pedido</p>
</footer>

</body>
</html>






