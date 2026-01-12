<?php
// create_admin.php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $pdo = getPDO();

    // DATOS DEL ADMIN
    $nombre      = 'Admin';
    $apellido1   = 'EcoHome';
    $apellido2   = '';
    $telefono    = '600000000';
    $correo      = 'admin@ecohome.com';
    $password    = 'admin123'; // ← contraseña inicial
    $rol         = 'admin';

    // Hash de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar solo si no existe
    $stmt = $pdo->prepare("
        INSERT INTO trabajadores
        (nombre, apellido1, apellido2, telefono, correo, password_hash, rol)
        VALUES
        (:nombre, :apellido1, :apellido2, :telefono, :correo, :password_hash, :rol)
    ");

    $stmt->execute([
        ':nombre'        => $nombre,
        ':apellido1'     => $apellido1,
        ':apellido2'     => $apellido2,
        ':telefono'      => $telefono,
        ':correo'        => $correo,
        ':password_hash' => $passwordHash,
        ':rol'           => $rol
    ]);

    echo "<h2>✅ Usuario ADMIN creado correctamente</h2>";
    echo "<p><strong>Correo:</strong> admin@ecohome.com</p>";
    echo "<p><strong>Contraseña:</strong> admin123</p>";
    echo "<p>⚠️ Borra este archivo después de usarlo.</p>";

} catch (Throwable $e) {
    echo "<h2>⚠️ Error al crear el admin</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
