<?php
// stock.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

// ✅ SOLO Admin y Logística pueden acceder a Stock
$rolesPuedenVer = ['admin', 'Logistica'];
if (!in_array($rol, $rolesPuedenVer, true)) {
    header('Location: dashboard.php');
    exit;
}

// ✅ Solo Admin y Logística pueden operar (en este caso coincide con los que entran)
$puedeOperar = true;

$pdo = getPDO();

$ok  = $_GET['ok'] ?? '';
$err = $_GET['err'] ?? '';

// ===============================
// PROCESAR MOVIMIENTO DE STOCK
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeOperar) {
    $materialId = (int)($_POST['material_id'] ?? 0);
    $tipo       = trim($_POST['tipo'] ?? '');
    $cantidad   = (int)($_POST['cantidad'] ?? 0);
    $nota       = trim($_POST['nota'] ?? '');

    if ($materialId <= 0 || $cantidad <= 0) {
        header('Location: stock.php?err=' . urlencode('Material y cantidad válidos obligatorios.'));
        exit;
    }

    if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
        header('Location: stock.php?err=' . urlencode('Tipo de movimiento no válido.'));
        exit;
    }

    $st = $pdo->prepare("SELECT id, unidades FROM materiales WHERE id = :id");
    $st->execute([':id' => $materialId]);
    $mat = $st->fetch();

    if (!$mat) {
        header('Location: stock.php?err=' . urlencode('Material no encontrado.'));
        exit;
    }

    $actual = (int)$mat['unidades'];
    $delta  = ($tipo === 'entrada' || $tipo === 'ajuste') ? $cantidad : -$cantidad;

    if (($actual + $delta) < 0) {
        header('Location: stock.php?err=' . urlencode('Stock insuficiente.'));
        exit;
    }

    try {
        $pdo->beginTransaction();

        $up = $pdo->prepare("UPDATE materiales SET unidades = unidades + :d WHERE id = :id");
        $up->execute([':d' => $delta, ':id' => $materialId]);

        // Registrar historial
        $ins = $pdo->prepare("
            INSERT INTO stock_movimientos (material_id, trabajador_id, tipo, cantidad, nota)
            VALUES (:mid, :tid, :tipo, :cant, :nota)
        ");
        $ins->execute([
            ':mid'  => $materialId,
            ':tid'  => (int)$user['id'],
            ':tipo' => $tipo,
            ':cant' => $cantidad,
            ':nota' => $nota
        ]);

        $pdo->commit();
        header('Location: stock.php?ok=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header('Location: stock.php?err=' . urlencode('Error: ' . $e->getMessage()));
        exit;
    }
}

// ===============================
// STOCK ACTUAL
// ===============================
$materiales = $pdo->query("
    SELECT id, nombre, unidades, precio_unitario
    FROM materiales
    ORDER BY nombre
")->fetchAll();

// ===============================
// HISTORIAL (SIN FILTROS)
// ===============================
$sqlHist = "
    SELECT sm.creado_en, sm.tipo, sm.cantidad, sm.nota,
           m.nombre AS material,
           t.correo AS usuario
    FROM stock_movimientos sm
    JOIN materiales m ON m.id = sm.material_id
    JOIN trabajadores t ON t.id = sm.trabajador_id
    ORDER BY sm.creado_en DESC
    LIMIT 100
";

$stHist = $pdo->prepare($sqlHist);
$stHist->execute();
$historial = $stHist->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Stock - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Gestionar Stock</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:1200px; text-align:left;">

<?php if ($ok): ?><p class="success">Movimiento aplicado ✅</p><?php endif; ?>
<?php if ($err): ?><div class="error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<h2>Stock actual</h2>

<div style="overflow:auto;">
<table class="tabla">
<thead>
<tr>
    <th>Material</th>
    <th>Unidades</th>
    <th>Precio</th>
    <?php if ($puedeOperar): ?>
        <th>Tipo</th><th>Cantidad</th><th>Nota</th><th></th>
    <?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($materiales as $m): ?>
<tr>
<td><?= htmlspecialchars($m['nombre']) ?></td>
<td><?= (int)$m['unidades'] ?></td>
<td><?= number_format((float)$m['precio_unitario'],2) ?> €</td>

<?php if ($puedeOperar): ?>
<form method="POST">
<td>
    <input type="hidden" name="material_id" value="<?= (int)$m['id'] ?>">
    <select name="tipo">
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
        <option value="ajuste">Ajuste</option>
    </select>
</td>
<td><input type="number" name="cantidad" min="1" required style="width:90px;"></td>
<td><input type="text" name="nota" placeholder="Motivo"></td>
<td><button>Aplicar</button></td>
</form>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<hr style="margin:30px 0;">

<h2>Historial de movimientos (últimos 100)</h2>

<div style="overflow:auto; margin-top:15px;">
<table class="tabla">
<thead>
<tr>
    <th>Fecha</th>
    <th>Material</th>
    <th>Tipo</th>
    <th>Cantidad</th>
    <th>Usuario</th>
    <th>Nota</th>
</tr>
</thead>
<tbody>
<?php foreach ($historial as $h): ?>
<tr>
<td><?= htmlspecialchars($h['creado_en']) ?></td>
<td><?= htmlspecialchars($h['material']) ?></td>
<td><?= htmlspecialchars($h['tipo']) ?></td>
<td><?= (int)$h['cantidad'] ?></td>
<td><?= htmlspecialchars($h['usuario']) ?></td>
<td><?= htmlspecialchars($h['nota']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<a class="link-btn" href="../../../dashboard.php" style="margin-top:15px;">⬅ Volver</a>

</section>
</main>

<footer class="footer">
<p>© 2026 EcoHome · Stock</p>
</footer>

</body>
</html>


