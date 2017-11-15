<?php

declare(strict_types=1);

namespace Example\GiftItem\Test\Integration;

use Example\GiftItem\Model\GiftItemManager;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function \array_filter as filter;

class ProductToQuoteItemExplorationTest extends TestCase
{
    private function createQuote(): Quote
    {
        return ObjectManager::getInstance()->create(Quote::class);
    }

    private function isDateTimeOption(ProductCustomOptionInterface $option)
    {
        return in_array($option->getType(), [
            ProductCustomOptionInterface::OPTION_TYPE_DATE,
            ProductCustomOptionInterface::OPTION_TYPE_DATE_TIME,
        ], true);
    }

    private function getDateTimeOptionDummyValue()
    {
        return ['day' => 1, 'month' => 1, 'year' => date('Y') + 1, 'hour' => 1, 'minute' => 1];
    }

    private function buildBuyRequestData(ProductModel $product): array
    {
        $buyRequestData = ['qty' => 1];
        /** @var Option|ProductCustomOptionInterface $option */
        foreach ($product->getOptions() as $option) {
            if ($option->getIsRequire()) {
                $buyRequestData['options'][$option->getId()] = 1;
                if ($this->isDateTimeOption($option)) {
                    $buyRequestData['options'][$option->getId()] = $this->getDateTimeOptionDummyValue();
                }
            }
        }

        return $buyRequestData;
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testAddingItemsToQuote()
    {
        //$this->markTestSkipped('Exploration test');
        $quote = $this->createQuote();
        
        /** @var ProductResource $productResource */
        $productResource = ObjectManager::getInstance()->create(ProductResource::class);
        
        /** @var ProductModel $product */
        $product = ObjectManager::getInstance()->create(ProductModel::class);
        $productResource->load($product, 1);
        $buyRequestData = $this->buildBuyRequestData($product);
        $buyRequest = ObjectManager::getInstance()->create(DataObject::class, ['data' => $buyRequestData]);
        $quote->addProduct($product, $buyRequest);

        /** @var ProductModel $gift */
        $gift = ObjectManager::getInstance()->create(ProductModel::class);
        $productResource->load($gift, 1);
        $gift->addCustomOption(GiftItemManager::OPTION_IS_GIFT, 1);
        $quote->addProduct($gift, $buyRequest);
        
        $gifts = filter($quote->getAllItems(), function (Quote\Item $item) {
            $option = $item->getOptionByCode(GiftItemManager::OPTION_IS_GIFT);
            return $option && 1 == $option->getValue();
        });

        $this->assertCount(2, $quote->getAllItems());
        $this->assertCount(1, $gifts);
    }
}
