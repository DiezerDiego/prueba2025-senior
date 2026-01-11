<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Psr\Log\LoggerInterface;
use App\Application\UseCase\GetItem;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;
use App\Infrastructure\Http\Validation\ItemValidator;

final class ItemController
{
    public function __construct(
        private GetItem $getItem,
        private LoggerInterface $logger,
        private ItemValidator $validator
    ) {}

    public function item(ServerRequestInterface $request, array $vars): Response
    {
        try {
            $sku=$id = $this->validator->validateSku($vars);
            $itemRecord = $this->getItem->execute($sku);

            $this->logger->info('Fetched item', [
                'sku' => $sku,
                'available_quantity' => $itemRecord->availableQuantity
            ]);

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($itemRecord)
            );
        } catch (\DomainException $e) {
            $this->logger->warning('Item not found', ['sku' => $sku]);
            return new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        }
    }
}
