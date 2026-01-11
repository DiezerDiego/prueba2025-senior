<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Validation;

use Psr\Http\Message\ServerRequestInterface;
use DomainException;

final class ReservationValidator
{
    public function __construct(){}
    public function validateCreate(ServerRequestInterface $request): array
    {
        $body = json_decode((string)$request->getBody(), true);

        if (!is_array($body)) {
            throw new DomainException('Invalid JSON body');
        }

        if (empty($body['sku']) || !is_string($body['sku'])) {
            throw new DomainException('SKU is required and must be a string');
        }

        $quantity = $body['quantity'] ?? null;
        if (!is_int($quantity) || $quantity <= 0) {
            throw new DomainException('Quantity is required and must be a positive integer');
        }

        $ttl = $body['ttl_seconds'] ?? null;
        if (!is_int($ttl) || $ttl <= 0) {
            throw new DomainException('TTL seconds is required and must be a positive integer');
        }

        $idempotencyKey = $request->getHeaderLine('Idempotency-Key');
        if (empty($idempotencyKey)) {
            throw new DomainException('Idempotency-Key header required');
        }

        return [
            'sku' => $body['sku'],
            'quantity' => $quantity,
            'ttl_seconds' => $ttl,
            'idempotency_key' => $idempotencyKey
        ];
    }

    public function validateId(array $vars): int
    {
        if (empty($vars['id']) || !is_numeric($vars['id'])) {
            throw new DomainException('Reservation ID is required and must be an integer');
        }

        return (int)$vars['id'];
    }
}
