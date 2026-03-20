<?php
declare(strict_types=1);

function db(array $cfg, bool $withoutDatabase = false): PDO
{
    static $connections = [];

    $key = md5(json_encode([$cfg, $withoutDatabase]));
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

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $connections[$key] = $pdo;
    return $pdo;
}

