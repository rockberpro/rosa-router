<?php

namespace Rockberpro\RestRouter\Database\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use PDO;

class PDOLogHandler extends AbstractProcessingHandler
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table, $level = \Monolog\Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    protected function write(array $record): void
    {
        $sql = "INSERT INTO {$this->table} (channel, level, message, context, created_at) VALUES (:channel, :level, :message, :context, :created_at)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':channel' => $record['channel'],
            ':level' => $record['level_name'],
            ':message' => $record['message'],
            ':context' => json_encode($record['context']),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}