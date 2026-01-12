<?php
// pedidos.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

$rolesPuedenVer = ['admin', 'RRHH', 'Directivos', 'Logistica', 'Gestion'];
if (!in_array($rol, $rolesPuedenVer, true)) {
    header('Location: dashboard.php');
    exit;
}

$puedeOperar = in_array($rol, ['admin', 'Logistica'], true);

$pdo = getPDO();

// Mensajes
$ok = $_GET['ok'] ?? '';
$err = $_GET['err'] ?? '';

// Cargar materiales activos
$materiales = $pdo->query("
    SELECT id, nombre, unidades, precio_unitario
    FROM materiales
    WHERE activo = 1
    ORDER BY nombre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Pedir material</h1>
    <p>Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> | Rol: <strong><?= htmlspecialchars($rol) ?></strong></p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width: 1000px; text-align:left;">

        <?php if ($ok === '1'): ?>
            <p class="success">Pedido creado y factura generada ✅</p>
        <?php endif; ?>

        <?php if ($err !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <h2>Materiales disponibles</h2>

        <div style="overflow:auto;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Unidades</th>
                        <th>Precio unitario</th>
                        <?php if ($puedeOperar): ?><th>Pedir (cantidad)</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($materiales as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['nombre']) ?></td>
                        <td><?= (int)$m['unidades'] ?></td>
                        <td><?= number_format((float)$m['precio_unitario'], 2) ?> €</td>
                        <?php if ($puedeOperar): ?>
                            <td>
                                <input type="number" min="0" name="cant[<?= (int)$m['id'] ?>]" value="0" form="formPedido" style="width:90px;">
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($puedeOperar): ?>
            <form id="formPedido" method="POST" action="pedidos_crear.php" style="margin-top:15px;">
                <button type="submit">Crear pedido</button>
            </form>
        <?php else: ?>
            <p style="margin-top:15px;">
                Puedes ver el almacén, pero <strong>solo Admin y Logística</strong> pueden crear pedidos.
            </p>
        <?php endif; ?>

        <hr style="margin:20px 0;">

        <h2>Pedidos recientes</h2>
        <?php
        $pedidos = $pdo->query("
        SELECT p.id, p.creado_en, p.total, t.correo, t.rol, f.estado
        FROM pedidos p
        JOIN trabajadores t ON t.id = p.trabajador_id
        JOIN facturas f ON f.pedido_id = p.id
        WHERE f.estado = 'pendiente'
        ORDER BY p.id DESC
        LIMIT 10
        ")->fetchAll();
        ?>

        <div style="overflow:auto;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Hecho por</th>
                        <th>Rol</th>
                        <th>Total</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><?= (int)$p['id'] ?></td>
                        <td><?= htmlspecialchars($p['creado_en']) ?></td>
                        <td><?= htmlspecialchars($p['correo']) ?></td>
                        <td><?= htmlspecialchars($p['rol']) ?></td>
                        <td><?= number_format((float)$p['total'], 2) ?> €</td>
                        <td><a href="pedido_ver.php?id=<?= (int)$p['id'] ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:15px;">
            <a class="link-btn" href="dashboard.php">⬅ Volver</a>
        </div>

    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Pedidos y almacén</p>
</footer>

</body>
</html>
