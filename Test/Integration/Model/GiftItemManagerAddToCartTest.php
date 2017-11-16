<?php

declare(strict_types=1);

namespace Example\GiftItem\Test\Integration\Model;

use Example\GiftItem\Model\GiftingRuleInterface;
use Example\GiftItem\Model\GiftItemManager;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture Magento/Catalog/_files/product_without_options.php
 * @magentoDataFixture makeFixtureProductSalable
 */
class GiftItemManagerAddToCartTest extends TestCase
{
    public static function makeFixtureProductSalable()
    {
        /** @var StockRegistryInterface $stockRegistry */
        $stockRegistry = ObjectManager::getInstance()->create(StockRegistryInterface::class);
        $item = $stockRegistry->getStockItemBySku('simple');
        $item->setQty(100);
        $item->setIsInStock(true);
        $stockRegistry->updateStockItemBySku('simple', $item);
    }

    private function createGiftItemManager(GiftingRuleInterface $rule): GiftItemManager
    {
        return ObjectManager::getInstance()->create(GiftItemManager::class, ['giftingRule' => $rule]);
    }

    private function createAddingGiftingRule(): GiftingRuleInterface
    {
        return new class implements GiftingRuleInterface
        {
            public function getGiftsToAdd(Quote $quote, array $currentGifts)
            {
                /** @var ProductRepositoryInterface $repo */
                $repo = ObjectManager::getInstance()->create(ProductRepositoryInterface::class);

                return [$repo->get('simple')];
            }

            public function getGiftsToRemove(Quote $quote, array $currentGifts)
            {
                return [];
            }

            public function getGiftItemQtyUpdates(Quote $quote, array $currentGifts)
            {
                return [];
            }
        };
    }

    private function createRemovingGiftingRule(): GiftingRuleInterface
    {
        return new class implements GiftingRuleInterface
        {
            public function getGiftsToAdd(Quote $quote, array $currentGifts)
            {
                return [];
            }

            public function getGiftsToRemove(Quote $quote, array $currentGifts)
            {
                return array_slice($currentGifts, 0, 1);
            }

            public function getGiftItemQtyUpdates(Quote $quote, array $currentGifts)
            {
                return [];
            }
        };
    }

    private function createIncrementQtyGiftingRule(): GiftingRuleInterface
    {
        return new class implements GiftingRuleInterface
        {
            public function getGiftsToAdd(Quote $quote, array $currentGifts)
            {
                return [];
            }

            public function getGiftsToRemove(Quote $quote, array $currentGifts)
            {
                return [];
            }

            public function getGiftItemQtyUpdates(Quote $quote, array $currentGifts)
            {
                $targetQtys = [];
                foreach ($currentGifts as $gift) {
                    $targetQtys[$gift->getId()] = $gift->getQty() + 1;
                }
                return $targetQtys;
            }
        };
    }

    private function getItemByArrayIdx(Quote $quote, int $idx): Quote\Item
    {
        return $quote->getAllItems()[$idx];
    }

    public function testAddGiftItem()
    {
        $giftingRule = $this->createAddingGiftingRule();

        $giftItemManager = $this->createGiftItemManager($giftingRule);

        /** @var Quote $quote */
        $quote = ObjectManager::getInstance()->create(Quote::class);
        
        /** @var ProductInterface $product */
        $product = ObjectManager::getInstance()->get(ProductRepositoryInterface::class)->get('simple');
        $quote->addProduct($product);
        
        $giftItemManager->setGiftItemsForQuote($quote);
        
        $this->assertCount(2, $quote->getAllItems(), 'No gift item was added to cart.');
    }

    public function testAddAndRemoveGiftItem()
    {
        /** @var Quote $quote */
        $quote = ObjectManager::getInstance()->create(Quote::class);
        
        $addingGiftingRule = $this->createAddingGiftingRule();
        $this->createGiftItemManager($addingGiftingRule)->setGiftItemsForQuote($quote);
        $itemCountBefore = count($quote->getAllItems());

        $removeGiftingRule = $this->createRemovingGiftingRule();
        $this->createGiftItemManager($removeGiftingRule)->setGiftItemsForQuote($quote);
        $itemsAfterCount = count($quote->getAllItems());
        
        $this->assertSame(1, $itemCountBefore, 'Expected 1 item to be in the cart.');
        $this->assertSame(0, $itemsAfterCount, 'Expected 0 items to be in the cart.');
    }
    
    public function testQtyUpdatingRule()
    {
        /** @var Quote $quote */
        $quote = ObjectManager::getInstance()->create(Quote::class);

        $addingGiftingRule = $this->createAddingGiftingRule();
        $this->createGiftItemManager($addingGiftingRule)->setGiftItemsForQuote($quote);
        $giftQtyBefore = $this->getItemByArrayIdx($quote, 0)->getQty();

        $qtyUpdatingGiftingRule = $this->createIncrementQtyGiftingRule();
        $this->createGiftItemManager($qtyUpdatingGiftingRule)->setGiftItemsForQuote($quote);
        $giftQtyAfter = $this->getItemByArrayIdx($quote, 0)->getQty();

        $this->assertSame($giftQtyBefore + 1, $giftQtyAfter, 'Gift item qty does not match the expected value.');
    }
}
