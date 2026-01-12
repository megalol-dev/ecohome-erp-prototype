<?php
// informes_stock_operaciones.php (filtro en acordeón, cerrado por defecto)
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
 * Filtros
 */
$q       = trim($_GET['q'] ?? '');       // material / correo / id
$desde   = trim($_GET['desde'] ?? '');   // YYYY-MM-DD
$hasta   = trim($_GET['hasta'] ?? '');   // YYYY-MM-DD
$minC    = trim($_GET['minC'] ?? '');
$maxC    = trim($_GET['maxC'] ?? '');

// Acordeón del filtro: cerrado por defecto, se abre si hay filtros activos
$openFiltro = ($q . $desde . $hasta . $minC . $maxC) !== '';

$params = [];
$where  = [];

if ($q !== '') {
    $where[] = "(
        m.nombre LIKE :q
        OR t.correo LIKE :q
        OR CAST(sm.id AS TEXT) LIKE :q
    )";
    $params[':q'] = '%' . $q . '%';
}

if ($desde !== '') {
    $where[] = "sm.creado_en >= :desde";
    $params[':desde'] = $desde . " 00:00:00";
}

if ($hasta !== '') {
    $where[] = "sm.creado_en <= :hasta";
    $params[':hasta'] = $hasta . " 23:59:59";
}

if ($minC !== '' && is_numeric($minC)) {
    $where[] = "sm.cantidad >= :minC";
    $params[':minC'] = (int)$minC;
}

if ($maxC !== '' && is_numeric($maxC)) {
    $where[] = "sm.cantidad <= :maxC";
    $params[':maxC'] = (int)$maxC;
}

$sql = "
    SELECT
        sm.id,
        sm.creado_en,
        sm.cantidad,
        sm.nota,
        m.nombre AS material,
        t.correo AS creador_correo,
        t.rol    AS creador_rol
    FROM stock_movimientos sm
    JOIN materiales m ON m.id = sm.material_id
    JOIN trabajadores t ON t.id = sm.trabajador_id
    " . ($where ? ("WHERE " . implode(" AND ", $where)) : "") . "
    ORDER BY sm.id DESC
";

$st = $pdo->prepare($sql);
$st->execute($params);
$ops = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes · Operaciones de stock - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Informes · Ver operaciones de stock</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:1200px; text-align:left;">

    <!-- ========================= -->
    <!-- Acordeón: Filtros -->
    <!-- ========================= -->
    <details class="acordeon" <?= $openFiltro ? 'open' : '' ?>>
        <summary class="acordeon__summary">
            <span>🔎 Filtros (Operaciones)</span>
            <span class="badge">Abrir</span>
        </summary>

        <div class="acordeon__content">
            <form method="GET" class="form-grid" style="max-width:950px;">
                <label>Buscar (material / correo / ID)</label>
                <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ej: ladrillos / admin@ / 15">

                <label>Fecha desde</label>
                <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">

                <label>Fecha hasta</label>
                <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">

                <label>Cantidad mínima</label>
                <input type="number" step="1" name="minC" value="<?= htmlspecialchars($minC) ?>">

                <label>Cantidad máxima</label>
                <input type="number" step="1" name="maxC" value="<?= htmlspecialchars($maxC) ?>">

                <button type="submit">Aplicar filtro</button>
                <a class="link-btn" href="informes_stock_operaciones.php" style="text-align:center;">Limpiar</a>
            </form>
        </div>
    </details>

    <hr style="margin:22px 0;">

    <!-- ========================= -->
    <!-- Tabla Historial -->
    <!-- ========================= -->
    <h2>Historial</h2>

    <?php if (!$ops): ?>
        <p>No hay resultados.</p>
    <?php else: ?>
        <div style="overflow:auto;">
            <table class="tabla">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Material</th>
                    <th>Cantidad</th>
                    <th>Nota</th>
                    <th>Hecho por</th>
                    <th>Rol</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ops as $o): ?>
                    <tr>
                        <td><?= (int)$o['id'] ?></td>
                        <td><?= htmlspecialchars((string)$o['creado_en']) ?></td>
                        <td><?= htmlspecialchars((string)$o['material']) ?></td>
                        <td><?= (int)$o['cantidad'] ?></td>
                        <td><?= htmlspecialchars((string)($o['nota'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)$o['creador_correo']) ?></td>
                        <td><?= htmlspecialchars((string)$o['creador_rol']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="link-btn" href="informes_stock.php">⬅ Volver</a>
        <a class="link-btn" href="informes_stock_ver.php">Ver stock</a>
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes · Operaciones</p>
</footer>

</body>
</html>

