<?php
// informes_facturas_resumen.php
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

function getResumen(PDO $pdo): array {
    $r = [];

    $r['empresa_pend_num'] = (int)$pdo->query("SELECT COUNT(*) FROM facturas WHERE estado='pendiente'")->fetchColumn();
    $r['empresa_pag_num']  = (int)$pdo->query("SELECT COUNT(*) FROM facturas WHERE estado='pagada'")->fetchColumn();
    $r['empresa_pend_sum'] = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado='pendiente'")->fetchColumn();
    $r['empresa_pag_sum']  = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado='pagada'")->fetchColumn();

    $r['cli_pend_num'] = (int)$pdo->query("SELECT COUNT(*) FROM facturas_clientes WHERE estado='pendiente'")->fetchColumn();
    $r['cli_pag_num']  = (int)$pdo->query("SELECT COUNT(*) FROM facturas_clientes WHERE estado='pagada'")->fetchColumn();
    $r['cli_pend_sum'] = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas_clientes WHERE estado='pendiente'")->fetchColumn();
    $r['cli_pag_sum']  = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas_clientes WHERE estado='pagada'")->fetchColumn();

    return $r;
}

$sum = getResumen($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes · Resumen facturas - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Informes · Resumen de facturas</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:900px; text-align:left;">

    <h2>Resumen total</h2>

    <div style="overflow:auto;">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Nº</th>
                    <th>Total (€)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Empresa · Pendientes</td>
                    <td><?= $sum['empresa_pend_num'] ?></td>
                    <td><?= number_format($sum['empresa_pend_sum'], 2) ?> €</td>
                </tr>
                <tr>
                    <td>Empresa · Pagadas</td>
                    <td><?= $sum['empresa_pag_num'] ?></td>
                    <td><?= number_format($sum['empresa_pag_sum'], 2) ?> €</td>
                </tr>
                <tr>
                    <td>Clientes · Pendientes</td>
                    <td><?= $sum['cli_pend_num'] ?></td>
                    <td><?= number_format($sum['cli_pend_sum'], 2) ?> €</td>
                </tr>
                <tr>
                    <td>Clientes · Pagadas</td>
                    <td><?= $sum['cli_pag_num'] ?></td>
                    <td><?= number_format($sum['cli_pag_sum'], 2) ?> €</td>
                </tr>
                <tr>
                    <td><strong>TOTAL PENDIENTE</strong></td>
                    <td><strong><?= $sum['empresa_pend_num'] + $sum['cli_pend_num'] ?></strong></td>
                    <td><strong><?= number_format($sum['empresa_pend_sum'] + $sum['cli_pend_sum'], 2) ?> €</strong></td>
                </tr>
                <tr>
                    <td><strong>TOTAL PAGADO</strong></td>
                    <td><strong><?= $sum['empresa_pag_num'] + $sum['cli_pag_num'] ?></strong></td>
                    <td><strong><?= number_format($sum['empresa_pag_sum'] + $sum['cli_pag_sum'], 2) ?> €</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="link-btn" href="informes_facturas.php">⬅ Volver</a>
        <a class="link-btn" href="informes_facturas_empresa.php">Ir a empresa</a>
        <a class="link-btn" href="informes_facturas_clientes.php">Ir a clientes</a>
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes · Resumen facturas</p>
</footer>

</body>
</html>
