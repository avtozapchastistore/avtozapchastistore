<?php
session_start();
require_once 'config.php';
$pageTitle = 'Новости';
$extraCss = ['css/news.css'];
require __DIR__ . '/header.php';
?>

<main class="container">
    <div class="news">
        <h2>Последние новости</h2>
        <?php
        $sql = "SELECT * FROM news ORDER BY created_at DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='news-item'>";
                if (!empty($row['image'])) {
                    echo "<img src='uploads/news/" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['title']) . "' style='max-width:100%; border-radius:8px; margin-bottom:12px;'>";
                }
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

<?php require __DIR__ . '/footer.php'; ?>
