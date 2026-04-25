<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

function fail($msg, $detail = '') {
  $resp = ['ok' => false, 'error' => $msg];
  if ($detail !== '') $resp['error_detail'] = $detail; // покажем точную причину
  echo json_encode($resp, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Неверный метод.');
  }

  if (!isset($conn) || !($conn instanceof mysqli)) {
    fail('Нет соединения с базой (config.php).');
  }

  // CSRF
  $csrf = $_POST['csrf'] ?? '';
  $expected = hash_hmac('sha256', session_id(), 'reviews_salt');
  if (!$csrf || !hash_equals($expected, $csrf)) {
    fail('Токен безопасности недействителен. Обновите страницу.');
  }

  // Данные
  $name    = trim((string)($_POST['name'] ?? ''));
  $rating  = (int)($_POST['rating'] ?? 0);
  $message = trim((string)($_POST['message'] ?? ''));

  if ($name === '' || mb_strlen($name) > 100)        fail('Введите корректное имя (до 100 символов).');
  if ($rating < 1 || $rating > 5)                    fail('Некорректная оценка.');
  if ($message === '' || mb_strlen($message) > 1000) fail('Введите текст отзыва (до 1000 символов).');

  // Таблица должна существовать
  $chk = $conn->query("SHOW TABLES LIKE 'reviews'");
  if (!$chk || $chk->num_rows === 0) {
    $sqlHelp =
"CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  rating TINYINT NOT NULL,
  message TEXT NOT NULL,
  is_approved TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    fail('Таблица reviews не найдена.', $sqlHelp);
  }

  // Вставка
  $stmt = $conn->prepare("INSERT INTO reviews (name, rating, message, is_approved, created_at) VALUES (?, ?, ?, 0, NOW())");
  if (!$stmt) fail('Ошибка сервера (prepare).', $conn->error);

  if (!$stmt->bind_param('sis', $name, $rating, $message)) {
    $detail = $stmt->error ?: $conn->error;
    $stmt->close();
    fail('Ошибка сервера (bind).', $detail);
  }

  if (!$stmt->execute()) {
    $detail = $stmt->error ?: $conn->error;
    $stmt->close();
    fail('Ошибка базы данных (execute).', $detail);
  }

  $stmt->close();
  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  fail('Системная ошибка.', $e->getMessage());
} finally {
  if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
  }
}
