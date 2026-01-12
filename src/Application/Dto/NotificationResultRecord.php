<?php
declare(strict_types=1);

namespace App\Application\Dto;

final class NotificationResultRecord
{
    public function __construct(
        public readonly bool $sent
    ) {}
}
