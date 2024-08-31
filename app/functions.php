<?php

declare(strict_types=1);

/**
 * Bot start process
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @return void Nothing returns
 */
function startBot(Telegram $telegram, string|int $chatId): void
{
    $options = [
        [
            $telegram->buildInlineKeyboardButton(
                text: 'Send Message ✉️',
                callback_data: 'sendMessage'
            )
        ],
        [
            $telegram->buildInlineKeyboardButton(
                text: "Bot source code 🤖",
                callback_data: 'botSource'
            )
        ]
    ];

    $keyboard = $telegram->buildInlineKeyBoard($options);

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'reply_markup' => $keyboard,
        'text' => "Hi 👋🏻, Welcome to TellRoxie bot 💖\n\nWith the button below you can send an anonymous message to @" . OWNER_USERNAME . " 💫\n\nDon't worry 😉, He won't know your identity!"
    ]);
}

/**
 * Cancel process
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @return void Nothing returns
 */
function cancelProcess(Telegram $telegram, string|int $chatId): void
{
    $telegram->sendMessage([
        'text' => "Fine, then... 😌\n\nIf you want to send a message again, just write /send_message.",
        'chat_id' => $chatId
    ]);
}

/**
 * Send message command
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @return void Nothing returns
 */
function sendMessageCommand(Telegram $telegram, string|int $chatId): void
{
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Send your message... ✍🏻\n\nOr... /cancel to cancel the process.",
    ]);
}

/**
 * Send message callback
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @param array $data Telegram's update data
 * @return void Nothing returns
 */
function sendMessageCallback(Telegram $telegram, string|int $chatId, array $data): void
{
    $telegram->editMessageText([
        'chat_id' => $chatId,
        'text' => "Now, send your message... ✍🏻\n\nOr... /cancel to cancel the process.",
        'message_id' => $data['callback_query']['message']
    ]);
}

/**
 * Sends the robot's Github repository
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @return void Nothing returns
 */
function sendBotSource(Telegram $telegram, string|int $chatId): void
{
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "This bot is open-source ✅\n\nYou can find the bot's source code here: https://github.com/MentionRoxie/TellRoxie 🤖\n\nFeel free to contribute or report bugs 🙂",
    ]);

    exit;
}

/**
 * Triggers when user sends an anonymous message
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @param string $hashedChatId Hashed chat id
 * @param array $data Telegram's update data
 * @return void
 */
function messageSent(Telegram $telegram, string|int $chatId, array $data): void
{
    $types = [
        'sticker',
        'message',
        'animation',
        'voice',
        'photo',
        'video'
    ];
    foreach ($types as $type) {
        if ($telegram->getUpdateType() === $type) {
            $value = $type === 'message' ? $data['message']['text'] : $data['message'][$type]['file_id'];
            break;
        }
    }

    $pdo = new PDO("mysql:host=localhost;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $stmt = $pdo->prepare("INSERT INTO messages_to_send (hashed_id, value, type) VALUES (?, ?, ?)");
    $stmt->execute([hashChatId($chatId), $value, $telegram->getUpdateType()]);

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Send /done if you're done with writing... ✅\n\nOr... /cancel to cancel the process."
    ]);
}

/**
 * Send the message anonymously to bot owner
 * @param Telegram $telegram Instance of Telegram class
 * @param string|int $chatId User's chat id
 * @return void Nothing returns
 */
function sendMessageAnonymously(Telegram $telegram, Database $database, string|int $chatId): void
{
    $messageToSend = $database->query("SELECT * FROM messages_to_send WHERE hashed_id = ?", [hashChatId($chatId)])->find();

    $types = [
        'sticker',
        'message',
        'animation',
        'voice',
        'photo',
        'video'
    ];
    foreach ($types as $type) {
        if ($messageToSend['type'] === 'message') {
            $telegram->{"send{$type}"}([
                'chat_id' => OWNER_ID,
                'text' => $messageToSend['value']
            ]);
        } elseif ($messageToSend['type'] === $type) {
            $telegram->{"send{$type}"}([
                'chat_id' => OWNER_ID,
                $type => $messageToSend['value']
            ]);
        }
    }

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => "Your message has been sent anonymously! 💌\n\nIf you want to send a message again, just write /send_message."
    ]);
}

/**
 * Hashes the chat id
 * @param string|int $chatId User's chat id
 * @return string Hashed chat id
 */
function hashChatId(string|int $chatId): string
{
    return hash('sha256', (string) $chatId . SALT);
}

/**
 * Set user's current state
 * @param string $hashedId Hashed chat id
 * @param string $state The state to set
 * @return void Nothing returns
 */
function setUserState(Database $database, string $hashedId, string $state): void
{
    // Check if the user is already in the database
    $user = getUserState($database, $hashedId);
    if ($user)
        clearUserState($database, $hashedId);

    $database->query(
        "INSERT INTO `user_states` (`hashed_id`, `state`) VALUES (?, ?)",
        [
            $hashedId,
            $state
        ]
    );
}

/**
 * Get user's current state
 * @param string $hashedId Hashed chat id
 * @return mixed User's state
 */
function getUserState(Database $database, string $hashedId): array|bool
{
    return $database->query(
        "SELECT `state` FROM `user_states` WHERE `hashed_id` = ?",
        [$hashedId]
    )->find();
}

/**
 * Clear user's current state
 * @param string $hashedId Hashed chat id
 * @return void Nothing returns
 */
function clearUserState(Database $database, string $hashedId): void
{
    $database->query("DELETE FROM `user_states` WHERE `hashed_id` = ?", [$hashedId]);
}

/**
 * Clear messages to send
 * @param string $hashedId Hashed chat id
 * @return void Nothing returns
 */
function clearMessages(Database $database, string $hashedId): void
{
    $database->query("DELETE FROM `messages_to_send` WHERE `hashed_id` = ?", [$hashedId]);
}
