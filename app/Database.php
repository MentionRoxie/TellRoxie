<?php

declare(strict_types=1);

class Database
{
    private \PDO $pdo;
    private \PDOStatement $stmt;

    public function __construct()
    {
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}";
        $options = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ];

        $this->pdo = new \PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $options);
    }

    public function query(string $query, array $params = []): Database
    {
        $this->stmt = $this->pdo->prepare($query);
        $this->stmt->execute($params);

        return $this;
    }

    public function find(): array|bool
    {
        return $this->stmt->fetch();
    }
}
