<?php
/**
 * ربات تلگرام مدیریتی ALIVANA
 */

// =============== تنظیمات ===============
define('BOT_TOKEN', '8987845796:AAHb16-SliYmq7RhC_rDMB-bwisSg4cwsqE');
define('ADMIN_ID', 8287336107);
// =======================================

define('DATA_DIR', __DIR__ . '/data/');
define('USERS_FILE', DATA_DIR . 'users.json');
define('MESSAGES_FILE', DATA_DIR . 'messages.json');
define('CATEGORIES_FILE', DATA_DIR . 'categories.json');

if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

function readJson($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function writeJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getDefaultCategories() {
    return [
        ['id' => 1, 'name' => '🧠 روانشناسی', 'content' => 'محتوای روانشناسی و توسعه فردی'],
        ['id' => 2, 'name' => '📖 فرهنگی', 'content' => 'محتوای فرهنگی و اجتماعی'],
        ['id' => 3, 'name' => '🤖 هوش مصنوعی', 'content' => 'محتوای مرتبط با AI و تکنولوژی'],
        ['id' => 4, 'name' => '⚽ ورزشی', 'content' => 'محتوای ورزشی و سلامت']
    ];
}

function initData() {
    if (!file_exists(USERS_FILE)) writeJson(USERS_FILE, []);
    if (!file_exists(MESSAGES_FILE)) writeJson(MESSAGES_FILE, []);
    if (!file_exists(CATEGORIES_FILE)) writeJson(CATEGORIES_FILE, getDefaultCategories());
}

function telegram($method, $params = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/$method";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function sendMessage($chatId, $text, $keyboard = null) {
    $params = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($keyboard) {
        $params['reply_markup'] = json_encode($keyboard);
    }
    
    return telegram('sendMessage', $params);
}

function registerUser($user) {
    $users = readJson(USERS_FILE);
    $id = strval($user['id']);
    
    if (!isset($users[$id])) {
        $users[$id] = [
            'id' => $user['id'],
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'username' => $user['username'] ?? '',
            'joined_at' => date('Y-m-d H:i:s'),
            'is_vip' => false
        ];
        writeJson(USERS_FILE, $users);
        
        // اطلاع به ادمین
        $name = $user['first_name'] ?? 'کاربر';
        sendMessage(ADMIN_ID, "👤 <b>کاربر جدید!</b>\n\nنام: $name\nID: <code>{$user['id']}</code>");
    }
    
    return $users[$id];
}

function showMainMenu($chatId) {
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🆘 پشتیبانی', 'callback_data' => 'support']],
            [['text' => '🌐 فضای اجتماعی', 'callback_data' => 'social']],
            [['text' => '📚 دسته‌بندی‌ها', 'callback_data' => 'categories']],
        ]
    ];
    
    if ($chatId == ADMIN_ID) {
        $keyboard['inline_keyboard'][] = [['text' => '⚙️ پنل مدیریت', 'callback_data' => 'admin']];
    }
    
    $users = readJson(USERS_FILE);
    
    $text = "👋 <b>خوش آمدید!</b>\n\n";
    $text .= "🤖 ربات مدیریتی ALIVANA\n";
    $text .= "👥 تعداد اعضا: <b>" . count($users) . "</b>\n\n";
    $text .= "یک گزینه را انتخاب کنید:";
    
    sendMessage($chatId, $text, $keyboard);
}

function showSupportMenu($chatId) {
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '📧 ارسال پیام به مدیر', 'callback_data' => 'send_message']],
            [['text' => '❓ سوالات متداول', 'callback_data' => 'faq']],
            [['text' => '◀️ بازگشت', 'callback_data' => 'back_main']]
        ]
    ];
    
    sendMessage($chatId, "🆘 <b>بخش پشتیبانی</b>\n\nپیام خود را ارسال کنید:", $keyboard);
}

function showSocialMenu($chatId) {
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🎬 کانال ALIVANA_Ai', 'url' => 'https://t.me/ALIVANA_Ai']],
            [['text' => '📖 کانال madare_elm1400', 'url' => 'https://t.me/madare_elm1400']],
            [['text' => '🌐 وبسایت (به زودی)', 'callback_data' => 'coming_soon']],
            [['text' => '◀️ بازگشت', 'callback_data' => 'back_main']]
        ]
    ];
    
    sendMessage($chatId, "🌐 <b>فضای اجتماعی ما</b>\n\nما را دنبال کنید:", $keyboard);
}

function showCategoriesMenu($chatId) {
    $categories = readJson(CATEGORIES_FILE);
    
    $keyboard = ['inline_keyboard' => []];
    foreach ($categories as $cat) {
        $keyboard['inline_keyboard'][] = [['text' => $cat['name'], 'callback_data' => 'cat_' . $cat['id']]];
    }
    $keyboard['inline_keyboard'][] = [['text' => '◀️ بازگشت', 'callback_data' => 'back_main']];
    
    sendMessage($chatId, "📚 <b>دسته‌بندی‌ها</b>\n\nیک موضوع را انتخاب کنید:", $keyboard);
}

function showAdminPanel($chatId) {
    if ($chatId != ADMIN_ID) {
        sendMessage($chatId, "❌ شما دسترسی ندارید!");
        return;
    }
    
    $users = readJson(USERS_FILE);
    $messages = readJson(MESSAGES_FILE);
    $pending = array_filter($messages, fn($m) => ($m['status'] ?? 'pending') === 'pending');
    
    $text = "⚙️ <b>پنل مدیریت</b>\n\n";
    $text .= "👥 کل کاربران: <b>" . count($users) . "</b>\n";
    $text .= "💬 پیام‌های در انتظار: <b>" . count($pending) . "</b>\n";
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '📊 آمار', 'callback_data' => 'admin_stats'], ['text' => '💬 پیام‌ها', 'callback_data' => 'admin_messages']],
            [['text' => '📢 پیام همگانی', 'callback_data' => 'admin_broadcast']],
            [['text' => '👥 لیست کاربران', 'callback_data' => 'admin_users']],
            [['text' => '◀️ بازگشت', 'callback_data' => 'back_main']]
        ]
    ];
    
    sendMessage($chatId, $text, $keyboard);
}

function showAdminMessages($chatId) {
    if ($chatId != ADMIN_ID) return;
    
    $messages = readJson(MESSAGES_FILE);
    $pending = array_filter($messages, fn($m) => ($m['status'] ?? 'pending') === 'pending');
    
    if (empty($pending)) {
        $keyboard = ['inline_keyboard' => [[['text' => '◀️ بازگشت', 'callback_data' => 'admin']]]];
        sendMessage($chatId, "✅ پیام جدیدی وجود ندارد.", $keyboard);
        return;
    }
    
    $text = "💬 <b>پیام‌های دریافتی:</b>\n\n";
    $count = 0;
    
    foreach ($pending as $id => $msg) {
        if ($count >= 5) break;
        $text .= "━━━━━━━━━━━━━━━━\n";
        $text .= "👤 <code>" . $msg['user_id'] . "</code>\n";
        $text .= "📝 " . mb_substr($msg['text'], 0, 100) . "\n";
        $text .= "🆔 <code>$id</code>\n\n";
        $count++;
    }
    
    $text .= "━━━━━━━━━━━━━━━━\n";
    $text .= "📌 برای پاسخ:\n<code>/reply ID پاسخ</code>";
    
    $keyboard = ['inline_keyboard' => [[['text' => '◀️ بازگشت', 'callback_data' => 'admin']]]];
    sendMessage($chatId, $text, $keyboard);
}

function saveSupportMessage($userId, $text, $userName) {
    $messages = readJson(MESSAGES_FILE);
    $id = substr(uniqid(), -6);
    
    $messages[$id] = [
        'user_id' => $userId,
        'user_name' => $userName,
        'text' => $text,
        'time' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];
    
    writeJson(MESSAGES_FILE, $messages);
    
    $adminText = "📩 <b>پیام جدید!</b>\n\n";
    $adminText .= "👤 $userName\n";
    $adminText .= "🆔 <code>$userId</code>\n";
    $adminText .= "📝 $text\n\n";
    $adminText .= "پاسخ: <code>/reply $id متن</code>";
    
    sendMessage(ADMIN_ID, $adminText);
    
    return $id;
}

function replyToMessage($messageId, $replyText) {
    $messages = readJson(MESSAGES_FILE);
    
    if (!isset($messages[$messageId])) return false;
    
    $userId = $messages[$messageId]['user_id'];
    $messages[$messageId]['status'] = 'replied';
    $messages[$messageId]['reply'] = $replyText;
    
    writeJson(MESSAGES_FILE, $messages);
    
    sendMessage($userId, "📨 <b>پاسخ پشتیبانی:</b>\n\n$replyText");
    
    return true;
}

function broadcastMessage($text) {
    $users = readJson(USERS_FILE);
    $sent = 0;
    
    foreach ($users as $user) {
        $result = sendMessage($user['id'], "📢 <b>پیام همگانی:</b>\n\n$text");
        if ($result['ok'] ?? false) $sent++;
        usleep(50000);
    }
    
    return $sent;
}

// =============== پردازش اصلی ===============

initData();

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    die('OK');
}

// پیام متنی
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $userId = $message['from']['id'];
    $text = $message['text'] ?? '';
    $firstName = $message['from']['first_name'] ?? 'کاربر';
    
    registerUser($message['from']);
    
    if ($text === '/start') {
        showMainMenu($chatId);
    }
    elseif ($text === '/admin' && $chatId == ADMIN_ID) {
        showAdminPanel($chatId);
    }
    elseif (strpos($text, '/reply ') === 0 && $chatId == ADMIN_ID) {
        $parts = explode(' ', $text, 3);
        if (count($parts) >= 3) {
            if (replyToMessage($parts[1], $parts[2])) {
                sendMessage($chatId, "✅ پاسخ ارسال شد.");
            } else {
                sendMessage($chatId, "❌ پیام پیدا نشد.");
            }
        }
    }
    elseif (strpos($text, '/broadcast ') === 0 && $chatId == ADMIN_ID) {
        $sent = broadcastMessage(substr($text, 11));
        sendMessage($chatId, "✅ ارسال شد به $sent نفر");
    }
    elseif ($text && !str_starts_with($text, '/')) {
        $id = saveSupportMessage($userId, $text, $firstName);
        sendMessage($chatId, "✅ پیام شما ثبت شد.\n🆔 کد پیگیری: <code>$id</code>");
    }
}

// Callback
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chatId = $callback['from']['id'];
    $data = $callback['data'];
    
    telegram('answerCallbackQuery', ['callback_query_id' => $callback['id']]);
    
    switch ($data) {
        case 'support': showSupportMenu($chatId); break;
        case 'social': showSocialMenu($chatId); break;
        case 'categories': showCategoriesMenu($chatId); break;
        case 'admin': showAdminPanel($chatId); break;
        case 'admin_messages': showAdminMessages($chatId); break;
        case 'back_main': showMainMenu($chatId); break;
        case 'coming_soon': sendMessage($chatId, "🔜 به زودی..."); break;
        case 'send_message': sendMessage($chatId, "📧 پیام خود را بنویسید:"); break;
        
        case 'admin_stats':
            $users = readJson(USERS_FILE);
            $text = "📊 <b>آمار:</b>\n\n👥 کاربران: " . count($users);
            sendMessage($chatId, $text);
            break;
            
        case 'admin_broadcast':
            sendMessage($chatId, "📢 فرمت:\n<code>/broadcast متن پیام</code>");
            break;
            
        case 'admin_users':
            $users = readJson(USERS_FILE);
            $text = "👥 <b>کاربران:</b>\n\n";
            $count = 0;
            foreach ($users as $u) {
                if ($count >= 10) break;
                $text .= "• {$u['first_name']} - <code>{$u['id']}</code>\n";
                $count++;
            }
            sendMessage($chatId, $text);
            break;
            
        case 'faq':
            sendMessage($chatId, "❓ <b>سوالات متداول:</b>\n\n🔹 پاسخگویی: حداکثر ۲۴ ساعت\n🔹 پرداخت: کارت به کارت");
            break;
            
        default:
            if (strpos($data, 'cat_') === 0) {
                $catId = intval(substr($data, 4));
                $categories = readJson(CATEGORIES_FILE);
                foreach ($categories as $cat) {
                    if ($cat['id'] == $catId) {
                        $kb = ['inline_keyboard' => [[['text' => '◀️ بازگشت', 'callback_data' => 'categories']]]];
                        sendMessage($chatId, "📚 <b>{$cat['name']}</b>\n\n{$cat['content']}", $kb);
                        break;
                    }
                }
            }
            break;
    }
}

http_response_code(200);
echo 'OK';
