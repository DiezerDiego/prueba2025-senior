<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;

final class CorrelationIdMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $correlationId = $request->getHeaderLine('X-Correlation-Id') ?: bin2hex(random_bytes(8));

        $request = $request->withAttribute('correlation_id', $correlationId);

        $response = $handler->handle($request);
        return $response->withHeader('X-Correlation-Id', $correlationId);
    }
}
