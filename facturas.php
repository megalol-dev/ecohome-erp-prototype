<?php
// facturas.php (menú de gestión de facturas / pagos)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

$rolesPuedenEntrar = ['admin', 'RRHH', 'Gestion'];
if (!in_array($rol, $rolesPuedenEntrar, true)) {
    header('Location: dashboard.php');
    exit;
}

$puedeOperar = in_array($rol, ['admin', 'Gestion'], true);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar facturas / pagos - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Gestionar facturas / pagos</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:1000px; text-align:left;">

    <h2>Accesos</h2>
    <p style="margin-top:0;">
        Desde aquí gestionamos el trabajo del día a día: crear facturas, marcar pagos y consultar pagadas.
    </p>

    <div class="dashboard-grid" style="margin-top:15px;">
        <a href="facturas_crear.php" class="card">🧾 Crear factura</a>
        <a href="facturas_pagos.php" class="card">📁 Facturas sin pagar</a>
        <a href="facturas_pagadas.php" class="card">📁 Facturas pagadas</a>
    </div>

    <?php if (!$puedeOperar): ?>
        <div class="error" style="margin-top:18px;">
            Puedes ver esta sección, pero <strong>solo Admin y Gestión</strong> pueden crear facturas o gestionar pagos.
        </div>
    <?php endif; ?>

    <div style="margin-top:15px;">
        <a class="link-btn" href="dashboard.php">⬅ Volver</a>
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Gestión de facturas</p>
</footer>

</body>
</html>



