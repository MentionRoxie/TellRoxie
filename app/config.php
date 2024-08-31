<?php

declare(strict_types=1);

date_default_timezone_set('Iran');

require_once 'Database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database();
$configs = $database->query("SELECT * FROM config")->find();

define('BOT_TOKEN', $_ENV['BOT_TOKEN']);
define('OWNER_ID', $configs['owner_id']);
define('OWNER_USERNAME', $configs['owner_username']);
define('SALT', $_ENV['SALT']);
