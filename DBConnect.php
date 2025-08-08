<?php
// db.php (PostgreSQL / Neon)
declare(strict_types=1);

$host    = 'ep-floral-hill-a1y8vxri-pooler.ap-southeast-1.aws.neon.tech';
$db      = 'carDB';
$user    = 'neondb_owner';
$pass    = 'npg_9CdmbMVYvat1';
$sslmode = 'require';
$port    = 5432;

$dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$sslmode}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // PDO::ATTR_PERSISTENT => true, // optional
    ]);
} catch (PDOException $e) {
    throw new RuntimeException('DB connection failed: ' . $e->getMessage(), 0, $e);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  //This line ensures that PDO will throw exceptions on errors