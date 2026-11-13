
<?php

// EVENTHUB - KET NOI DATABASE

$host = '127.0.0.1';
$dbname = 'eventhub';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {

    // Tao bien ket noi PDO
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    die("Ket noi database that bai. Vui long kiem tra cau hinh.");

}

