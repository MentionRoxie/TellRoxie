<?php

/**
 * This file is going to clear the database after 1 week
 */

declare(strict_types=1);

require_once '../vendor/autoload.php';

$database = new Database();

$database->query("DELETE FROM `user_states` WHERE `created_at` < NOW() - INTERVAL 1 WEEK");
$database->query("DELETE FROM `messages_to_send` WHERE `created_at` < NOW() - INTERVAL 1 WEEK");
