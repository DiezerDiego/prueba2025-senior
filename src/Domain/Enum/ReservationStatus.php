<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum ReservationStatus: string
{
    case CREATED = 'created';
    case PENDING = 'pending';
    case NEEDS_CONFIRMATION = 'needs_confirmation';
    case CONFIRMED = 'confirmed';
    case EXPIRED = 'expired';
    case CANCELLED= 'cancelled';
}
