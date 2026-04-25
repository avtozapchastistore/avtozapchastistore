<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Админ-панель'; ?></title>
    <!-- Абсолютные пути -->
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/admin_index.css">
</head>
<body>
    <header class="header">
        <h1>Админ-панель</h1>
        <nav class="nav">
            <a href="/index.php">Главная</a>
            <a href="/cart.php">Корзина</a>
            <a href="/news.php">Новости</a>
            <a href="/admin/index.php" class="active">Админ-панель</a>
            <a href="/admin/logout.php">Выйти</a>
        </nav>
    </header>
    <main>
