<?php
declare(strict_types=1);

namespace App\Infrastructure\Dto;

final class NotificationResultRecord
{
    public function __construct(
        public readonly bool $sent
    ) {}
}
