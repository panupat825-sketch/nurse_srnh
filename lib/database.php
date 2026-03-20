<?php

function db($cfg, $withoutDatabase = false)
{
    static $connections = array();

    $key = md5(json_encode(array($cfg, $withoutDatabase)));
    if (isset($connections[$key])) {
        return $connections[$key];
    }

    $dbPart = $withoutDatabase ? '' : ';dbname=' . $cfg['name'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $dbPart,
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $connections[$key] = $pdo;
    return $pdo;
}
