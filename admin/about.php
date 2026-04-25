<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>О нас — Магазин автозапчастей</title>
  <link rel="stylesheet" href="/css/common.css">
</head>
<body>
  <div class="header">
    <div class="container">
      <h1>Магазин автозапчастей</h1>
      <div class="nav">
        <a href="index.php">Главная</a>
        <a href="cart.php">Корзина</a>
        <a href="news.php">Новости</a>
        <a href="about.php">О нас</a>
      </div>
    </div>
  </div>

  <main class="container">
    <div class="card">
      <h2>О нас</h2>
      <p>Мы — команда, которая с 2015 года помогает автовладельцам быстро и выгодно находить нужные запчасти. Работаем только с проверенными поставщиками и даём гарантию на весь ассортимент.</p>
      <p>Наши приоритеты — качество, прозрачные цены и удобный сервис. Если у вас есть вопросы — <a href="mailto:info@example.com">напишите нам</a>.</p>
      <ul>
        <li>Более 10&nbsp;000 наименований на складе</li>
        <li>Доставка по всей стране</li>
        <li>Подбор по VIN</li>
      </ul>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      © <?php echo date('Y'); ?> Магазин автозапчастей
    </div>
  </footer>
</body>
</html>
<?php $conn->close(); ?>
