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

$fn = 'orders_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$fn.'"');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Клиент','Адрес','Телефон','Статус','Сумма','Позиции','Дата'], ';');

$st = $pdo->query("SELECT id, customer_name, customer_address, phone, status, total, products, created_at FROM orders ORDER BY created_at DESC");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    // превратим products JSON в строку: "Название x Кол-во; ..."
    $itemsStr = '';
    $items = json_decode($r['products'], true);
    if (is_array($items) && $items) {
        $ids = array_values(array_unique(array_map(fn($i)=> (int)($i['id'] ?? 0), $items)));
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $st2 = $pdo->prepare("SELECT id, name FROM products WHERE id IN ($in)");
            $st2->execute($ids);
            $names = [];
            while ($p = $st2->fetch(PDO::FETCH_ASSOC)) $names[(int)$p['id']] = $p['name'];
            foreach ($items as $it) {
                $pid = (int)$it['id'];
                $q = (int)$it['quantity'];
                $nm = $names[$pid] ?? ('#'.$pid);
                $itemsStr .= $nm . ' x ' . $q . '; ';
            }
            $itemsStr = rtrim($itemsStr, '; ');
        }
    }

    fputcsv($out, [
        $r['id'],
        $r['customer_name'],
        $r['customer_address'],
        $r['phone'],
        $r['status'],
        $r['total'],
        $itemsStr,
        date('d.m.Y H:i', strtotime($r['created_at']))
    ], ';');
}
fclose($out);
