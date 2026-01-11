<?php

declare(strict_types=1);

namespace App\Infrastructure\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\LogRecord;
use Psr\Http\Message\ServerRequestInterface;

final class LoggerFactory
{
    public static function create(string $name, ?ServerRequestInterface $request = null): Logger
    {
        $logger = new Logger($name);

        $handler = new StreamHandler('php://stdout', Logger::DEBUG);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);

        $logger->pushProcessor(new UidProcessor());

        $correlationId =
            $request?->getHeaderLine('X-Correlation-Id')
                ?: bin2hex(random_bytes(8));

        $logger->pushProcessor(
            function (LogRecord $record) use ($correlationId): LogRecord {
                return $record->with(
                    extra: array_merge(
                        $record->extra,
                        ['correlation_id' => $correlationId]
                    )
                );
            }
        );

        return $logger;
    }
}
