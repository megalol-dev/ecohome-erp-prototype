<?php
// informes_stock_ver.php (filtro en acordeón, cerrado por defecto)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

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

$pdo = getPDO();

/**
 * Filtros (GET)
 */
$q     = trim($_GET['q'] ?? '');
$minU  = trim($_GET['minU'] ?? '');
$maxU  = trim($_GET['maxU'] ?? '');

// Abrir filtro solo si hay filtros activos
$openFiltro = ($q . $minU . $maxU) !== '';

$params = [];
$where  = [];

if ($q !== '') {
    $where[] = "(m.nombre LIKE :q OR CAST(m.id AS TEXT) LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if ($minU !== '' && is_numeric($minU)) {
    $where[] = "m.unidades >= :minU";
    $params[':minU'] = (int)$minU;
}

if ($maxU !== '' && is_numeric($maxU)) {
    $where[] = "m.unidades <= :maxU";
    $params[':maxU'] = (int)$maxU;
}

$sql = "
    SELECT m.id, m.nombre, m.unidades
    FROM materiales m
    " . ($where ? ("WHERE " . implode(" AND ", $where)) : "") . "
    ORDER BY m.nombre ASC
";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes · Ver stock - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Informes · Ver stock</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:1100px; text-align:left;">

    <!-- ========================= -->
    <!-- Acordeón: Filtros -->
    <!-- ========================= -->
    <details class="acordeon" <?= $openFiltro ? 'open' : '' ?>>
        <summary class="acordeon__summary">
            <span>🔎 Filtros (Stock)</span>
            <span class="badge">Abrir</span>
        </summary>

        <div class="acordeon__content">
            <form method="GET" class="form-grid" style="max-width:900px;">
                <label>Buscar (nombre o ID)</label>
                <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ej: ladrillos / 3">

                <label>Unidades mínimas</label>
                <input name="minU" type="number" step="1" min="0" value="<?= htmlspecialchars($minU) ?>">

                <label>Unidades máximas</label>
                <input name="maxU" type="number" step="1" min="0" value="<?= htmlspecialchars($maxU) ?>">

                <button type="submit">Aplicar filtro</button>
                <a class="link-btn" href="informes_stock_ver.php" style="text-align:center;">Limpiar</a>
            </form>
        </div>
    </details>

    <hr style="margin:22px 0;">

    <!-- ========================= -->
    <!-- Tabla Stock -->
    <!-- ========================= -->
    <h2>Stock actual</h2>

    <?php if (!$rows): ?>
        <p>No hay resultados.</p>
    <?php else: ?>
        <div style="overflow:auto;">
            <table class="tabla">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Material</th>
                    <th>Unidades</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= (int)$r['unidades'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="link-btn" href="informes_stock.php">⬅ Volver</a>
        <a class="link-btn" href="informes_stock_operaciones.php">Ver operaciones</a>
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes · Stock</p>
</footer>

</body>
</html>

