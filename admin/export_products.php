<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(403); exit('Forbidden');
}
try {
    $pdo = new PDO("mysql:host=localhost;dbname=auto_parts_shop;charset=utf8", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500); exit('DB error');
}

$fn = 'products_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$fn.'"');
// BOM для Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Название','Категория','Цена','Остаток','Короткое описание'], ';');

$st = $pdo->query("SELECT p.id, p.name, c.name AS category_name, p.price, p.stock, p.short_description
                   FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $r['id'],
        $r['name'],
        $r['category_name'],
        $r['price'],
        isset($r['stock']) ? $r['stock'] : 0,
        $r['short_description']
    ], ';');
}
fclose($out);
