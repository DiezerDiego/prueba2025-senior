<?php
declare(strict_types=1);


use App\Domain\Entity\Item;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    public function test_it_reserves_stock_when_available(): void
    {
        $item = new Item(
            id: 1,
            sku: 'SKU-001',
            name: 'Test Item',
            availableQuantity: 5
        );

        $item->reserve(2);

        $this->assertSame(3, $item->availableQuantity());
    }

    public function test_it_throws_exception_when_stock_is_insufficient(): void
    {
        $item = new Item(1, 'SKU-001', 'Test Item', 1);

        $this->expectException(DomainException::class);

        $item->reserve(2);
    }

    public function test_it_releases_stock(): void
    {
        $item = new Item(1, 'SKU-001', 'Test Item', 3);

        $item->release(2);

        $this->assertSame(5, $item->availableQuantity());
    }

    public function test_it_does_not_allow_zero_or_negative_release(): void
    {
        $item = new Item(1, 'SKU-001', 'Test Item', 3);

        $this->expectException(InvalidArgumentException::class);

        $item->release(0);
    }
}
