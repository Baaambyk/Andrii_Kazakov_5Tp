<?php

declare(strict_types=1);

/**
 * Cienka warstwa nad PDO — jedno połączenie na żądanie.
 */
final class Database
{
    private static ?PDO $pdo = null;

    /** @param array<string, mixed> $config */
    public static function connect(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        self::$pdo = new PDO($dsn, (string) $config['user'], (string) $config['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);

        return self::$pdo;
    }
}
