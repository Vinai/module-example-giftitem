<?php

declare(strict_types=1);

namespace Example\GiftItem\Model;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;

class GiftItemManagerTest extends TestCase
{
    /**
     * @param GiftingRuleInterface|\PHPUnit_Framework_MockObject_MockObject $giftingRule
     * @return GiftItemManager
     */
    private function createInstance(GiftingRuleInterface $giftingRule): GiftItemManager
    {
        return new GiftItemManager($giftingRule);
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockQuote(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(Quote::class);
    }

    /**
     * @return Quote\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockGiftItem(): Quote\Item
    {
        $mockGiftItem = $this->createMock(Quote\Item::class);
        $mockGiftItem->method('getOptionByCode')
            ->with(GiftItemManager::OPTION_IS_GIFT)
            ->willReturn($this->createMockGiftItemOption());

        return $mockGiftItem;
    }

    /**
     * @return Option|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockGiftItemOption(): Option
    {
        $mockGiftItemOption = $this->createMock(Option::class);
        $mockGiftItemOption->method('getValue')->willReturn(1);

        return $mockGiftItemOption;
    }

    public function testFetchesGiftItemsFromGiftingRule()
    {
        $mockQuote = $this->createMockQuote();
        $dummyCurrentGifts = [];
        
        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->expects($this->once())->method('getGiftsToAdd')->with($mockQuote, $dummyCurrentGifts);

        $giftItemManager = $this->createInstance($mockGiftingRule);
        $giftItemManager->setGiftItemsForQuote($mockQuote);
    }

    public function testAddsGiftItemsToQuote()
    {
        $mockQuote = $this->createMockQuote();
        
        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->method('getGiftsToAdd')->willReturn(
            [$this->createMock(Product::class), $this->createMock(Product::class)]
        );
        
        $mockQuote->expects($this->exactly(2))->method('addProduct')->with($this->isInstanceOf(Product::class));

        $this->createInstance($mockGiftingRule)->setGiftItemsForQuote($mockQuote);
    }

    public function testSetsIsGiftOptionOnGifts()
    {
        $mockQuote = $this->createMockQuote();
        
        $mockProduct = $this->createMock(Product::class);
        $mockProduct->expects($this->once())->method('addCustomOption')->with(GiftItemManager::OPTION_IS_GIFT, 1);
        
        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->method('getGiftsToAdd')->willReturn([$mockProduct]);
        
        $this->createInstance($mockGiftingRule)->setGiftItemsForQuote($mockQuote);
    }

    public function testPassesExistingGiftsToGiftingRule()
    {
        $mockQuote = $this->createMockQuote();
        $mockRegularItem = $this->createMock(Quote\Item::class);
        $mockGiftItem = $this->createMockGiftItem();
        
        $mockQuote->method('getAllItems')->willReturn([$mockGiftItem, $mockRegularItem]);

        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->expects($this->once())->method('getGiftsToAdd')->with($mockQuote, [$mockGiftItem]);

        $this->createInstance($mockGiftingRule)->setGiftItemsForQuote($mockQuote);
    }

    public function testFetchesGiftsToRemoveFromGiftingRule()
    {
        $mockQuote = $this->createMockQuote();
        $dummyCurrentGifts = [$this->createMockGiftItem()];
        $mockQuote->method('getAllItems')->willReturn($dummyCurrentGifts);

        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->expects($this->once())->method('getGiftsToRemove')->with($mockQuote, $dummyCurrentGifts);

        $giftItemManager = $this->createInstance($mockGiftingRule);
        $giftItemManager->setGiftItemsForQuote($mockQuote);
    }

    public function testDeletesItemsToRemoveFromQuote()
    {
        $mockQuote = $this->createMockQuote();
        $mockRegularItem = $this->createMock(Quote\Item::class);
        $mockGiftItem = $this->createMockGiftItem();
        
        $mockQuote->method('getAllItems')->willReturn([$mockRegularItem, $mockGiftItem]);
        $mockQuote->expects($this->once())->method('deleteItem')->with($mockGiftItem);
        
        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->method('getGiftsToRemove')->willReturn([$mockGiftItem]);

        $giftItemManager = $this->createInstance($mockGiftingRule);
        $giftItemManager->setGiftItemsForQuote($mockQuote);
    }

    public function testFetchesGiftItemQuantityUpdatesFromGiftingRule()
    {
        $mockQuote = $this->createMockQuote();
        $dummyCurrentGifts = [$this->createMockGiftItem()];
        $mockQuote->method('getAllItems')->willReturn($dummyCurrentGifts);

        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->expects($this->once())->method('getGiftItemQtyUpdates')->with($mockQuote, $dummyCurrentGifts);

        $giftItemManager = $this->createInstance($mockGiftingRule);
        $giftItemManager->setGiftItemsForQuote($mockQuote);
    }

    public function testSetsTargetQtyOnGiftItems()
    {
        $mockGiftItem = $this->createMockGiftItem();
        $mockGiftItem->method('getId')->willReturn(1);
        $mockGiftItem->expects($this->once())->method('setQty')->with(2);

        $mockQuote = $this->createMockQuote();
        $mockQuote->method('getAllItems')->willReturn([$mockGiftItem]);

        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $mockGiftingRule->expects($this->once())->method('getGiftItemQtyUpdates')->willReturn([1 => 2]);

        $giftItemManager = $this->createInstance($mockGiftingRule);
        $giftItemManager->setGiftItemsForQuote($mockQuote);
    }

    public function testReturnsTrueForGiftItems()
    {
        $mockGiftItem = $this->createMockGiftItem();
        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $this->assertTrue($this->createInstance($mockGiftingRule)->isGiftItem($mockGiftItem));
    }

    public function testReturnsFalseForGiftItems()
    {
        /** @var Quote\Item|\PHPUnit_Framework_MockObject_MockObject $mockNonGiftItem */
        $mockNonGiftItem = $this->createMock(Quote\Item::class);
        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $this->assertFalse($this->createInstance($mockGiftingRule)->isGiftItem($mockNonGiftItem));
    }

    public function testSetsGiftOptionOnProduct()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $mockProduct */
        $mockProduct = $this->createMock(Product::class);
        $mockProduct->expects($this->once())->method('addCustomOption')->with(GiftItemManager::OPTION_IS_GIFT, 1);

        $mockGiftingRule = $this->createMock(GiftingRuleInterface::class);
        $gift = $this->createInstance($mockGiftingRule)->markAsGift($mockProduct);
        $this->assertSame($mockProduct, $gift);
    }
}
