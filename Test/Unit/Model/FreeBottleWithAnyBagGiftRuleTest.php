<?php

declare(strict_types=1);

namespace Example\GiftItem\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

class FreeBottleWithAnyBagGiftRuleTest extends TestCase
{
    private function createInstance(): FreeBottleWithAnyBagGiftRule
    {
        /** @var ProductResource|\PHPUnit_Framework_MockObject_MockObject $mockProductResource */
        $mockProductResource = $this->createMock(ProductResource::class);

        return $this->createInstanceWithProductResource($mockProductResource);
    }

    private function createInstanceWithProductResource(ProductResource $productResource): FreeBottleWithAnyBagGiftRule
    {
        /** @var ProductFactory|\PHPUnit_Framework_MockObject_MockObject $productFactory */
        $productFactory = $this->createMock(ProductFactory::class);
        $productFactory->method('create')->willReturnCallback(function () {
            return $this->createMock(Product::class);
        });

        return new FreeBottleWithAnyBagGiftRule($productFactory, $productResource);
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockQuote(): Quote
    {
        return $this->createMock(Quote::class);
    }

    /**
     * @return Quote\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockGiftItem(): Quote\Item
    {
        $mockItemOption = $this->createMock(Quote\Item\Option::class);
        $mockItemOption->method('getValue')->willReturn(FreeBottleWithAnyBagGiftRule::RULE_CODE);
        $mockGift = $this->createMockQuoteItemWithSku(FreeBottleWithAnyBagGiftRule::GIFT_SKU);
        $mockGift->method('getOptionByCode')->with(GiftingRuleInterface::GIFT_TYPE)->willReturn($mockItemOption);
        $mockGift->method('getProduct')->willReturn($this->createMock(Product::class));

        return $mockGift;
    }

    /**
     * @return Quote\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockQuoteItemWithSku(string $sku): Quote\Item
    {
        $mockGift = $this->createMock(Quote\Item::class);
        $mockGift->method('getSku')->willReturn($sku);
        $mockGift->method('getProduct')->willReturn($this->createMock(Product::class));

        return $mockGift;
    }

    /**
     * @return Quote\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockBagItem($qty = 1): Quote\Item
    {
        return $this->createQuoteItemWithAttributeSetId(FreeBottleWithAnyBagGiftRule::BAG_ATTRIBUTE_SET_ID, $qty);
    }

    /**
     * @param int $attributeSetId
     * @return Quote\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createQuoteItemWithAttributeSetId(int $attributeSetId, $qty = 1): Quote\Item
    {
        $mockBag = $this->createMock(Product::class);
        $mockBag->method('getAttributeSetId')->willReturn($attributeSetId);
        $mockBagItem = $this->createMock(Quote\Item::class);
        $mockBagItem->method('getProduct')->willReturn($mockBag);
        $mockBagItem->method('getQty')->willReturn($qty);

        return $mockBagItem;
    }

    public function testImplementsGiftingRuleInterface()
    {
        $this->assertInstanceOf(GiftingRuleInterface::class, $this->createInstance());
    }

    public function testRemovesGiftItemIfNoBagInCart()
    {
        $mockGift = $this->createMockGiftItem();
        $mockQuote = $this->createMockQuote();
        $mockQuote->method('getAllItems')->willReturn([$mockGift]);

        $this->assertSame([$mockGift], $this->createInstance()->getGiftsToRemove($mockQuote, [$mockGift]));
    }

    public function testDoesNotRemoveGiftItemIfBagInCart()
    {
        $mockGift = $this->createMockGiftItem();
        $mockBag = $this->createMockBagItem();

        $mockQuote = $this->createMockQuote();
        $mockQuote->method('getAllItems')->willReturn([$mockBag, $mockGift]);

        $this->assertSame([], $this->createInstance()->getGiftsToRemove($mockQuote, [$mockGift]));
    }

    public function testAddsNoGiftItemIfNoBagIsPresent()
    {
        $mockQuote = $this->createMockQuote();
        $nonBagAttributeSetId = FreeBottleWithAnyBagGiftRule::BAG_ATTRIBUTE_SET_ID + 1;
        $mockNonBagItem = $this->createQuoteItemWithAttributeSetId($nonBagAttributeSetId);
        $mockQuote->method('getAllItems')->willReturn([$mockNonBagItem]);

        $this->assertSame([], $this->createInstance()->getGiftsToAdd($mockQuote, []));
    }

    public function testAddsNoGiftItemIfGiftBottleIsAlreadyPresent()
    {
        $mockQuote = $this->createMockQuote();
        $nonBagAttributeSetId = FreeBottleWithAnyBagGiftRule::BAG_ATTRIBUTE_SET_ID + 1;
        $mockNonBagItem = $this->createQuoteItemWithAttributeSetId($nonBagAttributeSetId);
        $mockBag = $this->createMockBagItem();
        $mockGift = $this->createMockGiftItem();

        $mockQuote->method('getAllItems')->willReturn([$mockNonBagItem, $mockBag, $mockGift]);

        $this->assertSame([], $this->createInstance()->getGiftsToAdd($mockQuote, [$mockGift]));
    }

    public function testAddsAGiftBottleIfBagIsPresent()
    {
        $mockQuote = $this->createMockQuote();
        $mockBagItem = $this->createMockBagItem();
        $mockQuote->method('getAllItems')->willReturn([$mockBagItem]);

        /** @var ProductResource|\PHPUnit_Framework_MockObject_MockObject $mockProductResource */
        $mockProductResource = $this->createMock(ProductResource::class);
        $mockProductResource->expects($this->once())
            ->method('getIdBySku')
            ->with(FreeBottleWithAnyBagGiftRule::GIFT_SKU)
            ->willReturn(42);

        $mockProductResource->expects($this->once())
            ->method('load')
            ->with($this->isInstanceOf(Product::class), 42)
            ->willReturnCallback(function (Product $product, int $id) {
                /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
                $product->expects($this->once())
                    ->method('addCustomOption')
                    ->with(GiftingRuleInterface::GIFT_TYPE, FreeBottleWithAnyBagGiftRule::RULE_CODE);
            });

        $giftsToAdd = $this->createInstanceWithProductResource($mockProductResource)->getGiftsToAdd($mockQuote, []);

        $this->assertCount(1, $giftsToAdd);
        $this->assertContainsOnlyInstancesOf(ProductInterface::class, $giftsToAdd);
    }

    public function testThrowsExceptionIfGiftSkuDoesNotExist()
    {
        $this->expectException(\OutOfBoundsException::class);

        $mockQuote = $this->createMockQuote();
        $mockBagItem = $this->createMockBagItem();
        $mockQuote->method('getAllItems')->willReturn([$mockBagItem]);

        $giftsToAdd = $this->createInstance()->getGiftsToAdd($mockQuote, []);

        $this->assertCount(1, $giftsToAdd);
        $this->assertContainsOnlyInstancesOf(ProductInterface::class, $giftsToAdd);
    }

    public function testSetsGiftQtyToBagQtyIfGiftBottleQtyIsHigher()
    {
        $mockQuote = $this->createMockQuote();
        $bagQtyty = 2;
        $mockBag = $this->createMockBagItem($bagQtyty);
        $mockGift = $this->createMockGiftItem();
        $mockGift->method('getQty')->willReturn($bagQtyty + 1);
        $dummyGiftItemId = 1;
        $mockGift->method('getId')->willReturn($dummyGiftItemId);

        $mockQuote->method('getAllItems')->willReturn([$mockBag, $mockGift]);

        $giftItemQtyUpdates = $this->createInstance()->getGiftItemQtyUpdates($mockQuote, [$mockGift]);
        $this->assertSame([$dummyGiftItemId => $bagQtyty], $giftItemQtyUpdates);
    }
}
