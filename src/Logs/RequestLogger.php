<?php

namespace Rockberpro\RestRouter\Logs;

use Rockberpro\RestRouter\Logs\InfoLogHandler;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Core\Request;
use Rockberpro\RestRouter\Core\Server;
use RuntimeException;

class RequestLogger
{
    private ?InfoLogHandler $infoLogHandler = null;

    public function __construct($file_path)
    {
        $this->infoLogHandler = new InfoLogHandler($file_path);
    }

    public function setInfoLogger(?InfoLogHandler $logger)
    {
        $this->infoLogHandler = $logger;
    }

    public function getInfoLogger(): ?InfoLogHandler
    {
        return $this->infoLogHandler;
    }

    public function writeLog(Request $request): void
    {
        if (!$this->getInfoLogger()) {
            throw new RuntimeException('Request logger is not set');
        }

        $is_closure = $request->getAction()->isClosure();
        $log_data = [
            'subject' => DotEnv::get('API_NAME'),
            'type' =>  $is_closure ? 'closure' : 'controller',
            'remote_address' => Server::remoteAddress(),
            'target_address' => Server::targetAddress(),
            'user_agent' => Server::userAgent(),
            'request_method' => Server::requestMethod(),
            'request_uri' => Server::requestUri(),
            'request_body' => json_encode($request->getParameters()),
            'endpoint' => $request->getAction()->getUri() ?? '',
        ];
        if (!$is_closure) {
            $log_data['class'] = $request->getAction()->getClass();
            $log_data['method'] = $request->getAction()->getMethod();
        }

        $this->getInfoLogger()->write('Request', $log_data);
    }
}
