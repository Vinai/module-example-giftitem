<?php

declare(strict_types=1);

namespace Example\GiftItem\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Quote\Model\Quote;

use function \array_sum as sum;
use function \array_filter as filter;
use function \array_map as map;
use function \array_values as values;

class FreeBottleWithAnyBagGiftRule implements GiftingRuleInterface
{
    const BAG_ATTRIBUTE_SET_ID = 15;
    const GIFT_SKU = '24-UG06';
    const RULE_CODE = 'free_bottle';

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductResource
     */
    private $productResource;

    public function __construct(ProductFactory $productFactory, ProductResource $productResource)
    {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
    }

    private function countBagsInCart(Quote $quote): int
    {
        return $this->sumQuoteItemQtys($this->filterBags($quote->getAllItems()));
    }

    /**
     * @param Quote\Item[] $items
     * @return int
     */
    private function sumQuoteItemQtys(array $items): int
    {
        return sum(map(function (Quote\Item $item) {
            return $item->getQty();
        }, $items));
    }

    /**
     * @param Quote\Item[] $items
     * @return Quote\Item[]
     */
    private function filterBags(array $items): array
    {
        return filter($items, [$this, 'isBag']);
    }

    private function isBag(Quote\Item $item): bool
    {
        return self::BAG_ATTRIBUTE_SET_ID == $item->getProduct()->getAttributeSetId();
    }

    private function createGiftBottle(): Product
    {
        $product = $this->productFactory->create();
        $giftId = $this->productResource->getIdBySku(self::GIFT_SKU);
        if (! $giftId) {
            throw new \OutOfBoundsException(sprintf('The gift bottle SKU %s does not exist.', self::GIFT_SKU));
        }
        $this->productResource->load($product, $giftId);
        $product->addCustomOption(self::GIFT_TYPE, self::RULE_CODE);

        return $product;
    }

    /**
     * @param Quote\Item[] $items
     * @return Quote\Item[]
     */
    private function filterGiftBottles(array $items): array
    {
        return values(filter($items, [$this, 'isGiftBottle']));
    }

    private function isGiftBottle(Quote\Item $item): bool
    {
        if ($item->getSku() === self::GIFT_SKU) {
            $option = $item->getOptionByCode(self::GIFT_TYPE);

            return $option && $option->getValue() === self::RULE_CODE;
        }

        return false;
    }

    public function getGiftsToAdd(Quote $quote, array $currentGifts)
    {
        $bottles = $this->filterGiftBottles($currentGifts);
        $bagCount = $this->countBagsInCart($quote);
        if (empty($bottles) && $bagCount > 0) {
            return [$this->createGiftBottle()];
        }

        return [];
    }

    public function getGiftsToRemove(Quote $quote, array $currentGifts)
    {
        $bottles = $this->filterGiftBottles($currentGifts);
        $bagCount = $this->countBagsInCart($quote);

        return $bagCount === 0 ? $bottles : [];
    }

    public function getGiftItemQtyUpdates(Quote $quote, array $currentGifts)
    {
        $bagCount = $this->countBagsInCart($quote);
        $bottles = $this->filterGiftBottles($currentGifts);
        $bottleCount = $this->sumQuoteItemQtys($bottles);
        if ($bottleCount > $bagCount) {
            return [$bottles[0]->getId() => $bagCount];
        }
        return [];
    }

}
