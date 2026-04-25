<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php"); exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=auto_parts_shop;charset=utf8", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die("Ошибка подключения к базе данных");
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $err = "Недействительный CSRF-токен.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title === '' || $content === '') {
            $err = "Заполните заголовок и текст.";
        } else {
            $st = $pdo->prepare("INSERT INTO news (title, content) VALUES (?, ?)");
            if ($st->execute([$title, $content])) {
                header("Location: index.php"); exit;
            } else {
                $err = "Не удалось добавить новость.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Добавить новость</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/common.css">
  <link rel="stylesheet" href="/css/admin_form.css">
  <style>.notice.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 12px;border-radius:10px;margin-bottom:12px}</style>
</head>
<body>
<header class="site-header">
  <div class="container header-row">
    <a href="./index.php" class="brand"><span class="brand-name">Магазин автозапчастей — Админ</span></a>
    <nav class="main-nav">
      <a href="./index.php" class="nav-link">Главная</a>
      <a href="logout.php" class="nav-link">Выйти</a>
    </nav>
  </div>
</header>

<main class="container">
  <div class="admin-form">
    <h2>Добавить новость</h2>

    <?php if ($err): ?><div class="notice err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <form method="POST" action="add_news.php">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      <label for="title">Заголовок:</label>
      <input type="text" id="title" name="title" required maxlength="255">
      <label for="content">Содержание:</label>
      <textarea id="content" name="content" rows="10" required></textarea>
      <button type="submit" class="btn btn-primary">Добавить</button>
      <a href="index.php" class="btn btn-ghost" style="margin-left:10px;">Назад</a>
    </form>
  </div>
</main>

<footer class="site-footer"><div class="container"><p>© <?php echo date("Y"); ?> Магазин автозапчастей</p></div></footer>
</body>
</html>
