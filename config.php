<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('BOT_TOKEN', $_ENV['BOT_TOKEN']);
define('GITHUB_TOKEN', $_ENV['GITHUB_TOKEN']);

define('GITHUB_OWNER', $_ENV['GITHUB_OWNER']);
define('GITHUB_REPO', $_ENV['GITHUB_REPO']);
define('GITHUB_BRANCH', $_ENV['GITHUB_BRANCH']);

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);