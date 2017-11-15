<?php

declare(strict_types=1);

namespace Example\GiftItem\Model\Totals;

use Example\GiftItem\Model\GiftItemManager;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use PHPUnit\Framework\TestCase;

use function array_merge as merge;

class GiftItemAddressTotalTest extends TestCase
{
    /**
     * @param float|int $rowTotal
     * @return Quote\Item\AbstractItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createItemWithRowTotal($rowTotal, $baseRowTotal = null): Quote\Item\AbstractItem
    {
        $methods = merge(get_class_methods(Quote\Item\AbstractItem::class), ['getRowTotal', 'getBaseRowTotal']);
        $item = $this->getMockBuilder(Quote\Item\AbstractItem::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
        $item->method('getRowTotal')->willReturn($rowTotal);
        $item->method('getBaseRowTotal')->willReturn($baseRowTotal ?? $rowTotal);

        return $item;
    }

    /**
     * @param int|float $rowTotal
     * @return Quote\Item\AbstractItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createGiftItemWithRowTotal($rowTotal, $baseRowTotal = null): Quote\Item\AbstractItem
    {
        $option = $this->createMock(Quote\Item\Option::class);
        $option->method('getValue')->willReturn(1);
        $giftItem = $this->createItemWithRowTotal($rowTotal, $baseRowTotal);
        $giftItem->method('getOptionByCode')->with(GiftItemManager::OPTION_IS_GIFT)->willReturn($option);

        return $giftItem;
    }

    /**
     * @param Quote\Item\AbstractItem[] $items
     * @return ShippingAssignmentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createStubShippingAssignment(array $items): ShippingAssignmentInterface
    {
        $address = $this->createMock(Quote\Address::class);
        $shipping = $this->createMock(ShippingInterface::class);
        $shipping->method('getAddress')->willReturn($address);
        $shippingAssignment = $this->createMock(ShippingAssignmentInterface::class);
        $shippingAssignment->method('getShipping')->willReturn($shipping);

        $shippingAssignment->method('getItems')->willReturn($items);

        return $shippingAssignment;
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createDummyQuote(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(Quote::class);
    }

    /**
     * @return Quote\Address\Total|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createDummyAddressTotal(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(Quote\Address\Total::class);
    }

    public function testInheritsAbstractAddressTotalModel()
    {
        $this->assertInstanceOf(AbstractTotal::class, new GiftItemAddressTotal());
    }

    public function testReturnsZeroIfNoItemsArePassed()
    {
        $this->assertSame(0, (new GiftItemAddressTotal())->getGiftItemTotalSum());
    }

    public function testReturnsZeroIfOnlyNonGiftItemIsPassed()
    {
        $nonGiftItem = $this->createItemWithRowTotal(10);
        $this->assertSame(0, (new GiftItemAddressTotal())->getGiftItemTotalSum($nonGiftItem));
    }

    public function testReturnsGiftItemRowTotal()
    {
        $rowTotal = 6;
        $giftItem = $this->createGiftItemWithRowTotal($rowTotal);
        $this->assertSame($rowTotal, (new GiftItemAddressTotal())->getGiftItemTotalSum($giftItem));
    }

    public function testReturnsSumOfGiftItemTotalsOnly()
    {
        $items = [
            $this->createItemWithRowTotal(1),
            $this->createItemWithRowTotal(2),
            $this->createGiftItemWithRowTotal(4),
            $this->createGiftItemWithRowTotal(8),
        ];
        $this->assertSame(12, (new GiftItemAddressTotal())->getGiftItemTotalSum(...$items));
    }

    public function testReturnsSumOfGiftItemBaseTotalsOnly()
    {
        $items = [
            $this->createItemWithRowTotal(0, 2),
            $this->createItemWithRowTotal(0, 4),
            $this->createGiftItemWithRowTotal(0, 8),
            $this->createGiftItemWithRowTotal(0, 16),
        ];
        $this->assertSame(24, (new GiftItemAddressTotal())->getGiftItemBaseTotalSum(...$items));
    }

    public function testSubtractsGiftItemTotalsFromSubtotal()
    {
        $items = [
            $this->createItemWithRowTotal(2, 4),
            $this->createGiftItemWithRowTotal(10, 20),
        ];

        $quote = $this->createDummyQuote();
        $total = $this->createDummyAddressTotal();
        $shippingAssignment = $this->createStubShippingAssignment($items);

        $total->expects($this->once())->method('addTotalAmount')->with('subtotal', -10);
        $total->expects($this->once())->method('addBaseTotalAmount')->with('subtotal', -20);

        (new GiftItemAddressTotal())->collect($quote, $shippingAssignment, $total);
    }

    public function testZerosPriceOnGiftItems()
    {
        $item = $this->createItemWithRowTotal(2, 4);
        $item->expects($this->never())->method('setData');

        $giftItem = $this->createGiftItemWithRowTotal(10, 20);
        $giftItem->expects($this->exactly(2))->method('setData')->withConsecutive(
            ['calculation_price', 0],
            ['base_calculation_price', 0]
        );
        $giftItem->expects($this->once())->method('calcRowTotal');

        $quote = $this->createDummyQuote();
        $total = $this->createDummyAddressTotal();
        $shippingAssignment = $this->createStubShippingAssignment([$item, $giftItem]);

        (new GiftItemAddressTotal())->collect($quote, $shippingAssignment, $total);
    }
}
