<?php
// informes.php (HUB)
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
    <title>Informes - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Informes</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width:900px; text-align:left;">
        <h2>¿Qué quieres consultar?</h2>

        <div class="dashboard-grid" style="margin-top:15px;">
            <a href="informes_facturas.php" class="card">🧾 Facturas</a>
            <a href="informes_stock.php" class="card">🏷️ Stock</a>
        </div>

        <div style="margin-top:15px;">
            <a class="link-btn" href="../../../dashboard.php">⬅ Volver</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes</p>
</footer>

</body>
</html>

