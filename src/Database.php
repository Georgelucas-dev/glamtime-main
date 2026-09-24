<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $instancia = null;

    private function __construct() {}          // impede new Database()
    private function __clone() {}              // impede clone

    public static function getConnection(): PDO
    {
        if (self::$instancia === null) {
            $envFile = __DIR__ . '/../.env';
            $config = file_exists($envFile) ? (parse_ini_file($envFile) ?: []) : [];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['DB_HOST'] ?? 'localhost',
                $config['DB_PORT'] ?? '3306',
                $config['DB_NAME'] ?? 'glamtime'
            );
            self::$instancia = new PDO($dsn, $config['DB_USER'] ?? 'root', $config['DB_PASS'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instancia;
    }
}