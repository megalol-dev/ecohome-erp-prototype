<?php
// usuarios.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$rolSesion = $_SESSION['user']['rol'] ?? '';
if (!in_array($rolSesion, ['admin', 'RRHH'], true)) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getPDO();

// Crear usuario
$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido1 = trim($_POST['apellido1'] ?? '');
    $apellido2 = trim($_POST['apellido2'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $rol       = trim($_POST['rol'] ?? '');
    $password  = (string)($_POST['password'] ?? '');

    $rolesValidos = ['admin', 'RRHH', 'Directivos', 'Logistica', 'Gestion'];

    if ($nombre === '' || $apellido1 === '' || $correo === '' || $password === '' || $rol === '') {
        $errors[] = "Rellena todos los campos obligatorios.";
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El correo no tiene un formato válido.";
    }
    if (!in_array($rol, $rolesValidos, true)) {
        $errors[] = "Rol no válido.";
    }
    if (strlen($password) < 4) {
        $errors[] = "La contraseña debe tener al menos 4 caracteres.";
    }

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO trabajadores (nombre, apellido1, apellido2, telefono, correo, password_hash, rol, activo)
                VALUES (:nombre, :apellido1, :apellido2, :telefono, :correo, :hash, :rol, 1)
            ");
            $stmt->execute([
                ':nombre'    => $nombre,
                ':apellido1' => $apellido1,
                ':apellido2' => $apellido2,
                ':telefono'  => $telefono,
                ':correo'    => $correo,
                ':hash'      => $hash,
                ':rol'       => $rol
            ]);

            $ok = "Usuario creado correctamente ✅";
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                $errors[] = "Ese correo ya existe.";
            } else {
                $errors[] = "Error al crear el usuario: " . $e->getMessage();
            }
        }
    }
}

// Listar usuarios
$usuarios = $pdo->query("
    SELECT id, nombre, apellido1, apellido2, telefono, correo, rol, activo, creado_en
    FROM trabajadores
    ORDER BY id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1>Usuarios</h1>
    <p>Acceso permitido para: <strong>admin</strong> y <strong>RRHH</strong></p>
</header>

<main class="main">
    <section class="dashboard-box" style="max-width: 1000px; text-align:left;">
        <h2>Crear trabajador</h2>

        <?php if ($ok): ?>
            <p class="success"><?= htmlspecialchars($ok) ?></p>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="usuarios.php" class="form-grid">
            <input type="hidden" name="action" value="create">

            <label>Nombre *</label>
            <input name="nombre" required>

            <label>Apellido 1 *</label>
            <input name="apellido1" required>

            <label>Apellido 2</label>
            <input name="apellido2">

            <label>Teléfono</label>
            <input name="telefono">

            <label>Correo *</label>
            <input name="correo" type="email" required>

            <label>Contraseña *</label>
            <input name="password" type="password" required>

            <label>Rol *</label>
            <select name="rol" required>
                <option value="RRHH">RRHH</option>
                <option value="Directivos">Directivos</option>
                <option value="Logistica">Logistica</option>
                <option value="Gestion">Gestion</option>
                <option value="admin">admin</option>
            </select>

            <button type="submit">Crear usuario</button>
        </form>

        <hr style="margin:20px 0;">

        <h2>Listado de usuarios</h2>

        <div style="overflow:auto;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido1'] . ' ' . ($u['apellido2'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($u['correo']) ?></td>
                        <td><?= htmlspecialchars($u['rol']) ?></td>
                        <td><?= ((int)$u['activo'] === 1) ? 'Sí' : 'No' ?></td>
                        <td><?= htmlspecialchars($u['creado_en']) ?></td>
                        <td>
                            <a href="usuarios_edit.php?id=<?= (int)$u['id'] ?>">Editar</a>
                            |
                            <a href="usuarios_delete.php?id=<?= (int)$u['id'] ?>" onclick="return confirm('¿Seguro que quieres borrar este usuario?');">Borrar</a>
                        </td>
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
    <p>© 2026 EcoHome · Gestión de usuarios</p>
</footer>

</body>
</html>
