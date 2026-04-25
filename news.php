<?php
session_start();
require_once 'config.php';
$pageTitle = 'Название страницы';           // опционально
$extraCss   = ['css/about.css'];            // опционально: стили страницы
require __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/news.css">
</head>
<body>
    <!-- Контент -->
    <main class="container">
        <div class="news">
            <h2>Последние новости</h2>
            <?php
            $sql = "SELECT * FROM news ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='news-item'>";
                    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                    echo "<p class='news-date'>" . date("d.m.Y H:i", strtotime($row['created_at'])) . "</p>";
                    echo "<p>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<p>Новостей пока нет</p>";
            }
            ?>
        </div>
    </main>

    <!-- Футер -->
    <footer class="site-footer">
        <div class="container">
            &copy; <?php echo date("Y"); ?> Магазин автозапчастей. Все права защищены.
        </div>
    </footer>

</body>
</html>
<?php $conn->close(); ?>
