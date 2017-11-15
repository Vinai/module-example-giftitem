<?php

declare(strict_types=1);

namespace Example\GiftItem\Test\Integration\Model\Totals;

use Example\GiftItem\Model\GiftItemManager;
use Example\GiftItem\Model\Totals\GiftItemAddressTotal;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function \array_filter as filter;
use function \array_values as values;

class GiftItemAddressTotalTest extends TestCase
{
    const FIXTURE_SKU = 'simple';
    
    public static function makeFixtureProductSalable()
    {
        /** @var StockRegistryInterface $stockRegistry */
        $stockRegistry = ObjectManager::getInstance()->create(StockRegistryInterface::class);
        $item = $stockRegistry->getStockItemBySku(self::FIXTURE_SKU);
        $item->setQty(100);
        $item->setIsInStock(true);
        $stockRegistry->updateStockItemBySku(self::FIXTURE_SKU, $item);
    }

    private function loadProductBySku($sku): ProductModel
    {
        /** @var ProductModel $product */
        $product = ObjectManager::getInstance()->create(ProductModel::class);
        /** @var ProductResource $resource */
        $resource = ObjectManager::getInstance()->create(ProductResource::class);
        $resource->load($product, $resource->getIdBySku($sku));

        return $product;
    }

    private function createGiftItemManager(): GiftItemManager
    {
        return ObjectManager::getInstance()->create(GiftItemManager::class);
    }

    /**
     * @param Quote\Item[] $items
     * @return Quote\Item[]
     */
    private function filterGiftItems(array $items): array
    {
        return values(filter($items, [$this->createGiftItemManager(), 'isGiftItem']));
    }

    private function filterNonGiftItems(array $items): array
    {
        $giftItemManager = $this->createGiftItemManager();
        return values(filter($items, function (Quote\Item $item) use ($giftItemManager) {
            return ! $giftItemManager->isGiftItem($item);
        }));
    }

    private function collectTotals(Quote $quote)
    {
        $quote->getShippingAddress();
        $quote->getBillingAddress();
        $quote->collectTotals();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_without_options.php
     * @magentoDataFixture makeFixtureProductSalable
     */
    public function testZerosPriceForGiftItems()
    {
        /** @var Quote $quote */
        $quote = ObjectManager::getInstance()->create(Quote::class);

        $nonGiftProduct = $this->loadProductBySku(self::FIXTURE_SKU);
        $giftProduct = $this->createGiftItemManager()->markAsGift($this->loadProductBySku(self::FIXTURE_SKU));

        $nonGiftQty = 2;
        $quote->addProduct($nonGiftProduct, $nonGiftQty);
        $quote->addProduct($giftProduct);

        $this->collectTotals($quote);

        $gifts = $this->filterGiftItems($quote->getAllItems());
        $nonGifts = $this->filterNonGiftItems($quote->getAllItems());

        $expectedTotal = $nonGiftProduct->getPrice() * $nonGiftQty;
        
        $this->assertSame($expectedTotal, $quote->getGrandTotal(), 'Quote grand total does not match expected value');

        $this->assertCount(1, $gifts, 'Expected gift item count is not 1');
        $this->assertEquals(0, $gifts[0]->getPrice(), 'Gift item price not set to zero');
        
        $this->assertCount(1, $nonGifts, 'More than one non-gift item found in cart');
        $this->assertEquals(
            $expectedTotal,
            $nonGifts[0]->getRowTotal(),
            'The non-gift item row total does not match the expected value'
        );
    }
}
