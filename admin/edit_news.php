<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php"); exit;
}

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die("Ошибка подключения к базе данных");
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

$st = $pdo->prepare("SELECT id, title, content, image, created_at FROM news WHERE id = ?");
$st->execute([$id]);
$news = $st->fetch(PDO::FETCH_ASSOC);
if (!$news) { header("Location: index.php"); exit; }

$ok = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $err = "Недействительный CSRF-токен.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $image = $news['image'];

        if ($title === '' || $content === '') {
            $err = "Заполните заголовок и текст.";
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $mime = mime_content_type($_FILES['image']['tmp_name']);
                if (!in_array($mime, $allowed)) {
                    $err = 'Разрешены только изображения: JPEG, PNG, GIF, WEBP.';
                } else {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('news_') . '.' . $ext;
                    $upload_dir = __DIR__ . '/../uploads/news/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $dest = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        if ($image && file_exists($upload_dir . $image)) {
                            @unlink($upload_dir . $image);
                        }
                        $image = $filename;
                    } else {
                        $err = 'Не удалось сохранить изображение.';
                    }
                }
            }

            if (!$err) {
                $st = $pdo->prepare("UPDATE news SET title = ?, content = ?, image = ? WHERE id = ?");
                if ($st->execute([$title, $content, $image, $id])) {
                    $ok = "Изменения сохранены.";
                    $news['title'] = $title;
                    $news['content'] = $content;
                    $news['image'] = $image;
                } else {
                    $err = "Не удалось сохранить изменения.";
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Редактировать новость #<?php echo (int)$news['id']; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/common.css">
  <link rel="stylesheet" href="/css/admin_form.css">
  <style>
    .notice { margin-bottom: 12px; padding: 10px 12px; border-radius: 10px; }
    .notice-ok { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
    .notice-err { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
    .admin-form textarea.input { min-height: 150px; resize: vertical; }
    .image-preview { margin-top: 8px; max-width: 250px; }
    .image-preview img { max-width: 100%; border-radius: 8px; }
  </style>
</head>
<body>
<header class="site-header">
  <div class="container header-row">
    <a href="./index.php" class="brand"><span class="brand-name">Магазин автозапчастей — Админ</span></a>
    <nav class="main-nav">
      <a href="./index.php" class="nav-link">Админ-панель</a>
      <a href="logout.php" class="nav-link">Выйти</a>
    </nav>
  </div>
</header>

<main class="container">
  <div class="admin-form">
    <h2>Редактировать новость #<?php echo (int)$news['id']; ?></h2>

    <?php if ($ok): ?><div class="notice notice-ok"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice notice-err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <form method="post" action="edit_news.php?id=<?php echo (int)$news['id']; ?>" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

      <label>Заголовок</label>
      <input type="text" name="title" class="input" required maxlength="255" value="<?php echo htmlspecialchars($news['title']); ?>">

      <label>Текст новости</label>
      <textarea name="content" class="input" rows="10" required><?php echo htmlspecialchars($news['content']); ?></textarea>

      <label>Изображение новости</label>
      <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
      <input type="file" name="image" class="input" accept="image/jpeg,image/png,image/gif,image/webp">
      <small style="color:#666;">JPEG, PNG, GIF, WEBP. Макс. 5 МБ.</small>

      <?php if (!empty($news['image']) && file_exists(__DIR__ . '/../uploads/news/' . $news['image'])): ?>
        <div class="image-preview">
          <p style="margin:0 0 6px;font-size:13px;color:#666;">Текущее изображение:</p>
          <img src="../uploads/news/<?php echo htmlspecialchars($news['image']); ?>" alt="Текущее изображение">
        </div>
      <?php endif; ?>

      <div style="margin-top:12px;">
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a class="btn btn-ghost" href="index.php" style="margin-left:8px;">Назад</a>
      </div>
    </form>
  </div>
</main>

<footer class="site-footer"><div class="container">© <?php echo date('Y'); ?> Магазин автозапчастей</div></footer>
</body>
</html>
