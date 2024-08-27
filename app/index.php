<?php

declare(strict_types=1);

date_default_timezone_set('Iran');

require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('BOT_TOKEN', $_ENV['BOT_TOKEN']);
define('OWNER_ID', $_ENV['OWNER_ID']);

$telegram = new Telegram(BOT_TOKEN);

$data = $telegram->getData();
$chatId = $telegram->ChatID();
$text = $telegram->Text();

$userState = getUserState(hashUserId($chatId));

if ($text === '/start') {
    startBot($telegram, $chatId);

    setUserState(hashUserId($chatId), 'started');
    clearMessages(hashUserId($chatId));

    exit;
}

if ($text === '/cancel') {
    $telegram->sendMessage([
        'text' => "Fine, then... 😌\n\nIf you want to send a message again, just write /send_message.",
        'chat_id' => $chatId
    ]);

    clearUserState(hashUserId($chatId));
    clearMessages(hashUserId($chatId));

    exit;
}

if ($text === '/send_message') {
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Send your message... ✍🏻\n\nOr... /cancel to cancel the process.",
    ]);

    setUserState(hashUserId($chatId), 'sendingMessage');
    clearMessages(hashUserId($chatId));

    exit;
}

if ($text === 'sendMessage') {
    $telegram->editMessageText([
        'chat_id' => $chatId,
        'text' => "Now, send your message... ✍🏻\n\nOr... /cancel to cancel the process.",
        'message_id' => $data['callback_query']['message']
    ]);

    setUserState(hashUserId($chatId), 'sendingMessage');
    clearMessages(hashUserId($chatId));

    exit;
}

if ($userState['state'] === 'sendingMessage') {
    $hashedId = hashUserId($chatId);

    if ($telegram->getUpdateType() === 'sticker') {
        $value = $data['message']['sticker']['file_id'];
    } elseif ($telegram->getUpdateType() === 'message') {
        $value = $text;
    } elseif ($telegram->getUpdateType() === 'animation') {
        $value = $data['message']['animation']['file_id'];
    } elseif ($telegram->getUpdateType() === 'voice') {
        $value = $data['message']['voice']['file_id'];
    } elseif ($telegram->getUpdateType() === 'photo') {
        $value = $data['message']['photo']['file_id'];
    } elseif ($telegram->getUpdateType() === 'video') {
        $value = $data['message']['video']['file_id'];
    }

    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("INSERT INTO messages_to_send (hashed_id, value, type) VALUES (?, ?, ?)");
    $stmt->execute([$hashedId, $value, $telegram->getUpdateType()]);

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Send /done if you're done with writing... ✅\n\nOr... /cancel to cancel the process."
    ]);

    setUserState(hashUserId($chatId), 'messageSent');

    exit;
}

if ($text === '/done' && $userState['state'] === 'messageSent') {
    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("SELECT * FROM messages_to_send WHERE hashed_id = ?");
    $stmt->execute([hashUserId($chatId)]);

    $messageToSend = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($messageToSend['type'] === 'sticker') {
        $telegram->sendSticker([
            'chat_id' => OWNER_ID,
            'sticker' => $messageToSend['value']
        ]);
    } elseif ($messageToSend['type'] === 'message') {
        $telegram->sendMessage([
            'chat_id' => OWNER_ID,
            'text' => $messageToSend['value']
        ]);
    } elseif ($messageToSend['type'] === 'animation') {
        $telegram->sendAnimation([
            'chat_id' => OWNER_ID,
            'animation' => $messageToSend['value']
        ]);
    } elseif ($messageToSend['type'] === 'voice') {
        $telegram->sendVoice([
            'chat_id' => OWNER_ID,
            'voice' => $messageToSend['value']
        ]);
    } elseif ($messageToSend['type'] === 'photo') {
        $telegram->sendPhoto([
            'chat_id' => OWNER_ID,
            'photo' => $messageToSend['value']
        ]);
    } elseif ($messageToSend['type'] === 'video') {
        $telegram->sendVideo([
            'chat_id' => OWNER_ID,
            'video' => $messageToSend['value']
        ]);
    }

    clearUserState(hashUserId($chatId));
    clearMessages(hashUserId($chatId));

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Your message has been sent anonymously! 💌\n\nIf you want to send a message again, just write /send_message."
    ]);

    exit;
}

$telegram->sendMessage([
    'chat_id' => $chatId,
    'text' => "I'm not sure what you want to do... 🤔, start the bot to perform an action: /start"
]);

function startBot(Telegram $telegram, string|int $chatId)
{
    $options = [
        [
            $telegram->buildInlineKeyboardButton(
                text: 'Send Message ✉️',
                callback_data: 'sendMessage'
            )
        ]
    ];

    $keyboard = $telegram->buildInlineKeyBoard($options);

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'reply_markup' => $keyboard,
        'text' => "Hi 👋🏻, Welcome to TellRoxie bot 💖\n\nWith the button below you can send an anonymous message to @TheRoxieRoxy 💫\n\nDon't worry 😉, He won't know your identity!"
    ]);
}

function hashUserId(string|int $userId)
{
    return hash('sha256', (string) $userId);
}

function setUserState(string $hashedId, string $state)
{
    $user = getUserState($hashedId);
    if ($user)
        clearUserState($hashedId);

    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("INSERT INTO `user_states` (`hashed_id`, `state`) VALUES (?, ?)");
    $stmt->execute([$hashedId, $state]);
}

function getUserState(string $hashedId)
{
    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("SELECT `state` FROM `user_states` WHERE `hashed_id` = ?");
    $stmt->execute([$hashedId]);

    return $stmt->fetch();
}

function clearUserState(string $hashedId)
{
    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("DELETE FROM `user_states` WHERE `hashed_id` = ?");

    $stmt->execute([$hashedId]);
}

function clearMessages(string $hashedId)
{
    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("DELETE FROM `messages_to_send` WHERE `hashed_id` = ?");

    $stmt->execute([$hashedId]);
}
