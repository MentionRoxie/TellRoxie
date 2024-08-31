<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';

$database = new Database();
$telegram = new Telegram(BOT_TOKEN);

$data = $telegram->getData();
$chatId = $telegram->ChatID();
$hashedChatId = hashChatId($chatId);
$text = $telegram->Text();

$userState = getUserState($database, $hashedChatId);

if ($text === '/start') {
    startBot($telegram, $chatId);

    clearMessages($database, $hashedChatId);

    exit;
}

if ($text === '/cancel') {
    cancelProcess($telegram, $chatId);

    clearUserState($database, $hashedChatId);
    clearMessages($database, $hashedChatId);
    exit;
}

if ($text === '/send_message') {
    sendMessageCommand($telegram, $chatId);

    setUserState($database, $hashedChatId, 'sendingMessage');
    clearMessages($database, $hashedChatId);

    exit;
}

if ($text === 'sendMessage') {
    sendMessageCallback($telegram, $chatId, $data);

    setUserState($database, $hashedChatId, 'sendingMessage');
    clearMessages($database, $hashedChatId);

    exit;
}

if ($text === 'botSource') {
    sendBotSource($telegram, $chatId);

    setUserState($database, $hashedChatId, 'sendingMessage');
    clearMessages($database, $hashedChatId);

    exit;
}

if ($userState['state'] === 'sendingMessage') {
    messageSent($telegram, $chatId, $data);

    setUserState($database, $hashedChatId, 'messageSent');

    exit;
}

if ($text === '/done' && $userState['state'] === 'messageSent') {
    sendMessageAnonymously($telegram, $database, $chatId);

    clearUserState($database, $hashedChatId);
    clearMessages($database, $hashedChatId);

    exit;
}

$telegram->sendMessage([
    'chat_id' => $chatId,
    'text' => "I'm not sure what you want to do... 🤔, to send a message just write: /send_message"
]);
