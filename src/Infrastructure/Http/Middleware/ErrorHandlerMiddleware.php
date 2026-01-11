<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Nyholm\Psr7\Response;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);

        } catch (DomainException $e) {
            return new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );

        } catch (InvalidArgumentException $e) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );

        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->getAttribute('correlation_id'),
            ]);

            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Internal Server Error'])
            );
        }
    }
}