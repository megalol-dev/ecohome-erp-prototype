<?php
// facturas_pagadas.php (empresa + clientes pagadas, con acordeones y filtros internos + recordar acordeón/posición)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

// Solo admin y Gestion
if (!in_array($rol, ['admin', 'Gestion'], true)) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getPDO();

// ✅ recordar qué acordeón y qué fila abrir al volver
$openFromReturn  = trim($_GET['open'] ?? '');   // 'empresa' | 'clientes'
$focusFromReturn = trim($_GET['focus'] ?? '');  // id del <tr>

/* =========================
   Filtros Empresa (GET)
   ========================= */
$emp_id     = trim($_GET['emp_id'] ?? '');
$emp_pedido = trim($_GET['emp_pedido'] ?? '');
$emp_correo = trim($_GET['emp_correo'] ?? '');
$emp_desde  = trim($_GET['emp_desde'] ?? '');
$emp_hasta  = trim($_GET['emp_hasta'] ?? '');
$emp_min    = trim($_GET['emp_min'] ?? '');
$emp_max    = trim($_GET['emp_max'] ?? '');

/* =========================
   Filtros Clientes (GET)
   ========================= */
$cli_id      = trim($_GET['cli_id'] ?? '');
$cli_cliente = trim($_GET['cli_cliente'] ?? '');
$cli_concept = trim($_GET['cli_concept'] ?? '');
$cli_correo  = trim($_GET['cli_correo'] ?? '');
$cli_desde   = trim($_GET['cli_desde'] ?? '');
$cli_hasta   = trim($_GET['cli_hasta'] ?? '');
$cli_min     = trim($_GET['cli_min'] ?? '');
$cli_max     = trim($_GET['cli_max'] ?? '');

/* =========================
   Helpers: construir WHERE
   ========================= */
function buildEmpWhere(array &$params): string {
    global $emp_id, $emp_pedido, $emp_correo, $emp_desde, $emp_hasta, $emp_min, $emp_max;

    $w = [];

    if ($emp_id !== '' && ctype_digit($emp_id)) {
        $w[] = "f.id = :emp_id";
        $params[':emp_id'] = (int)$emp_id;
    }
    if ($emp_pedido !== '' && ctype_digit($emp_pedido)) {
        $w[] = "f.pedido_id = :emp_pedido";
        $params[':emp_pedido'] = (int)$emp_pedido;
    }
    if ($emp_correo !== '') {
        $w[] = "t.correo LIKE :emp_correo";
        $params[':emp_correo'] = '%' . $emp_correo . '%';
    }
    if ($emp_desde !== '') {
        $w[] = "f.creado_en >= :emp_desde";
        $params[':emp_desde'] = $emp_desde . " 00:00:00";
    }
    if ($emp_hasta !== '') {
        $w[] = "f.creado_en <= :emp_hasta";
        $params[':emp_hasta'] = $emp_hasta . " 23:59:59";
    }
    if ($emp_min !== '' && is_numeric($emp_min)) {
        $w[] = "f.total >= :emp_min";
        $params[':emp_min'] = (float)$emp_min;
    }
    if ($emp_max !== '' && is_numeric($emp_max)) {
        $w[] = "f.total <= :emp_max";
        $params[':emp_max'] = (float)$emp_max;
    }

    return $w ? (' AND ' . implode(' AND ', $w)) : '';
}

function buildCliWhere(array &$params): string {
    global $cli_id, $cli_cliente, $cli_concept, $cli_correo, $cli_desde, $cli_hasta, $cli_min, $cli_max;

    $w = [];

    if ($cli_id !== '' && ctype_digit($cli_id)) {
        $w[] = "fc.id = :cli_id";
        $params[':cli_id'] = (int)$cli_id;
    }
    if ($cli_cliente !== '') {
        $w[] = "(fc.cliente_nombre LIKE :cli_cliente OR fc.cliente_email LIKE :cli_cliente)";
        $params[':cli_cliente'] = '%' . $cli_cliente . '%';
    }
    if ($cli_concept !== '') {
        $w[] = "fc.concepto LIKE :cli_concept";
        $params[':cli_concept'] = '%' . $cli_concept . '%';
    }
    if ($cli_correo !== '') {
        $w[] = "t.correo LIKE :cli_correo";
        $params[':cli_correo'] = '%' . $cli_correo . '%';
    }
    if ($cli_desde !== '') {
        $w[] = "fc.creado_en >= :cli_desde";
        $params[':cli_desde'] = $cli_desde . " 00:00:00";
    }
    if ($cli_hasta !== '') {
        $w[] = "fc.creado_en <= :cli_hasta";
        $params[':cli_hasta'] = $cli_hasta . " 23:59:59";
    }
    if ($cli_min !== '' && is_numeric($cli_min)) {
        $w[] = "fc.total >= :cli_min";
        $params[':cli_min'] = (float)$cli_min;
    }
    if ($cli_max !== '' && is_numeric($cli_max)) {
        $w[] = "fc.total <= :cli_max";
        $params[':cli_max'] = (float)$cli_max;
    }

    return $w ? (' AND ' . implode(' AND ', $w)) : '';
}

/* =========================
   Consultas (PAGADAS)
   ========================= */

// Empresa pagadas
$empParams = [];
$empExtra  = buildEmpWhere($empParams);

$sqlEmpresa = "
    SELECT f.id, f.pedido_id, f.creado_en, f.total, f.estado,
           t.correo AS creador_correo, t.rol AS creador_rol
    FROM facturas f
    JOIN trabajadores t ON t.id = f.trabajador_id
    WHERE f.estado = 'pagada'
    $empExtra
    ORDER BY f.id DESC
";
$stEmp = $pdo->prepare($sqlEmpresa);
$stEmp->execute($empParams);
$factEmpresa = $stEmp->fetchAll();

// Clientes pagadas
$cliParams = [];
$cliExtra  = buildCliWhere($cliParams);

$sqlClientes = "
    SELECT fc.id, fc.creado_en, fc.cliente_nombre, fc.cliente_email, fc.concepto,
           fc.archivo_path,
           fc.total, fc.estado,
           t.correo AS creador_correo, t.rol AS creador_rol
    FROM facturas_clientes fc
    JOIN trabajadores t ON t.id = fc.trabajador_id
    WHERE fc.estado = 'pagada'
    $cliExtra
    ORDER BY fc.id DESC
";
$stCli = $pdo->prepare($sqlClientes);
$stCli->execute($cliParams);
$factClientes = $stCli->fetchAll();

$countEmpresa  = count($factEmpresa);
$countClientes = count($factClientes);

/* =========================
   Aperturas: filtro vs acordeón
   ========================= */

// ✅ Solo si hay filtros, abrimos el acordeón del filtro
$openEmpresaFiltro  = ($emp_id.$emp_pedido.$emp_correo.$emp_desde.$emp_hasta.$emp_min.$emp_max) !== '';
$openClientesFiltro = ($cli_id.$cli_cliente.$cli_concept.$cli_correo.$cli_desde.$cli_hasta.$cli_min.$cli_max) !== '';

// ✅ El acordeón grande se abre si hay filtros o si vuelves con open=...
$openEmpresaAcordeon  = $openEmpresaFiltro;
$openClientesAcordeon = $openClientesFiltro;

if ($openFromReturn === 'empresa')  $openEmpresaAcordeon  = true;
if ($openFromReturn === 'clientes') $openClientesAcordeon = true;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturas pagadas - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Facturas pagadas</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:1200px; text-align:left;">

    <!-- ========================= -->
    <!-- Acordeón: Empresa -->
    <!-- ========================= -->
    <details class="acordeon" <?= $openEmpresaAcordeon ? 'open' : '' ?>>
        <summary class="acordeon__summary">
            <span>Facturas de la empresa · Pagadas</span>
            <span class="badge">Total: <?= (int)$countEmpresa ?></span>
        </summary>

        <div class="acordeon__content">

            <!-- Filtros internos (cerrado por defecto; se abre SOLO si hay filtros) -->
            <details class="acordeon" style="margin:0 0 18px 0;" <?= $openEmpresaFiltro ? 'open' : '' ?>>
                <summary class="acordeon__summary">
                    <span>🔎 Filtros (Empresa)</span>
                    <span class="badge">Abrir</span>
                </summary>
                <div class="acordeon__content">

                    <form method="GET" class="form-grid" style="max-width:950px;">
                        <label>ID factura</label>
                        <input name="emp_id" value="<?= htmlspecialchars($emp_id) ?>" placeholder="Ej: 12">

                        <label>ID pedido</label>
                        <input name="emp_pedido" value="<?= htmlspecialchars($emp_pedido) ?>" placeholder="Ej: 5">

                        <label>Correo creador</label>
                        <input name="emp_correo" value="<?= htmlspecialchars($emp_correo) ?>" placeholder="admin@ecohome.com">

                        <label>Fecha desde</label>
                        <input type="date" name="emp_desde" value="<?= htmlspecialchars($emp_desde) ?>">

                        <label>Fecha hasta</label>
                        <input type="date" name="emp_hasta" value="<?= htmlspecialchars($emp_hasta) ?>">

                        <label>Total mínimo (€)</label>
                        <input type="number" step="0.01" min="0" name="emp_min" value="<?= htmlspecialchars($emp_min) ?>">

                        <label>Total máximo (€)</label>
                        <input type="number" step="0.01" min="0" name="emp_max" value="<?= htmlspecialchars($emp_max) ?>">

                        <button type="submit">Aplicar filtro (Empresa)</button>
                        <a class="link-btn" href="facturas_pagadas.php" style="text-align:center;">Limpiar</a>
                    </form>

                </div>
            </details>

            <?php if (!$factEmpresa): ?>
                <p>No hay resultados para este filtro.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table class="tabla">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Creada por</th>
                            <th>Total</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($factEmpresa as $f): ?>
                            <tr id="emp_<?= (int)$f['id'] ?>">
                                <td><?= (int)$f['id'] ?></td>
                                <td>#<?= (int)$f['pedido_id'] ?></td>
                                <td><?= htmlspecialchars($f['creado_en']) ?></td>
                                <td><?= htmlspecialchars($f['creador_correo']) ?> (<?= htmlspecialchars($f['creador_rol']) ?>)</td>
                                <td><?= number_format((float)$f['total'], 2) ?> €</td>
                                <td>
                                    <a href="../pedidos/pedido_ver.php?id=<?= (int)$f['pedido_id'] ?>&back=facturas_pagadas&open=empresa&focus=emp_<?= (int)$f['id'] ?>">
                                    Ver pedido</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </details>

    <!-- ========================= -->
    <!-- Acordeón: Clientes -->
    <!-- ========================= -->
    <details class="acordeon" <?= $openClientesAcordeon ? 'open' : '' ?>>
        <summary class="acordeon__summary">
            <span>Facturas a clientes · Pagadas</span>
            <span class="badge">Total: <?= (int)$countClientes ?></span>
        </summary>

        <div class="acordeon__content">

            <!-- Filtros internos (cerrado por defecto; se abre SOLO si hay filtros) -->
            <details class="acordeon" style="margin:0 0 18px 0;" <?= $openClientesFiltro ? 'open' : '' ?>>
                <summary class="acordeon__summary">
                    <span>🔎 Filtros (Clientes)</span>
                    <span class="badge">Abrir</span>
                </summary>
                <div class="acordeon__content">

                    <form method="GET" class="form-grid" style="max-width:950px;">
                        <label>ID factura</label>
                        <input name="cli_id" value="<?= htmlspecialchars($cli_id) ?>" placeholder="Ej: 7">

                        <label>Cliente (nombre o email)</label>
                        <input name="cli_cliente" value="<?= htmlspecialchars($cli_cliente) ?>" placeholder="Rubén / gmail.com">

                        <label>Concepto</label>
                        <input name="cli_concept" value="<?= htmlspecialchars($cli_concept) ?>" placeholder="reforma / proyecto / ...">

                        <label>Correo creador</label>
                        <input name="cli_correo" value="<?= htmlspecialchars($cli_correo) ?>" placeholder="admin@ecohome.com">

                        <label>Fecha desde</label>
                        <input type="date" name="cli_desde" value="<?= htmlspecialchars($cli_desde) ?>">

                        <label>Fecha hasta</label>
                        <input type="date" name="cli_hasta" value="<?= htmlspecialchars($cli_hasta) ?>">

                        <label>Total mínimo (€)</label>
                        <input type="number" step="0.01" min="0" name="cli_min" value="<?= htmlspecialchars($cli_min) ?>">

                        <label>Total máximo (€)</label>
                        <input type="number" step="0.01" min="0" name="cli_max" value="<?= htmlspecialchars($cli_max) ?>">

                        <button type="submit">Aplicar filtro (Clientes)</button>
                        <a class="link-btn" href="facturas_pagadas.php" style="text-align:center;">Limpiar</a>
                    </form>

                </div>
            </details>

            <?php if (!$factClientes): ?>
                <p>No hay resultados para este filtro.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table class="tabla">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Concepto</th>
                            <th>Creada por</th>
                            <th>Total</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($factClientes as $fc): ?>
                            <tr id="cli_<?= (int)$fc['id'] ?>">
                                <td><?= (int)$fc['id'] ?></td>
                                <td><?= htmlspecialchars($fc['creado_en']) ?></td>
                                <td><?= htmlspecialchars($fc['cliente_nombre']) ?></td>
                                <td><?= htmlspecialchars($fc['cliente_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($fc['concepto']) ?></td>
                                <td><?= htmlspecialchars($fc['creador_correo']) ?> (<?= htmlspecialchars($fc['creador_rol']) ?>)</td>
                                <td><?= number_format((float)$fc['total'], 2) ?> €</td>
                                <td>
                                    <?php if (!empty($fc['archivo_path'])): ?>
                                        <a href="ver_factura_cliente.php?id=<?= (int)$fc['id'] ?>&back=facturas_pagadas&open=clientes&focus=cli_<?= (int)$fc['id'] ?>">Ver imagen</a>
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
        <a class="link-btn" href="facturas.php">⬅ Volver</a>
        <a class="link-btn" href="facturas_crear.php">Ir a Crear factura</a>
        <a class="link-btn" href="facturas_pagos.php">Ir a Facturas sin pagar</a>
        
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Facturas pagadas</p>
</footer>

<!-- ✅ scroll a la fila al volver -->
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

