<?php
session_start();
require_once __DIR__ . '/config.php';

$pageTitle = 'Название страницы';           // опционально
$extraCss   = ['css/about.css'];            // опционально: стили страницы
require __DIR__ . '/header.php';


// Текущие фильтры
$currentCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$currentSearch   = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Безопасный запрос: не валим страницу, если SQL упал (например, таблицы нет)
function safe_query(mysqli $conn, string $sql) {
  try {
    return $conn->query($sql);
  } catch (Throwable $e) {
    error_log("SQL error: " . $e->getMessage() . " | SQL: " . $sql);
    return false;
  }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Магазин автозапчастей</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="css/common.css" />
  <link rel="stylesheet" href="css/index.css" />

  <!-- Стили отзывов + модалки и краткого описания -->
  <style>
    .reviews { padding: 48px 0; background: #fafafa; }
    .reviews .section-head { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; }
    .reviews-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
    .review-card { background:#fff; border:1px solid #eee; border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:8px; }
    .review-name { font-weight:600; }
    .review-text { color:#333; line-height:1.45; }
    .review-meta { font-size:12px; color:#777; }
    .stars { font-size:14px; letter-spacing:1px; color:#f5a623; }

    /* краткое описание в карточках каталога */
    .product-card .excerpt{margin:.25rem 0 .5rem;color:#444;line-height:1.35}

    /* Modal */
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.4); display:none; align-items:center; justify-content:center; padding:16px; z-index:1000; }
    .modal-backdrop:target { display:flex; }                 /* открытие по якорю (без JS) */
    .modal-backdrop.active { display:flex; }                 /* если JS добавит класс */
    .modal-backdrop:not(:target) { display:none !important; }/* закрывать всегда, если нет якоря */
    .modal { width:100%; max-width:520px; background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.2); }
    .modal-header { padding:16px 20px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between; }
    .modal-title { font-weight:700; }
    .modal-close { border:none; background:transparent; font-size:22px; line-height:1; cursor:pointer; text-decoration:none; }
    .modal-body { padding:20px; display:flex; flex-direction:column; gap:12px; }
    .modal-footer { padding:16px 20px; border-top:1px solid #eee; display:flex; gap:8px; justify-content:flex-end; }
    .input, .textarea, .select { width:100%; border:1px solid #ddd; border-radius:10px; padding:10px 12px; font:inherit; }
    .textarea { min-height:110px; resize:vertical; }
    .btn { cursor:pointer; }
    .btn-secondary { background:#f2f2f2; border:1px solid #e5e5e5; color:#333; border-radius:10px; padding:10px 14px; }
    .alert { padding:10px 12px; border-radius:10px; font-size:14px; }
    .alert-success { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
    .alert-error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
  </style>
</head>
<body>


<section class="search-bar">
  <div class="container">
    <form method="GET" action="index.php" class="search-form">
      <input type="text" name="search" class="input" placeholder="Поиск запчастей..." value="<?php echo $currentSearch; ?>" />
      <button type="submit" class="btn btn-primary">Найти</button>
      <?php if (!empty($currentSearch) || $currentCategory): ?>
        <a href="index.php" class="btn btn-ghost">Сбросить</a>
      <?php endif; ?>
    </form>
  </div>
</section>

<section class="categories">
  <div class="container">
    <h2 class="section-title">Категории</h2>
    <div class="chips">
      <a href="index.php" class="chip <?php echo $currentCategory===0 ? 'active' : ''; ?>">Все</a>
      <?php
      $sqlCats = "SELECT * FROM categories";
      $result = safe_query($conn, $sqlCats);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $active = $currentCategory === (int)$row['id'] ? 'active' : '';
          echo "<a class='chip $active' href='index.php?category={$row['id']}'>".htmlspecialchars($row['name'])."</a>";
        }
      }
      ?>
    </div>
  </div>
</section>

<section class="products">
  <div class="container products-grid">
    <?php
      $where = "";
      if ($currentCategory) {
        $where .= " WHERE p.category_id = " . (int)$currentCategory;
      }
      if (isset($_GET['search']) && $_GET['search'] !== '') {
        $search = $conn->real_escape_string($_GET['search']);
        $where .= ($where ? " AND" : " WHERE") . " p.name LIKE '%$search%'";
      }

      $sqlProd = "SELECT p.*, c.name AS category_name
                  FROM products p
                  JOIN categories c ON p.category_id = c.id" . $where;
      $result = safe_query($conn, $sqlProd);

      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $price = number_format((float)$row['price'], 0, ',', ' ');
          // короткое описание (если заполнено в БД)
          $excerpt = '';
          if (!empty($row['short_description'])) {
            if (function_exists('mb_strimwidth')) {
              $excerpt = htmlspecialchars(mb_strimwidth($row['short_description'], 0, 120, '…', 'UTF-8'));
            } else {
              $excerpt = htmlspecialchars(substr($row['short_description'], 0, 120)) . '…';
            }
          }

          echo "<article class='product-card'>
                  <div class='thumb' aria-hidden='true'></div>
                  <div class='info'>
                    <h3 class='title'><a href='product.php?id={$row['id']}'>".htmlspecialchars($row['name'])."</a></h3>
                    <div class='meta'>Категория: ".htmlspecialchars($row['category_name'])."</div>";
          if ($excerpt) {
            echo "<p class='excerpt'>{$excerpt}</p>";
          }
          echo "    <div class='bottom'>
                      <div class='price'>{$price} ₽</div>
                      <a class='btn btn-success' href='cart.php?action=add&id={$row['id']}'>В корзину</a>
                    </div>
                  </div>
                </article>";
        }
      } else {
        echo "<div class='empty-state'>
                <div class='empty-icon'>🔍</div>
                <div class='empty-title'>Товары не найдены</div>
                <div class='empty-text'>Попробуйте изменить запрос или выбрать другую категорию.</div>
              </div>";
      }
    ?>
  </div>
</section>

<!-- ========== ОТЗЫВЫ КЛИЕНТОВ ========== -->
<section class="reviews">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title">Отзывы клиентов</h2>
      <!-- Якорь: откроет модалку даже без JS -->
      <a href="#reviewModalBackdrop" class="btn btn-primary" id="openReviewModal">Оставить отзыв</a>
    </div>

    <div class="reviews-grid">
      <?php
        $revSql = "SELECT name, rating, message, created_at
                   FROM reviews
                   WHERE is_approved = 1
                   ORDER BY created_at DESC
                   LIMIT 6";
        $revRes = safe_query($conn, $revSql);

        if ($revRes && $revRes->num_rows > 0) {
          while ($r = $revRes->fetch_assoc()) {
            $name = htmlspecialchars($r['name']);
            $rating = max(1, min(5, (int)$r['rating']));
            $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            $msg = nl2br(htmlspecialchars($r['message']));
            $date = date('d.m.Y', strtotime($r['created_at']));
            echo "<article class='review-card'>
                    <div class='stars' aria-label='Оценка: {$rating} из 5'>{$stars}</div>
                    <div class='review-text'>{$msg}</div>
                    <div class='review-name'>— {$name}</div>
                    <div class='review-meta'>{$date}</div>
                  </article>";
          }
        } else {
          echo "<div class='empty-state'>
                  <div class='empty-icon'>💬</div>
                  <div class='empty-title'>Пока нет отзывов</div>
                  <div class='empty-text'>Будьте первым! Нажмите «Оставить отзыв».</div>
                </div>";
        }
      ?>
    </div>
  </div>
</section>
<!-- ========== /ОТЗЫВЫ КЛИЕНТОВ ========== -->

<footer class="site-footer">
  <div class="container">
    © <?php echo date("Y"); ?> Магазин автозапчастей
  </div>
</footer>

<?php if (isset($conn) && $conn instanceof mysqli) { $conn->close(); } ?>

<!-- МОДАЛКА -->
<div class="modal-backdrop" id="reviewModalBackdrop" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle">
    <div class="modal-header">
      <div class="modal-title" id="reviewModalTitle">Оставить отзыв</div>
      <!-- Ссылка на # (снимет якорь => закроет окно без JS) -->
      <a href="#" class="modal-close" id="closeReviewModal" aria-label="Закрыть">×</a>
    </div>
    <form id="reviewForm" class="modal-body" method="post" action="reviews_submit.php" novalidate>
      <input type="hidden" name="csrf" value="<?php echo hash_hmac('sha256', session_id(), 'reviews_salt'); ?>">
      <label>
        Имя
        <input class="input" type="text" name="name" maxlength="100" required placeholder="Ваше имя" />
      </label>
      <label>
        Оценка
        <select class="select" name="rating" required>
          <option value="5">5 — Отлично</option>
          <option value="4">4 — Хорошо</option>
          <option value="3">3 — Нормально</option>
          <option value="2">2 — Плохо</option>
          <option value="1">1 — Очень плохо</option>
        </select>
      </label>
      <label>
        Отзыв
        <textarea class="textarea" name="message" maxlength="1000" required placeholder="Поделитесь опытом покупки..."></textarea>
      </label>
      <div id="reviewAlert" style="display:none"></div>
      <div class="modal-footer">
        <a href="#" class="btn btn-secondary" id="cancelReview">Отмена</a>
        <button type="submit" class="btn btn-primary" id="submitReview">Отправить</button>
      </div>
    </form>
  </div>
</div>

<!-- JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const backdrop  = document.getElementById('reviewModalBackdrop');
  const closeBtn  = document.getElementById('closeReviewModal');
  const cancelBtn = document.getElementById('cancelReview');
  const form      = document.getElementById('reviewForm');
  const alertBox  = document.getElementById('reviewAlert');

  function closeModal() {
    // убираем JS-признаки открытия
    if (backdrop) {
      backdrop.classList.remove('active');
      backdrop.setAttribute('aria-hidden', 'true');
    }
    if (alertBox) alertBox.style.display = 'none';

    // надёжно сбрасываем :target
    if (location.hash === '#reviewModalBackdrop') {
      try {
        location.hash = '#_'; // переключаемся на пустую цель => :target снимается
        history.replaceState(null, '', location.pathname + location.search); // чистим URL
      } catch (e) {
        // Фолбэк: временно убираем id
        if (backdrop) {
          const oldId = backdrop.id;
          backdrop.removeAttribute('id');
          setTimeout(() => { backdrop.id = oldId; }, 0);
        }
      }
    }
  }

  // Клики закрытия
  if (closeBtn)  closeBtn.addEventListener('click',  (e) => { e.preventDefault(); closeModal(); });
  if (cancelBtn) cancelBtn.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });

  // Клик по фону
  if (backdrop) backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closeModal(); });

  // ESC
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  // Отправка формы (AJAX)
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (alertBox) alertBox.style.display = 'none';

      try {
        const res  = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        const data = await res.json();

        if (data.ok) {
          if (alertBox) {
            alertBox.className = 'alert alert-success';
            alertBox.textContent = 'Спасибо! Отзыв отправлен и появится после модерации.';
            alertBox.style.display = 'block';
          }
          form.reset();
          setTimeout(() => { closeModal(); location.reload(); }, 800);
        } else {
          if (alertBox) {
            alertBox.className = 'alert alert-error';
            alertBox.textContent = (data.error_detail ? (data.error + ' — ' + data.error_detail) : data.error) || 'Не удалось отправить отзыв.';
            alertBox.style.display = 'block';
          }
        }
      } catch {
        if (alertBox) {
          alertBox.className = 'alert alert-error';
          alertBox.textContent = 'Ошибка сети. Попробуйте ещё раз.';
          alertBox.style.display = 'block';
        }
      }
    });
  }
});
</script>

</body>
</html>
