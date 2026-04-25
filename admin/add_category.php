<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $sql = "INSERT INTO categories (name) VALUES ('$name')";
    if ($conn->query($sql)) {
        header("Location: index.php");
    } else {
        echo "<p>Ошибка при добавлении категории</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить категорию</title>
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/admin_form.css">
</head>
<body>

<!-- Хедер -->
<header class="site-header">
    <div class="container header-row">
        <a href="../index.php" class="brand">
            <span class="brand-name">Магазин автозапчастей</span>
        </a>
        <nav class="main-nav">
            <a href="../index.php" class="nav-link">Главная</a>
            <a href="../cart.php" class="nav-link">Корзина</a>
            <a href="../news.php" class="nav-link">Новости</a>
            <a href="index.php" class="nav-link nav-link--ghost">Админ-панель</a>
            <a href="logout.php" class="nav-link">Выйти</a>
        </nav>
    </div>
</header>

<!-- Контент -->
<main class="container">
    <div class="admin-form">
        <h2>Новая категория</h2>
        <form method="POST" action="add_category.php">
            <label>Название:</label>
            <input type="text" name="name" required>
            <button type="submit">Добавить</button>
            <a href="index.php" class="btn btn-ghost" style="margin-top:10px;display:inline-block;">Назад</a>
        </form>
    </div>
</main>

<!-- Футер -->
<footer class="site-footer">
    <div class="container">
        &copy; <?= date('Y') ?> Магазин автозапчастей. Все права защищены.
    </div>
</footer>

</body>
</html>
<?php $conn->close(); ?>
