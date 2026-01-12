<?php
// facturas_crear.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$rol  = $user['rol'] ?? '';

// Solo admin y gestión
$rolesPuedenEntrar = ['admin', 'Gestion'];
if (!in_array($rol, $rolesPuedenEntrar, true)) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getPDO();

$ok  = $_GET['ok']  ?? '';
$err = $_GET['err'] ?? '';

// Crear factura cliente
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear_cliente') {
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_email  = trim($_POST['cliente_email'] ?? '');
    $concepto       = trim($_POST['concepto'] ?? '');
    $base           = (float)($_POST['base_imponible'] ?? 0);
    $iva            = (float)($_POST['iva_porcentaje'] ?? 21);

    $archivoPath = null;

    if ($cliente_nombre === '' || $concepto === '') $errors[] = "Rellena cliente y concepto.";
    if ($cliente_email !== '' && !filter_var($cliente_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email de cliente no válido.";
    if ($base < 0) $errors[] = "La base imponible no puede ser negativa.";
    if ($iva < 0) $errors[] = "El IVA no puede ser negativo.";

    // Subida de imagen opcional
    if (!$errors) {
        if (isset($_FILES['archivo_factura']) && $_FILES['archivo_factura']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['archivo_factura']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Error subiendo el archivo.";
            } else {
                $tmp  = (string)($_FILES['archivo_factura']['tmp_name'] ?? '');
                $size = (int)($_FILES['archivo_factura']['size'] ?? 0);

                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    $errors[] = "Subida no válida (archivo temporal no encontrado).";
                } elseif ($size > 5 * 1024 * 1024) {
                    $errors[] = "El archivo es demasiado grande (máx 5MB).";
                } else {

                    // Detectar MIME de forma robusta (sin depender de fileinfo)
                    $mime = null;

                    // 1) Si existe finfo (ideal, pero puede no estar)
                    if (class_exists('finfo')) {
                        $fi = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $fi->file($tmp);
                    }
                    // 2) Fallback: mime_content_type (a veces disponible)
                    elseif (function_exists('mime_content_type')) {
                        $mime = mime_content_type($tmp);
                    }

                    // 3) Último fallback para imágenes: getimagesize
                    if (!$mime && function_exists('getimagesize')) {
                        $info = @getimagesize($tmp);
                        if ($info && !empty($info['mime'])) {
                            $mime = $info['mime'];
                        }
                    }

                    $extMap = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!$mime || !isset($extMap[$mime])) {
                        $errors[] = "Formato no permitido. Solo JPG, PNG o WEBP.";
                    } else {
                        $uploadsDir = __DIR__ . '/uploads';
                        if (!is_dir($uploadsDir)) {
                            if (!mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                                $errors[] = "No se pudo crear el directorio uploads.";
                            }
                        }

                        if (!$errors) {
                            $ext = $extMap[$mime];
                            $safeName = 'fc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                            $destAbs  = $uploadsDir . '/' . $safeName;

                            if (!move_uploaded_file($tmp, $destAbs)) {
                                $errors[] = "No se pudo guardar el archivo en el servidor.";
                            } else {
                                $archivoPath = 'uploads/' . $safeName;
                            }
                        }
                    }
                }
            }
        }
    }

    if (!$errors) {
        $total = $base * (1 + ($iva / 100));

        try {
            $stmt = $pdo->prepare("
                INSERT INTO facturas_clientes
                (trabajador_id, cliente_nombre, cliente_email, concepto, archivo_path, base_imponible, iva_porcentaje, total, estado)
                VALUES
                (:tid, :cn, :ce, :con, :ap, :base, :iva, :total, 'pendiente')
            ");
            $stmt->execute([
                ':tid'   => (int)$user['id'],
                ':cn'    => $cliente_nombre,
                ':ce'    => $cliente_email,
                ':con'   => $concepto,
                ':ap'    => $archivoPath,
                ':base'  => $base,
                ':iva'   => $iva,
                ':total' => $total
            ]);

            header('Location: facturas_crear.php?ok=cliente_creada');
            exit;
        } catch (Throwable $e) {
            $errors[] = "Error creando factura de cliente: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear factura - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Crear factura</h1>
    <p>
        Usuario: <strong><?= htmlspecialchars($user['correo']) ?></strong> |
        Rol: <strong><?= htmlspecialchars($rol) ?></strong>
    </p>
</header>

<main class="main">
<section class="dashboard-box" style="max-width:900px; text-align:left;">

    <?php if ($ok === 'cliente_creada'): ?>
        <p class="success">Factura de cliente creada ✅ (estado: pendiente)</p>
    <?php endif; ?>

    <?php if ($err !== ''): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Factura a cliente</h2>

    <form method="POST" enctype="multipart/form-data" class="form-grid" style="max-width:600px;">
        <input type="hidden" name="action" value="crear_cliente">

        <label>Nombre del cliente *</label>
        <input name="cliente_nombre" required>

        <label>Email del cliente</label>
        <input name="cliente_email" type="email" placeholder="cliente@email.com">

        <label>Concepto *</label>
        <input name="concepto" required placeholder="Ej: Proyecto vivienda sostenible - Fase 1">

        <label>Adjuntar factura (imagen)</label>
        <input name="archivo_factura" type="file" accept="image/*">

        <label>Base imponible (€) *</label>
        <input name="base_imponible" type="number" step="0.01" min="0" required>

        <label>IVA (%)</label>
        <input name="iva_porcentaje" type="number" step="0.01" min="0" value="21">

        <button type="submit">Crear factura cliente</button>
    </form>

    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="link-btn" href="facturas.php">⬅ Volver</a>
        <a class="link-btn" href="facturas_pagos.php">Ir a Facturas sin pagar</a>
        <a class="link-btn" href="facturas_pagadas.php">Ir a Facturas pagadas</a>
    </div>

</section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Crear factura</p>
</footer>

</body>
</html>

