<?php
session_start();

$pageTitle = 'ИИ-диагностика неисправности';
require __DIR__ . '/header.php';

$problem  = trim($_POST['problem'] ?? '');
$answer   = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $problem !== '') {
    if (mb_strlen($problem) > 1500) {
        $error = 'Слишком длинное описание (макс. 1500 символов).';
    } else {
        $token = getenv('HF_API_TOKEN') ?: '';
        if ($token === '') {
            $error = 'Не задан HF_API_TOKEN на сервере.';
        } else {
            $model = getenv('HF_MODEL') ?: 'meta-llama/Llama-3.1-8B-Instruct:novita';

            $system = "Ты — опытный автомеханик-консультант магазина автозапчастей. " .
                "По описанию проблемы кратко и по-русски укажи: " .
                "1) вероятные причины (списком), " .
                "2) возможные решения и какие запчасти могут понадобиться, " .
                "3) обязательно в конце добавь рекомендацию обратиться к мастеру для точной диагностики. " .
                "Не давай медицинских советов. Отвечай структурно с подзаголовками.";

            $payload = json_encode([
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $problem],
                ],
                'max_tokens'  => 600,
                'temperature' => 0.3,
            ], JSON_UNESCAPED_UNICODE);

            $ch = curl_init('https://router.huggingface.co/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($resp === false) {
                $error = 'Ошибка соединения с ИИ: ' . htmlspecialchars($cerr);
            } elseif ($code >= 400) {
                $error = 'ИИ вернул ошибку (HTTP ' . $code . '). ' . htmlspecialchars(mb_substr($resp, 0, 300));
            } else {
                $data = json_decode($resp, true);
                $answer = $data['choices'][0]['message']['content']
                       ?? $data[0]['generated_text']
                       ?? '';
                if ($answer === '') {
                    $error = 'Пустой ответ от модели.';
                }
            }
        }
    }
}
?>
<style>
  .ai-wrap { max-width: 820px; margin: 32px auto; padding: 0 16px; }
  .ai-card { background:#fff; border:1px solid #eee; border-radius:14px; padding:20px; box-shadow:0 6px 18px rgba(0,0,0,.05); }
  .ai-card h1 { margin:0 0 8px; font-size:24px; }
  .ai-card p.lead { color:#555; margin:0 0 16px; }
  .ai-form textarea { width:100%; min-height:140px; padding:12px; border:1px solid #ddd; border-radius:10px; font:inherit; resize:vertical; }
  .ai-form .btn { margin-top:12px; padding:10px 18px; border:none; background:#1f6feb; color:#fff; border-radius:10px; font-weight:600; cursor:pointer; }
  .ai-form .btn:hover { background:#1857c1; }
  .ai-answer { margin-top:20px; padding:16px; background:#f7faff; border:1px solid #d8e6ff; border-radius:12px; white-space:pre-wrap; line-height:1.5; }
  .ai-error  { margin-top:20px; padding:12px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:10px; }
  .ai-note   { margin-top:14px; font-size:13px; color:#777; }
</style>

<main class="ai-wrap">
  <div class="ai-card">
    <h1>🔧 ИИ-диагностика неисправности</h1>
    <p class="lead">Опишите симптомы вашего автомобиля — ИИ подскажет вероятные причины и возможные решения.</p>

    <form class="ai-form" method="post" action="ai_diagnose.php">
      <textarea name="problem" maxlength="1500" required
        placeholder="Например: при запуске двигателя слышен металлический стук, мощность упала, расход масла вырос..."><?= htmlspecialchars($problem) ?></textarea>
      <button type="submit" class="btn">Проанализировать</button>
    </form>

    <?php if ($error): ?>
      <div class="ai-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($answer): ?>
      <div class="ai-answer"><?= nl2br(htmlspecialchars($answer)) ?></div>
    <?php endif; ?>

    <p class="ai-note">⚠️ Ответы ИИ носят рекомендательный характер. Для точной диагностики обратитесь к квалифицированному мастеру.</p>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
