<?php
// pedidos_crear.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

if (!in_array($rol, ['admin', 'Logistica'], true)) {
    header('Location: pedidos.php?err=' . urlencode('No tienes permisos para crear pedidos.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pedidos.php');
    exit;
}

$pdo = getPDO();

// Recibimos cantidades: cant[material_id] = cantidad
$cant = $_POST['cant'] ?? [];
if (!is_array($cant)) {
    header('Location: pedidos.php?err=' . urlencode('Datos de pedido inválidos.'));
    exit;
}

// Filtrar cantidades > 0
$items = [];
foreach ($cant as $materialId => $cantidad) {
    $materialId = (int)$materialId;
    $cantidad = (int)$cantidad;
    if ($materialId > 0 && $cantidad > 0) {
        $items[$materialId] = $cantidad;
    }
}

if (!$items) {
    header('Location: pedidos.php?err=' . urlencode('No has seleccionado ninguna cantidad.'));
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) Crear pedido (cabecera) con total 0 de momento
    $stmt = $pdo->prepare("INSERT INTO pedidos (trabajador_id, total) VALUES (:tid, 0)");
    $stmt->execute([':tid' => (int)$user['id']]);
    $pedidoId = (int)$pdo->lastInsertId();

    $total = 0.0;

    // 2) Para cada item: leer precio unitario y stock, calcular subtotal, insertar línea
    $sel = $pdo->prepare("SELECT id, unidades, precio_unitario, nombre FROM materiales WHERE id = :id AND activo = 1");
    $insItem = $pdo->prepare("
        INSERT INTO pedido_items (pedido_id, material_id, cantidad, precio_unitario, subtotal)
        VALUES (:pid, :mid, :cant, :pu, :sub)
    ");

    foreach ($items as $materialId => $cantidad) {
        $sel->execute([':id' => $materialId]);
        $mat = $sel->fetch();

        if (!$mat) {
            throw new RuntimeException("Material inválido (ID $materialId).");
        }

        // NOTA: aquí NO descontamos stock (en el futuro lo podéis hacer).
        // Ahora solo registramos el pedido.
        $precioUnit = (float)$mat['precio_unitario'];
        $subtotal = $precioUnit * $cantidad;

        $insItem->execute([
            ':pid'  => $pedidoId,
            ':mid'  => $materialId,
            ':cant' => $cantidad,
            ':pu'   => $precioUnit,
            ':sub'  => $subtotal
        ]);

        $total += $subtotal;
    }

    // 3) Actualizar total del pedido
    $upd = $pdo->prepare("UPDATE pedidos SET total = :total WHERE id = :id");
    $upd->execute([':total' => $total, ':id' => $pedidoId]);

    // 4) Crear factura asociada al pedido (estado pendiente)
    $fac = $pdo->prepare("
        INSERT INTO facturas (pedido_id, trabajador_id, total, estado)
        VALUES (:pid, :tid, :total, 'pendiente')
    ");
    $fac->execute([
        ':pid' => $pedidoId,
        ':tid' => (int)$user['id'],
        ':total' => $total
    ]);

    $pdo->commit();

    header('Location: pedidos.php?ok=1');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: pedidos.php?err=' . urlencode('Error creando pedido: ' . $e->getMessage()));
    exit;
}
