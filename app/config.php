<?php

declare(strict_types=1);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('BOT_TOKEN', $_ENV['BOT_TOKEN']);
define('OWNER_ID', $_ENV['OWNER_ID']);
define('SALT', $_ENV['SALT']);
