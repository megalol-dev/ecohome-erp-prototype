<?php
// informes_facturas.php (HUB Facturas)
declare(strict_types=1);
session_start();

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

$rolesPuedenEntrar = ['admin', 'RRHH', 'Directivos', 'Gestion'];
if (!in_array($rol, $rolesPuedenEntrar, true)) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes · Facturas - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Informes · Facturas</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width:950px; text-align:left;">
        <h2>Elige un informe</h2>

        <div class="dashboard-grid" style="margin-top:15px;">
            <a href="informes_facturas_empresa.php" class="card">🏭 Facturas de empresa</a>
            <a href="informes_facturas_clientes.php" class="card">👤 Facturas a clientes</a>
            <a href="informes_facturas_resumen.php" class="card">📌 Resumen total</a>
        </div>

        <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="link-btn" href="informes.php">⬅ Volver</a>
            <a class="link-btn" href="informes_stock.php">Ir a Stock</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes · Facturas</p>
</footer>

</body>
</html>

