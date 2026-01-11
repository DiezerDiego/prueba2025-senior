<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Reservation;
use App\Domain\Repository\ItemRepository;
use App\Domain\Repository\ReservationRepository;
use App\Domain\Repository\IdempotencyRepository;
use App\Infrastructure\Persistence\TransactionManager;
use DateTimeImmutable;
use DomainException;
use Psr\Log\LoggerInterface;
final class CreateReservation
{
    public function __construct(
        private ItemRepository $itemRepository,
        private ReservationRepository $reservationRepository,
        private IdempotencyRepository $idempotencyRepository,
        private TransactionManager $transactionManager,
        private LoggerInterface $logger
    ) {}

    public function execute(
        string $idempotencyKey,
        string $sku,
        int $quantity,
        int $ttlSeconds
    ): Reservation {
        if ($quantity <= 0 || $ttlSeconds <= 0) {
            $this->logger->error('Invalid quantity or TTL', ['sku' => $sku, 'quantity' => $quantity, 'ttl' => $ttlSeconds]);
            throw new DomainException('Invalid quantity or TTL');
        }

        return $this->transactionManager->transactional(function () use (
            $idempotencyKey,
            $sku,
            $quantity,
            $ttlSeconds
        ): Reservation {


            $previous = $this->idempotencyRepository->findByKey($idempotencyKey);

            if ($previous !== null) {
                $hash = $this->payloadHash($sku, $quantity, $ttlSeconds);
                if ($previous->payloadHash !== $this->payloadHash($sku, $quantity, $ttlSeconds)) {
                    $this->logger->warning('Idempotency key reused with different payload', [
                        'idempotencyKey' => $idempotencyKey,
                        'previousHash' => $previous->payloadHash,
                        'newHash' => $hash
                    ]);
                    throw new DomainException('Idempotency key reused with different payload');
                }
                $this->logger->info('Returning existing reservation due to idempotency', [
                    'reservation_id' => $previous->reservationId
                ]);
                return $this->reservationRepository->getById($previous->reservationId);
            }


            $item = $this->itemRepository->getBySkuForUpdate($sku);
            if (!$item->canReserve($quantity)) {
                $this->logger->warning('Insufficient stock', ['sku' => $sku, 'requested' => $quantity, 'available' => $item->availableQuantity()]);
                throw new DomainException('Insufficient stock');
            }
            $item->reserve($quantity);

            $now = new DateTimeImmutable();
            $expiresAt = $now->modify(sprintf('+%d seconds', $ttlSeconds));

            $reservation = Reservation::createPending(
                itemId: $item->id(),
                idempotencyKey: $idempotencyKey,
                quantity: $quantity,
                expiresAt: $expiresAt
            );


            $this->itemRepository->save($item);
            $this->reservationRepository->save($reservation);


            $this->idempotencyRepository->save(
                $idempotencyKey,
                $this->payloadHash($sku, $quantity, $ttlSeconds),
                $reservation->id()
            );
            $this->logger->info('Reservation created successfully', [
                'reservation_id' => $reservation->id(),
                'sku' => $sku,
                'quantity' => $quantity,
                'expires_at' => $expiresAt->format(DATE_ATOM)
            ]);
            return $reservation;
        });
    }

    private function payloadHash(string $sku, int $quantity, int $ttl): string
    {
        return hash('sha256', json_encode([
            'sku' => $sku,
            'quantity' => $quantity,
            'ttl' => $ttl,
        ]));
    }
}
