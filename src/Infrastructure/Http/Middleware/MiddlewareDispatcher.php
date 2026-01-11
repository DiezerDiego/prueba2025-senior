<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareDispatcher implements RequestHandlerInterface
{
    private int $index = 0;

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(
        private array                   $middlewares,
        private RequestHandlerInterface $finalHandler
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middlewares[$this->index])) {
            return $this->finalHandler->handle($request);
        }

        $middleware = $this->middlewares[$this->index];
        $this->index++;

        return $middleware->process($request, $this);
    }
}
