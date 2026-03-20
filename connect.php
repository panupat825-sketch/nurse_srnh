<?php

$config = require __DIR__ . '/config.php';
$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

$db = $config['db'];
$conn = @new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], (int) $db['port']);

if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');
}

?>
