<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['security']['session_name']);
    session_start();
}

require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/helpers.php';

$db = db($config['db']);
