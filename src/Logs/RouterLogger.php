<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Logs\ErrorLogHandler;
use RuntimeException;
use Throwable;

class RouterLogger
{
    private ?ErrorLogHandler $errorLogHandler = null;

    public function __construct(?ErrorLogHandler $errorLogHandler = null)
    {
        $this->errorLogHandler = $errorLogHandler;
    }

    public function setErrorLogger(?ErrorLogHandler $logger)
    {
        $this->errorLogHandler = $logger;
    }

    public function getErrorLogger(): ?ErrorLogHandler
    {
        return $this->errorLogHandler;
    }

    public function writeLog(Throwable $t): void
    {
        if (!$this->getErrorLogger()) {
            throw new RuntimeException('Error logger is not set');
        }

        $log_data = [
            'message' => $t->getMessage(),
            'file' => $t->getFile(),
            'line' => $t->getLine(),
            'trace' => $t->getTraceAsString(),
        ];

        $this->getErrorLogger()->write('Error', $log_data);
    }
}
