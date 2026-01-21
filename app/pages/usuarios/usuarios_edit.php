<?php
// usuarios_edit.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../../db.php';

if (empty($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit;
}

$rolSesion = $_SESSION['user']['rol'] ?? '';
if (!in_array($rolSesion, ['admin', 'RRHH'], true)) {
    header('Location: ../../dashboard.php');
    exit;
}

$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

$rolesValidos = ['admin', 'RRHH', 'Directivos', 'Logistica', 'Gestion'];

$stmt = $pdo->prepare("SELECT * FROM trabajadores WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: usuarios.php');
    exit;
}

$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido1 = trim($_POST['apellido1'] ?? '');
    $apellido2 = trim($_POST['apellido2'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $rol       = trim($_POST['rol'] ?? '');
    $activo    = (int)($_POST['activo'] ?? 1);
    $password  = (string)($_POST['password'] ?? ''); // opcional

    if ($nombre === '' || $apellido1 === '' || $correo === '' || $rol === '') {
        $errors[] = "Rellena los campos obligatorios.";
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Correo no válido.";
    }
    if (!in_array($rol, $rolesValidos, true)) {
        $errors[] = "Rol no válido.";
    }
    if (!in_array($activo, [0, 1], true)) {
        $errors[] = "Activo debe ser 0 o 1.";
    }

    if (!$errors) {
        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE trabajadores
                        SET nombre=:nombre, apellido1=:apellido1, apellido2=:apellido2, telefono=:telefono,
                            correo=:correo, rol=:rol, activo=:activo, password_hash=:hash
                        WHERE id=:id";
                $params = [
                    ':nombre'=>$nombre, ':apellido1'=>$apellido1, ':apellido2'=>$apellido2, ':telefono'=>$telefono,
                    ':correo'=>$correo, ':rol'=>$rol, ':activo'=>$activo, ':hash'=>$hash, ':id'=>$id
                ];
            } else {
                $sql = "UPDATE trabajadores
                        SET nombre=:nombre, apellido1=:apellido1, apellido2=:apellido2, telefono=:telefono,
                            correo=:correo, rol=:rol, activo=:activo
                        WHERE id=:id";
                $params = [
                    ':nombre'=>$nombre, ':apellido1'=>$apellido1, ':apellido2'=>$apellido2, ':telefono'=>$telefono,
                    ':correo'=>$correo, ':rol'=>$rol, ':activo'=>$activo, ':id'=>$id
                ];
            }

            $upd = $pdo->prepare($sql);
            $upd->execute($params);

            $ok = "Usuario actualizado ✅";

            // Recargar datos
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                $errors[] = "Ese correo ya existe.";
            } else {
                $errors[] = "Error al actualizar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar usuario - EcoHome</title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>Editar usuario</h1>
    <p>ID: <strong><?= (int)$user['id'] ?></strong></p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width: 700px; text-align:left;">
        <?php if ($ok): ?><p class="success"><?= htmlspecialchars($ok) ?></p><?php endif; ?>

        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <label>Nombre *</label>
            <input name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>

            <label>Apellido 1 *</label>
            <input name="apellido1" value="<?= htmlspecialchars($user['apellido1']) ?>" required>

            <label>Apellido 2</label>
            <input name="apellido2" value="<?= htmlspecialchars($user['apellido2'] ?? '') ?>">

            <label>Teléfono</label>
            <input name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">

            <label>Correo *</label>
            <input name="correo" type="email" value="<?= htmlspecialchars($user['correo']) ?>" required>

            <label>Rol *</label>
            <select name="rol" required>
                <?php foreach (['RRHH','Directivos','Logistica','Gestion','admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($user['rol'] === $r) ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>

            <label>Activo</label>
            <select name="activo">
                <option value="1" <?= ((int)$user['activo'] === 1) ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= ((int)$user['activo'] === 0) ? 'selected' : '' ?>>No</option>
            </select>

            <label>Nueva contraseña (opcional)</label>
            <input name="password" type="password" placeholder="(dejar en blanco para no cambiar)">

            <button type="submit">Guardar cambios</button>
        </form>

        <div style="margin-top:15px;">
            <a class="link-btn" href="usuarios.php">⬅ Volver</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2026 EcoHome · Edición de usuarios</p>
</footer>

</body>
</html>

