<?php
// informes_facturas_clientes.php (acordeón + filtros internos + recordar acordeón/posición al volver)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

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

// ✅ NUEVO: recordar qué acordeón abrir y a qué fila volver
$openFromReturn  = trim($_GET['open'] ?? '');   // 'pend' | 'pag'
$focusFromReturn = trim($_GET['focus'] ?? '');  // id del <tr>

/* =========================
   Filtros PENDIENTES (GET)
   ========================= */
$pend_id      = trim($_GET['pend_id'] ?? '');
$pend_cliente = trim($_GET['pend_cliente'] ?? '');
$pend_concept = trim($_GET['pend_concept'] ?? '');
$pend_correo  = trim($_GET['pend_correo'] ?? '');
$pend_desde   = trim($_GET['pend_desde'] ?? '');
$pend_hasta   = trim($_GET['pend_hasta'] ?? '');
$pend_min     = trim($_GET['pend_min'] ?? '');
$pend_max     = trim($_GET['pend_max'] ?? '');

/* =========================
   Filtros PAGADAS (GET)
   ========================= */
$pag_id      = trim($_GET['pag_id'] ?? '');
$pag_cliente = trim($_GET['pag_cliente'] ?? '');
$pag_concept = trim($_GET['pag_concept'] ?? '');
$pag_correo  = trim($_GET['pag_correo'] ?? '');
$pag_desde   = trim($_GET['pag_desde'] ?? '');
$pag_hasta   = trim($_GET['pag_hasta'] ?? '');
$pag_min     = trim($_GET['pag_min'] ?? '');
$pag_max     = trim($_GET['pag_max'] ?? '');

/* =========================
   Helpers
   ========================= */
function buildClientesWhere(array &$params, string $prefix): string {
    $id      = trim($_GET[$prefix . 'id'] ?? '');
    $cliente = trim($_GET[$prefix . 'cliente'] ?? '');
    $concept = trim($_GET[$prefix . 'concept'] ?? '');
    $correo  = trim($_GET[$prefix . 'correo'] ?? '');
    $desde   = trim($_GET[$prefix . 'desde'] ?? '');
    $hasta   = trim($_GET[$prefix . 'hasta'] ?? '');
    $minT    = trim($_GET[$prefix . 'min'] ?? '');
    $maxT    = trim($_GET[$prefix . 'max'] ?? '');

    $w = [];

    if ($id !== '' && ctype_digit($id)) {
        $w[] = "fc.id = :{$prefix}id";
        $params[":{$prefix}id"] = (int)$id;
    }
    if ($cliente !== '') {
        $w[] = "(fc.cliente_nombre LIKE :{$prefix}cliente OR fc.cliente_email LIKE :{$prefix}cliente)";
        $params[":{$prefix}cliente"] = '%' . $cliente . '%';
    }
    if ($concept !== '') {
        $w[] = "fc.concepto LIKE :{$prefix}concept";
        $params[":{$prefix}concept"] = '%' . $concept . '%';
    }
    if ($correo !== '') {
        $w[] = "t.correo LIKE :{$prefix}correo";
        $params[":{$prefix}correo"] = '%' . $correo . '%';
    }
    if ($desde !== '') {
        $w[] = "fc.creado_en >= :{$prefix}desde";
        $params[":{$prefix}desde"] = $desde . " 00:00:00";
    }
    if ($hasta !== '') {
        $w[] = "fc.creado_en <= :{$prefix}hasta";
        $params[":{$prefix}hasta"] = $hasta . " 23:59:59";
    }
    if ($minT !== '' && is_numeric($minT)) {
        $w[] = "fc.total >= :{$prefix}min";
        $params[":{$prefix}min"] = (float)$minT;
    }
    if ($maxT !== '' && is_numeric($maxT)) {
        $w[] = "fc.total <= :{$prefix}max";
        $params[":{$prefix}max"] = (float)$maxT;
    }

    return $w ? (' AND ' . implode(' AND ', $w)) : '';
}

function getClientes(PDO $pdo, string $estado, string $prefix): array {
    $params = [':estado' => $estado];
    $extra  = buildClientesWhere($params, $prefix);

    $sql = "
        SELECT fc.id, fc.creado_en, fc.total, fc.estado,
               fc.cliente_nombre, fc.cliente_email, fc.concepto,
               fc.base_imponible, fc.iva_porcentaje,
               fc.archivo_path,
               t.correo AS creador_correo, t.rol AS creador_rol
        FROM facturas_clientes fc
        JOIN trabajadores t ON t.id = fc.trabajador_id
        WHERE fc.estado = :estado
        $extra
        ORDER BY fc.id DESC
    ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

$pend = getClientes($pdo, 'pendiente', 'pend_');
$pag  = getClientes($pdo, 'pagada', 'pag_');

$countPend = count($pend);
$countPag  = count($pag);

// ✅ Solo abre el acordeón del FILTRO si hay filtros reales (no por volver)
$openPendFiltro = ($pend_id.$pend_cliente.$pend_concept.$pend_correo.$pend_desde.$pend_hasta.$pend_min.$pend_max) !== '';
$openPagFiltro  = ($pag_id.$pag_cliente.$pag_concept.$pag_correo.$pag_desde.$pag_hasta.$pag_min.$pag_max) !== '';

// ✅ El acordeón grande se abre si hay filtros O si vuelves con open=...
$openPendAcordeon = $openPendFiltro;
$openPagAcordeon  = $openPagFiltro;

if ($openFromReturn === 'pend') $openPendAcordeon = true;
if ($openFromReturn === 'pag')  $openPagAcordeon  = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes · Facturas a clientes - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Informes · Facturas a clientes</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:1200px; text-align:left;">

    <!-- Pendientes -->
    <details class="acordeon" <?= $openPendAcordeon ? 'open' : '' ?>>
        <summary class="acordeon__summary">
            <span>Pendientes</span>
            <span class="badge">Total: <?= (int)$countPend ?></span>
        </summary>

        <div class="acordeon__content">

            <!-- Filtros internos (solo se abre si hay filtros reales) -->
            <details class="acordeon" style="margin:0 0 18px 0;" <?= $openPendFiltro ? 'open' : '' ?>>
                <summary class="acordeon__summary">
                    <span>🔎 Filtros (Pendientes)</span>
                    <span class="badge">Abrir</span>
                </summary>
                <div class="acordeon__content">

                    <form method="GET" class="form-grid" style="max-width:950px;">
                        <label>ID factura</label>
                        <input name="pend_id" value="<?= htmlspecialchars($pend_id) ?>" placeholder="Ej: 7">

                        <label>Cliente (nombre o email)</label>
                        <input name="pend_cliente" value="<?= htmlspecialchars($pend_cliente) ?>" placeholder="Rubén / gmail.com">

                        <label>Concepto</label>
                        <input name="pend_concept" value="<?= htmlspecialchars($pend_concept) ?>" placeholder="reforma / proyecto / ...">

                        <label>Correo creador</label>
                        <input name="pend_correo" value="<?= htmlspecialchars($pend_correo) ?>" placeholder="admin@ecohome.com">

                        <label>Fecha desde</label>
                        <input type="date" name="pend_desde" value="<?= htmlspecialchars($pend_desde) ?>">

                        <label>Fecha hasta</label>
                        <input type="date" name="pend_hasta" value="<?= htmlspecialchars($pend_hasta) ?>">

                        <label>Total mínimo (€)</label>
                        <input type="number" step="0.01" min="0" name="pend_min" value="<?= htmlspecialchars($pend_min) ?>">

                        <label>Total máximo (€)</label>
                        <input type="number" step="0.01" min="0" name="pend_max" value="<?= htmlspecialchars($pend_max) ?>">

                        <button type="submit">Aplicar filtro</button>
                        <a class="link-btn" href="informes_facturas_clientes.php" style="text-align:center;">Limpiar</a>
                    </form>

                </div>
            </details>

            <?php if (!$pend): ?>
                <p>No hay resultados.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table class="tabla">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Email cliente</th>
                            <th>Concepto</th>
                            <th>Creada por</th>
                            <th>Total</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pend as $fc): ?>
                            <tr id="pend_<?= (int)$fc['id'] ?>">
                                <td><?= (int)$fc['id'] ?></td>
                                <td><?= htmlspecialchars($fc['creado_en']) ?></td>
                                <td><?= htmlspecialchars($fc['cliente_nombre']) ?></td>
                                <td><?= htmlspecialchars($fc['cliente_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($fc['concepto']) ?></td>
                                <td><?= htmlspecialchars($fc['creador_correo']) ?> (<?= htmlspecialchars($fc['creador_rol']) ?>)</td>
                                <td><?= number_format((float)$fc['total'], 2) ?> €</td>
                                <td>
                                    <?php if (!empty($fc['archivo_path'])): ?>
                                        <a href="../facturas/ver_factura_cliente.php?id=<?= (int)$fc['id'] ?>&back=informes_facturas_clientes&open=pend&focus=pend_<?= (int)$fc['id'] ?>">
                                        Ver imagen</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </details>

    <!-- Pagadas -->
    <details class="acordeon" <?= $openPagAcordeon ? 'open' : '' ?>>
        <summary class="acordeon__summary">
            <span>Pagadas</span>
            <span class="badge">Total: <?= (int)$countPag ?></span>
        </summary>

        <div class="acordeon__content">

            <!-- Filtros internos (solo se abre si hay filtros reales) -->
            <details class="acordeon" style="margin:0 0 18px 0;" <?= $openPagFiltro ? 'open' : '' ?>>
                <summary class="acordeon__summary">
                    <span>🔎 Filtros (Pagadas)</span>
                    <span class="badge">Abrir</span>
                </summary>
                <div class="acordeon__content">

                    <form method="GET" class="form-grid" style="max-width:950px;">
                        <label>ID factura</label>
                        <input name="pag_id" value="<?= htmlspecialchars($pag_id) ?>" placeholder="Ej: 12">

                        <label>Cliente (nombre o email)</label>
                        <input name="pag_cliente" value="<?= htmlspecialchars($pag_cliente) ?>" placeholder="Alex / hotmail.com">

                        <label>Concepto</label>
                        <input name="pag_concept" value="<?= htmlspecialchars($pag_concept) ?>" placeholder="obra / servicio / ...">

                        <label>Correo creador</label>
                        <input name="pag_correo" value="<?= htmlspecialchars($pag_correo) ?>" placeholder="admin@ecohome.com">

                        <label>Fecha desde</label>
                        <input type="date" name="pag_desde" value="<?= htmlspecialchars($pag_desde) ?>">

                        <label>Fecha hasta</label>
                        <input type="date" name="pag_hasta" value="<?= htmlspecialchars($pag_hasta) ?>">

                        <label>Total mínimo (€)</label>
                        <input type="number" step="0.01" min="0" name="pag_min" value="<?= htmlspecialchars($pag_min) ?>">

                        <label>Total máximo (€)</label>
                        <input type="number" step="0.01" min="0" name="pag_max" value="<?= htmlspecialchars($pag_max) ?>">

                        <button type="submit">Aplicar filtro</button>
                        <a class="link-btn" href="informes_facturas_clientes.php" style="text-align:center;">Limpiar</a>
                    </form>

                </div>
            </details>

            <?php if (!$pag): ?>
                <p>No hay resultados.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table class="tabla">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Email cliente</th>
                            <th>Concepto</th>
                            <th>Creada por</th>
                            <th>Total</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pag as $fc): ?>
                            <tr id="pag_<?= (int)$fc['id'] ?>">
                                <td><?= (int)$fc['id'] ?></td>
                                <td><?= htmlspecialchars($fc['creado_en']) ?></td>
                                <td><?= htmlspecialchars($fc['cliente_nombre']) ?></td>
                                <td><?= htmlspecialchars($fc['cliente_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($fc['concepto']) ?></td>
                                <td><?= htmlspecialchars($fc['creador_correo']) ?> (<?= htmlspecialchars($fc['creador_rol']) ?>)</td>
                                <td><?= number_format((float)$fc['total'], 2) ?> €</td>
                                <td>
                                    <?php if (!empty($fc['archivo_path'])): ?>
                                        <a href="../facturas/ver_factura_cliente.php?id=<?= (int)$fc['id'] ?>&back=informes_facturas_clientes&open=pag&focus=pag_<?= (int)$fc['id'] ?>">
                                        Ver imagen</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </details>

    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="link-btn" href="informes_facturas.php">⬅ Volver</a>
        <a class="link-btn" href="informes_facturas_empresa.php">Ir a empresa</a>
        <a class="link-btn" href="informes_facturas_resumen.php">Ver resumen</a>
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Informes · Clientes</p>
</footer>

<!-- ✅ Scroll a la fila al volver -->
<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const focus = params.get('focus');
    if (!focus) return;

    const el = document.getElementById(focus);
    if (!el) return;

    setTimeout(() => {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 60);
})();
</script>

</body>
</html>



