<?php
// dashboard.php
declare(strict_types=1);
session_start();

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EcoHome</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Panel de Gestión - EcoHome</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
    <section class="dashboard-box">

        <h2>Accesos disponibles</h2>

        <div class="dashboard-grid">

            <!-- Logística: solo pedir material + stock -->
            <?php if (in_array($rol, ['admin', 'Logistica'], true)): ?>
                <a href="app/pages/pedidos/pedidos.php" class="card">📦 Pedir material</a>
                <a href="app/pages/stock/stock.php" class="card">🏷️ Gestionar Stock</a>
            <?php endif; ?>

            <!-- Gestión: facturas + informes -->
            <?php if (in_array($rol, ['admin', 'Gestion'], true)): ?>
                <a href="app/pages/facturas/facturas.php" class="card">🧾 Gestionar facturas / pagos</a>
            <?php endif; ?>

            <!-- Informes: admin + directivos + gestión -->
            <?php if (in_array($rol, ['admin', 'Directivos', 'Gestion'], true)): ?>
                <a href="app/pages/informes/informes.php" class="card">📊 Ver Informes</a>
            <?php endif; ?>

            <!-- RRHH: solo usuarios -->
            <?php if (in_array($rol, ['admin', 'RRHH'], true)): ?>
                <a href="app/pages/usuarios/usuarios.php" class="card admin">👤 Crear/ Ver Usuarios</a>
            <?php endif; ?>

        </div>

        <a href="logout.php" class="link-btn logout-btn">Cerrar sesión</a>

    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Plataforma corporativa interna</p>
</footer>

</body>
</html>

