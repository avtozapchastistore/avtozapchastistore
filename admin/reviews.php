<?php
session_start();
require_once '../config.php';

// Простая проверка аутентификации (как в остальных админ-страницах)
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Пагинация
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Получение отзывов
$sql = "SELECT r.id, r.product_id, r.name, r.rating, r.comment, r.approved, r.created_at, p.name AS product_name
        FROM reviews r
        LEFT JOIN products p ON p.id = r.product_id
        ORDER BY r.created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$reviews = $stmt->get_result();

// Общее количество для страниц
$total = $conn->query("SELECT COUNT(*) AS c FROM reviews")->fetch_assoc()['c'] ?? 0;
$totalPages = (int)ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы — Админ-панель</title>
    <link rel="stylesheet" href="/css/common.css">
    <style>
        table { width:100%; border-collapse: collapse; }
        th, td { padding: 8px 10px; border-bottom:1px solid rgba(0,0,0,0.08); vertical-align: top; }
        .actions a { margin-right: 8px; }
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:12px; }
        .ok { background:#DCFCE7; }
        .no { background:#FEE2E2; }
        .wrap { white-space: pre-line; }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <h1>Админ-панель — Отзывы</h1>
        <nav class="nav">
            <a href="/admin/index.php">Назад в панель</a>
            <a href="/admin/logout.php">Выйти</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="card">
        <h2>Отзывы</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Товар</th>
                <th>Имя</th>
                <th>Оценка</th>
                <th>Комментарий</th>
                <th>Статус</th>
                <th>Создан</th>
                <th>Действия</th>
            </tr>
            <?php if ($reviews && $reviews->num_rows > 0): ?>
                <?php while($r = $reviews->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$r['id']; ?></td>
                        <td>#<?= (int)$r['product_id']; ?> — <?= htmlspecialchars($r['product_name'] ?? '—'); ?></td>
                        <td><?= htmlspecialchars($r['name']); ?></td>
                        <td><?= (int)$r['rating']; ?>/5</td>
                        <td class="wrap"><?= nl2br(htmlspecialchars($r['comment'])); ?></td>
                        <td>
                            <?php if ((int)$r['approved'] === 1): ?>
                                <span class="badge ok">опубликован</span>
                            <?php else: ?>
                                <span class="badge no">скрыт</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['created_at']); ?></td>
                        <td class="actions">
                            <?php if ((int)$r['approved'] === 1): ?>
                                <a class="btn" href="review_actions.php?action=hide&id=<?= (int)$r['id']; ?>">Скрыть</a>
                            <?php else: ?>
                                <a class="btn" href="review_actions.php?action=approve&id=<?= (int)$r['id']; ?>">Опубликовать</a>
                            <?php endif; ?>
                            <a class="btn" href="review_actions.php?action=delete&id=<?= (int)$r['id']; ?>" onclick="return confirm('Удалить отзыв #<?= (int)$r['id']; ?>?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">Отзывов пока нет</td></tr>
            <?php endif; ?>
        </table>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top:12px;">
                Страницы:
                <?php for ($i=1; $i<=$totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <strong><?= $i ?></strong>
                    <?php else: ?>
                        <a class="btn" href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        © <?= date('Y'); ?> Магазин автозапчастей
    </div>
</footer>
</body>
</html>
<?php $conn->close(); ?>
