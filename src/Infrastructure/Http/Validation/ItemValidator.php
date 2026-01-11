<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Validation;

use Psr\Http\Message\ServerRequestInterface;
use DomainException;

final class ItemValidator
{
    public function validateSku(array $vars): string
    {
        if (!isset($vars['sku']) || !is_string($vars['sku'])) {
            throw new DomainException('SKU is required and must be a string');
        }
        return trim($vars['sku']);
    }
}
