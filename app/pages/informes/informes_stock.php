<?php
// informes_stock.php (HUB)
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
    <title>Informes · Stock - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Informes · Stock</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:950px; text-align:left;">
    <h2>¿Qué quieres ver?</h2>

    <div class="dashboard-grid" style="margin-top:15px;">
        <a href="informes_stock_ver.php" class="card">📦 Ver stock</a>
        <a href="informes_stock_operaciones.php" class="card">🧾 Ver operaciones</a>
    </div>

    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="link-btn" href="informes.php">⬅ Volver</a>
        <a class="link-btn" href="../facturas/facturas.php">Ir a facturas</a>
    </div>
</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes · Stock</p>
</footer>

</body>
</html>

