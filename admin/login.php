<?php
session_start();
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    if ($password === 'admin123') { // ❗ В продакшене заменить на проверку с хэшем
        $_SESSION['admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'Неверный пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/admin_form.css">
</head>
<body>

<!-- ===== ХЕДЕР ===== -->
<header class="site-header">
    <div class="container header-row">
        <a href="../index.php" class="brand">
            <span class="brand-name">Магазин автозапчастей — Вход</span>
        </a>
        <nav class="main-nav">
            <a href="../index.php" class="nav-link">Главная</a>
            <a href="../cart.php" class="nav-link">Корзина</a>
            <a href="../news.php" class="nav-link">Новости</a>
        </nav>
    </div>
</header>

<!-- ===== ОСНОВНОЙ КОНТЕНТ ===== -->
<main class="container">
    <div class="admin-form">
        <h2>Вход в админ-панель</h2>
        <?php if ($error): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>
            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-primary">Войти</button>
                <a href="../index.php" class="btn btn-ghost" style="margin-left: 10px;">Назад</a>
            </div>
        </form>
    </div>
</main>

<!-- ===== ФУТЕР ===== -->
<footer class="site-footer">
    <div class="container">
        <p>© <?php echo date("Y"); ?> Магазин автозапчастей</p>
    </div>
</footer>

</body>
</html>
<?php $conn->close(); ?>
