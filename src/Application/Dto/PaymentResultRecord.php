<?php

declare(strict_types=1);

namespace App\Application\Dto;
final class PaymentResultRecord
{
    public function __construct(
        public readonly bool   $approved,
        public readonly string $providerRef
    )
    {
    }
}
