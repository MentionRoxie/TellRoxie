<?php

declare(strict_types=1);

date_default_timezone_set('Iran');

require_once '../vendor/autoload.php';

$telegram = new Telegram(BOT_TOKEN);

$data = $telegram->getData();
$chatId = $telegram->ChatID();
$hashedChatId = hashChatId($chatId);
$text = $telegram->Text();
$userState = getUserState($hashedChatId);

if ($text === '/start') {
    startBot($telegram, $chatId);

    setUserState($hashedChatId, 'started');
    clearMessages($hashedChatId);

    exit;
}

if ($text === '/cancel') {
    cancelProcess($telegram, $chatId);

    clearUserState($hashedChatId);
    clearMessages($hashedChatId);
    exit;
}

if ($text === '/send_message') {
    sendMessageCommand($telegram, $chatId);

    setUserState($chatId, 'sendingMessage');
    clearMessages($chatId);

    exit;
}

if ($text === 'sendMessage') {
    sendMessageCallback($telegram, $chatId, $data);

    setUserState($hashedChatId, 'sendingMessage');
    clearMessages($hashedChatId);

    exit;
}

if ($userState['state'] === 'sendingMessage') {
    messageSent($telegram, $chatId, $data);

    setUserState($hashedChatId, 'messageSent');

    exit;
}

if ($text === '/done' && $userState['state'] === 'messageSent') {
    sendMessageAnonymously($telegram, $chatId);

    clearUserState($hashedChatId);
    clearMessages($hashedChatId);

    exit;
}

$telegram->sendMessage([
    'chat_id' => $chatId,
    'text' => "I'm not sure what you want to do... 🤔, start the bot to perform an action: /start"
]);
