<?php

require __DIR__ . '/../app/bootstrap.php';

$pdo = new PDO(
    'mysql:host=' . config('database.host') . ';dbname=' . config('database.database') . ';charset=utf8mb4',
    (string) config('database.username'),
    (string) config('database.password'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$check = $pdo->query("SHOW COLUMNS FROM ibs_products LIKE 'supplier_product_category'");
if ($check->rowCount() > 0) {
    echo "SKIP: supplier_product_category already exists\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/../database/migrations/0011_supplier_product_category.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = trim($sql);
if ($sql === '') {
    fwrite(STDERR, "FAIL: empty migration SQL\n");
    exit(1);
}

$pdo->exec($sql);
$verify = $pdo->query("SHOW COLUMNS FROM ibs_products LIKE 'supplier_product_category'");
$row = $verify->fetch(PDO::FETCH_ASSOC);
echo "APPLIED: supplier_product_category\n";
echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
