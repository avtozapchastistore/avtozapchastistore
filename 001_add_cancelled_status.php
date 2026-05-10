<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN status enum('pending','accepted','cancelled') NOT NULL DEFAULT 'pending'");
    echo "Миграция выполнена";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
