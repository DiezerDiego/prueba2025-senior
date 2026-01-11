<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ItemRepository;
use App\Application\Dto\ItemRecord;
use Psr\Log\LoggerInterface;
use DomainException;

final class GetItem
{
    public function __construct(
        private ItemRepository $itemRepository,
        private LoggerInterface $logger
    ) {}

    public function execute(string $sku): ItemRecord
    {
        $item = $this->itemRepository->getBySkuForUpdate($sku);

        if (!$item) {
            $this->logger->warning('Item not found', ['sku' => $sku]);
            throw new DomainException("Item not found: {$sku}");
        }

        $this->logger->info('Fetched item', [
            'sku' => $sku,
            'available_quantity' => $item->availableQuantity()
        ]);

        return new ItemRecord(
            sku: $item->sku(),
            name: $item->name(),
            availableQuantity: $item->availableQuantity()
        );
    }
}
