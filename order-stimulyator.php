<?php
/**
 * Обработчик заказов для лендинга "Жидкрокристаллический Стимулятор"
 * Отправляет данные заказа на email vegga08@gmail.com
 */

// Разрешаем CORS запросы, если это необходимо
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Only POST requests allowed"]);
    exit;
}

// Попытка подключить ядро WordPress для использования wp_mail (если файл лежит в корне WP)
$wp_loaded = false;
$wp_load_path = __DIR__ . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    $wp_loaded = true;
}

// Получаем данные запроса (поддерживаем как JSON, так и обычный POST)
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

if (!$data) {
    $data = $_POST;
}

// Валидация обязательных полей
$name = isset($data['name']) ? trim(strip_tags($data['name'])) : '';
$phone = isset($data['phone']) ? trim(strip_tags($data['phone'])) : '';
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
$delivery_method = isset($data['delivery_method']) ? trim(strip_tags($data['delivery_method'])) : '';
$city = isset($data['city']) ? trim(strip_tags($data['city'])) : '';
$warehouse = isset($data['warehouse']) ? trim(strip_tags($data['warehouse'])) : '';
$address = isset($data['address']) ? trim(strip_tags($data['address'])) : '';
$comment = isset($data['comment']) ? trim(strip_tags($data['comment'])) : '';
$lang = isset($data['lang']) ? strtoupper(trim(strip_tags($data['lang']))) : 'UA';
$payment_method = isset($data['payment_method']) ? trim(strip_tags($data['payment_method'])) : '';

if (empty($name) || empty($phone)) {
    echo json_encode(["success" => false, "message" => "Имя и Телефон обязательны для заполнения / Name and Phone are required"]);
    exit;
}

$price_per_item = 500;
$total_price = $quantity * $price_per_item;

// Формируем тему письма
$subject = "Новое заказ: Стимулятор [Кол-во: {$quantity} шт, Сумма: {$total_price} грн]";

// Способ доставки на понятном языке
$delivery_text = "";
if ($delivery_method === 'warehouse') {
    $delivery_text = "Новая Почта (Отделение)";
} elseif ($delivery_method === 'postomat') {
    $delivery_text = "Новая Почта (Почтомат)";
} elseif ($delivery_method === 'address') {
    $delivery_text = "Новая Почта (Адресная доставка)";
} else {
    $delivery_text = "Не указан (" . htmlspecialchars($delivery_method) . ")";
}

// Способ оплаты на понятном языке
$payment_text = "";
if ($payment_method === 'cod') {
    $payment_text = "Оплата при получении (наложенный платеж)";
} elseif ($payment_method === 'card') {
    $payment_text = "Оплата на карту (предоплата)";
} else {
    $payment_text = "Не указан (" . htmlspecialchars($payment_method) . ")";
}

// Формируем HTML-содержимое письма
$message = "
<html>
<head>
    <title>Новый заказ с сайта</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f8fafc; }
        h2 { color: #1e3a8a; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #edf2f7; font-weight: bold; width: 35%; }
        .total { font-size: 18px; font-weight: bold; color: #10b981; }
        .footer { margin-top: 20px; font-size: 12px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>Детали заказа: Жидкокристаллический Стимулятор</h2>
        <table>
            <tr>
                <th>Имя клиента:</th>
                <td>" . htmlspecialchars($name) . "</td>
            </tr>
            <tr>
                <th>Телефон:</th>
                <td>" . htmlspecialchars($phone) . "</td>
            </tr>
            <tr>
                <th>Количество:</th>
                <td>" . htmlspecialchars($quantity) . " шт.</td>
            </tr>
            <tr>
                <th>Сумма к оплате:</th>
                <td class='total'>" . htmlspecialchars($total_price) . " грн.</td>
            </tr>
            <tr>
                <th>Способ доставки:</th>
                <td>" . htmlspecialchars($delivery_text) . "</td>
            </tr>
            <tr>
                <th>Способ оплаты:</th>
                <td>" . htmlspecialchars($payment_text) . "</td>
            </tr>
            <tr>
                <th>Населенный пункт:</th>
                <td>" . htmlspecialchars($city) . "</td>
            </tr>
";

if ($delivery_method === 'address') {
    $message .= "
            <tr>
                <th>Адрес доставки:</th>
                <td>" . htmlspecialchars($address) . "</td>
            </tr>
    ";
} else {
    $message .= "
            <tr>
                <th>Отделение / Почтомат:</th>
                <td>" . htmlspecialchars($warehouse) . "</td>
            </tr>
    ";
}

$message .= "
            <tr>
                <th>Комментарий:</th>
                <td>" . nl2br(htmlspecialchars($comment)) . "</td>
            </tr>
            <tr>
                <th>Язык страницы:</th>
                <td>" . htmlspecialchars($lang) . "</td>
            </tr>
            <tr>
                <th>Дата заказа:</th>
                <td>" . date('d.m.Y H:i:s') . "</td>
            </tr>
        </table>
        <div class='footer'>
            Письмо отправлено автоматически с сайта gorbunok.kiev.ua
        </div>
    </div>
</body>
</html>
";

// Заголовки письма для HTML-формата
$to = 'vegga08@gmail.com';
$headers = array();
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=utf-8';
$headers[] = 'From: Gorbunok Site <info@gorbunok.kiev.ua>';

$mail_sent = false;

if ($wp_loaded) {
    // Используем встроенный wp_mail, если WordPress подключен
    $mail_sent = wp_mail($to, $subject, $message, $headers);
} else {
    // В противном случае используем стандартную функцию PHP mail()
    $headers_string = implode("\r\n", $headers);
    $mail_sent = mail($to, $subject, $message, $headers_string);
}

// Резервное логирование заказа в защищенный файл
try {
    $log_file = __DIR__ . '/.orders_db_log.php';
    $log_entry = "";
    if (!file_exists($log_file)) {
        $log_entry .= "<?php die('Access Denied'); ?>\n";
        $log_entry .= "=== РЕЗЕРВНАЯ БАЗА ЗАКАЗОВ ===\n\n";
    }

    $log_entry .= "[" . date('Y-m-d H:i:s') . "] Новый заказ:\n";
    $log_entry .= "Имя: " . $name . "\n";
    $log_entry .= "Телефон: " . $phone . "\n";
    $log_entry .= "Количество: " . $quantity . " шт.\n";
    $log_entry .= "Сумма: " . $total_price . " грн.\n";
    $log_entry .= "Доставка: " . $delivery_text . "\n";
    if ($delivery_method === 'address') {
        $log_entry .= "Адрес: " . $address . "\n";
    } else {
        $log_entry .= "Отделение/Почтомат: " . $warehouse . "\n";
    }
    $log_entry .= "Оплата: " . $payment_text . "\n";
    $log_entry .= "Город: " . $city . "\n";
    $log_entry .= "Комментарий: " . str_replace("\n", " ", $comment) . "\n";
    $log_entry .= "Язык: " . $lang . "\n";
    $log_entry .= "IP клиента: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown') . "\n";
    $log_entry .= "Статус отправки почты: " . ($mail_sent ? "Успешно" : "Ошибка") . "\n";
    $log_entry .= "--------------------------------------------------\n";

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    // Игнорируем ошибки логирования, чтобы не прерывать оформление заказа для пользователя
}

if ($mail_sent) {
    echo json_encode(["success" => true, "message" => "Заказ успешно оформлен! Наш менеджер свяжется с вами в ближайшее время."]);
} else {
    // Если письмо не отправилось, но заказ успешно записан в резервную базу, мы все равно можем вернуть success = true, 
    // чтобы пользователь не видел пугающих ошибок, ведь владелец сайта получит его из лога!
    // Однако, чтобы владелец знал о сбоях, мы пишем статус отправки почты в лог. 
    // Для пользователя мы вернем успех, так как его заказ фактически сохранен на сервере в резервной базе.
    echo json_encode(["success" => true, "message" => "Заказ успешно оформлен! Наш менеджер свяжется с вами в ближайшее время.", "mail_error" => true]);
}

