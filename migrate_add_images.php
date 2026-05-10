<?php
require_once __DIR__ . '/config.php';

$products_images = [
    1 => 'oil_filter_mann.jpg',
    2 => 'brake_pads_front.jpg',
    3 => 'spark_plugs_ngk.jpg',
    4 => 'battery_60ah.jpg',
    5 => 'cabin_air_filter.jpg',
    6 => 'brake_disc_front.jpg',
    7 => 'suspension_arm.jpg',
    8 => 'shock_absorber_front.jpg',
    9 => 'stabilizer_strut.jpg',
    10 => 'timing_belt.jpg',
    11 => 'thermostat.jpg',
    12 => 'engine_oil_5w30.jpg',
    13 => 'fuel_filter.jpg',
    14 => 'brake_cylinder.jpg',
    15 => 'alternator.jpg',
    16 => 'tie_rod.jpg',
    17 => 'water_pump.jpg',
    18 => 'ignition_coil.jpg',
    19 => 'hub_bearing.jpg',
    20 => 'head_gasket.jpg',
];

$news_images = [
    1 => 'new_parts.jpg',
    2 => 'battery_sale.jpg',
    3 => 'new_items.jpg',
    4 => 'winter_service.jpg',
    5 => 'service_center.jpg',
];

$errors = [];
$success = [];

$result = $conn->query("ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL");
if ($result) {
    $success[] = "Таблица products: добавлено поле image";
} else {
    if ($conn->errno == 1060) {
        $success[] = "Таблица products: поле image уже существует";
    } else {
        $errors[] = "Ошибка products: " . $conn->error;
    }
}

$result = $conn->query("ALTER TABLE news ADD COLUMN image VARCHAR(255) DEFAULT NULL");
if ($result) {
    $success[] = "Таблица news: добавлено поле image";
} else {
    if ($conn->errno == 1060) {
        $success[] = "Таблица news: поле image уже существует";
    } else {
        $errors[] = "Ошибка news: " . $conn->error;
    }
}

foreach ($products_images as $id => $image) {
    $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
    $stmt->bind_param('si', $image, $id);
    $stmt->execute();
    $stmt->close();
}
$success[] = "Заполнено " . count($products_images) . " товаров";

foreach ($news_images as $id => $image) {
    $stmt = $conn->prepare("UPDATE news SET image = ? WHERE id = ?");
    $stmt->bind_param('si', $image, $id);
    $stmt->execute();
    $stmt->close();
}
$success[] = "Заполнено " . count($news_images) . " новостей";

echo "<h2>Миграция завершена</h2>";
if ($success) {
    echo "<ul>";
    foreach ($success as $s) echo "<li style='color:green'>$s</li>";
    echo "</ul>";
}
if ($errors) {
    echo "<ul>";
    foreach ($errors as $e) echo "<li style='color:red'>$e</li>";
    echo "</ul>";
}

echo "<h3>Картинки для загрузки:</h3>";
echo "<h4>Товары (uploads/products/):</h4><ul>";
foreach ($products_images as $img) echo "<li>$img</li>";
echo "</ul>";
echo "<h4>Новости (uploads/news/):</h4><ul>";
foreach ($news_images as $img) echo "<li>$img</li>";
echo "</ul>";
echo "<p><a href='index.php'>На главную</a></p>";

$conn->close();