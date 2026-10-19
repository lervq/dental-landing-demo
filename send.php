<?php
// Обработчик заявок с сайта: пересылает их в Telegram.
//
// Настройка (5 минут):
// 1. В Telegram напишите @BotFather → /newbot → получите токен бота.
// 2. Напишите своему боту любое сообщение (или добавьте его в чат администраторов клиники).
// 3. Откройте в браузере https://api.telegram.org/bot<ТОКЕН>/getUpdates и найдите "chat":{"id": ...}.
// 4. Впишите токен и chat id ниже, а в index.html поставьте DEMO_MODE = false.

const TG_TOKEN   = '';
const TG_CHAT_ID = '';

header('Content-Type: application/json; charset=utf-8');

function reply(bool $ok, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(false, 405);

// Скрытое поле-ловушка: люди его не видят, а спам-боты заполняют
if (!empty($_POST['website'])) reply(true);

$field = fn(string $key) => trim(mb_substr((string)($_POST[$key] ?? ''), 0, 200));

$phone = $field('phone');
if (strlen(preg_replace('/\D/', '', $phone)) !== 11) reply(false, 422);
if ($field('consent') !== 'да') reply(false, 422);

$labels = [
    'name'     => 'Имя',
    'phone'    => 'Телефон',
    'problem'  => 'Беспокоит',
    'teeth'    => 'Зубов',
    'when'     => 'Когда прийти',
    'gift'     => 'Подарок',
    'estimate' => 'Расчёт',
    'source'   => 'Откуда',
];

$lines = ['🦷 <b>Новая заявка с сайта</b>', ''];
foreach ($labels as $key => $label) {
    $value = $field($key);
    if ($value !== '') $lines[] = "<b>{$label}:</b> " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$ch = curl_init('https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'chat_id'    => TG_CHAT_ID,
        'text'       => implode("\n", $lines),
        'parse_mode' => 'HTML',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

reply($response !== false && $status === 200, $status === 200 ? 200 : 502);
