<?php
declare(strict_types=1);

use App\Infrastructure\Http\Middleware\CorrelationIdMiddleware;
use App\Infrastructure\Http\Middleware\ErrorHandlerMiddleware;
use App\Infrastructure\Http\Middleware\MiddlewareDispatcher;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseInterface;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Env
 */
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

/**
 * Container
 */
$container = require __DIR__ . '/../config/container.php';

/**
 * Request
 */
$psr17Factory = new Psr17Factory();
$request = (new ServerRequestCreator(
        $psr17Factory,
        $psr17Factory,
        $psr17Factory,
        $psr17Factory
))->fromGlobals();

/**
 * Router
 */
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('POST', '/reservations', ['ReservationController', 'create']);
    $r->addRoute('GET',  '/reservations/{id:\d+}', ['ReservationController', 'show']);
    $r->addRoute('POST', '/reservations/{id:\d+}/confirm', ['ReservationController', 'confirm']);
    $r->addRoute('GET',  '/items/{sku}', ['ItemController', 'item']);
});

/**
 * Final handler (dispatcher)
 */
$finalHandler = new class ($dispatcher, $container, $psr17Factory)
        implements RequestHandlerInterface {

    public function __construct(
            private $dispatcher,
            private $container,
            private $factory
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch(
                $request->getMethod(),
                $request->getUri()->getPath()
        );

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                return $this->factory->createResponse(404);

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                return $this->factory->createResponse(405);

            case FastRoute\Dispatcher::FOUND:
                [$controllerName, $method] = $routeInfo[1];
                $vars = $routeInfo[2];

                $controllerClass = "App\\Infrastructure\\Http\\Controller\\{$controllerName}";

                if (!isset($this->container[$controllerClass])) {
                    return $this->factory->createResponse(500);
                }

                $controller = $this->container[$controllerClass];

                return $controller->$method($request, $vars);
        }
    }
};

/**
 * Middleware chain
 */
$logger = $container['logger'];

$middlewareDispatcher = new MiddlewareDispatcher(
    [
        new CorrelationIdMiddleware(),
        new ErrorHandlerMiddleware($logger),
    ],
    $finalHandler
);

/**
 * Handle request
 */
$response = $middlewareDispatcher->handle($request);

/**
 * Emit response
 */
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("$name: $value", false);
    }
}
echo $response->getBody();
