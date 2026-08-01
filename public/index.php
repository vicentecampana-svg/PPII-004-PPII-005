<?php
#prueba para ver si funciona todo bien
declare(strict_types=1);

$host = getenv('PG_HOST');
$port = getenv('PG_PORT');
$database = getenv('PG_DATABASE');
$user = getenv('PG_USER');
$password = getenv('PG_PASSWORD');

try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$database}",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    echo '<h1>Proyecto Tech Hub ULS</h1>';
    echo '<p>PHP funciona correctamente.</p>';
    echo '<p>Conexión con PostgreSQL exitosa.</p>';
} catch (PDOException $exception) {
    echo '<h1>Error de conexión</h1>';
    echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
}