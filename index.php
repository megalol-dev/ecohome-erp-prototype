<?php
// index.php
declare(strict_types=1);
session_start();

if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de EcoHome</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <h1>Gestor de EcoHome</h1>
        <p>Plataforma interna de gestión y control</p>
    </header>

    <!-- MAIN / LOGIN -->
    <main class="main">
        <section class="login-box">
            <h2>Iniciar sesión</h2>

            <?php if ($error === '1'): ?>
            <p class="error">Correo o contraseña incorrectos.</p>
            <?php elseif ($error === '2'): ?>
            <p class="error">Tu usuario está desactivado. Contacta con administración.</p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" placeholder="usuario@ecohome.com" required>

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>

                <button type="submit">Acceder</button>
            </form>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <p>© 2026 EcoHome · Plataforma corporativa interna</p>
        <p>Transformación digital orientada a sostenibilidad y transparencia</p>
    </footer>

</body>

</html>