<?php
// DBconnect.php
$host = 'ep-floral-hill-a1y8vxri-pooler.ap-southeast-1.aws.neon.tech';
$db   = 'carDB';
$user = 'neondb_owner';
$pass = 'npg_9CdmbMVYvat1';
$sslmode = 'require';

$dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=$sslmode";

//psql 'postgresql://neondb_owner:npg_9CdmbMVYvat1@ep-floral-hill-a1y8vxri-pooler.ap-southeast-1.aws.neon.tech/carDB?sslmode=require&channel_binding=require'

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected to PostgreSQL successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>


