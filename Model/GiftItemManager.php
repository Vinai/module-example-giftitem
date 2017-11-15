<?php

declare(strict_types=1);

namespace Example\GiftItem\Model;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;

use function \array_map as map;
use function \array_filter as filter;

class GiftItemManager
{
    const OPTION_IS_GIFT = 'is_gift';
    
    /**
     * @var GiftingRuleInterface
     */
    private $giftingRule;

    public function __construct(GiftingRuleInterface $giftingRule)
    {
        $this->giftingRule = $giftingRule;
    }

    public function setGiftItemsForQuote(Quote $quote)
    {
        $currentGifts = $this->getCurrentGifts($quote);
        
        $giftsToRemove = (array) $this->giftingRule->getGiftsToRemove($quote, $currentGifts);
        $giftsToAdd = (array) $this->giftingRule->getGiftsToAdd($quote, $currentGifts);
        $qtyUpdates = (array) $this->giftingRule->getGiftItemQtyUpdates($quote, $currentGifts);

        $this->removeGifts($quote, $giftsToRemove);
        $this->applyGiftItemQtyUpdates($currentGifts, $qtyUpdates);
        $this->addGiftsToQuote($quote, $giftsToAdd);
    }

    /**
     * @param Quote $quote
     * @return Quote\Item[]
     */
    private function getCurrentGifts(Quote $quote): array
    {
        return filter((array) $quote->getAllItems(), function (Quote\Item $item) {
            $option = $item->getOptionByCode(static::OPTION_IS_GIFT);
            return $option && $option->getValue();
        });
    }

    /**
     * @param Quote $quote
     * @param Quote\Item[] $giftsToRemove
     */
    private function removeGifts(Quote $quote, array $giftsToRemove)
    {
        map([$quote, 'deleteItem'], $giftsToRemove);
    }

    /**
     * @param Quote $quote
     * @param Product[] $giftsToAdd
     */
    private function addGiftsToQuote(Quote $quote, array $giftsToAdd)
    {
        map(function (Product $product) use ($quote) {
            return $quote->addProduct($this->markAsGift($product));
        }, $giftsToAdd);
    }

    /**
     * @param Quote\Item[] $currentGifts
     * @param int[] $giftItemQtyUpdates
     */
    private function applyGiftItemQtyUpdates(array $currentGifts, array $giftItemQtyUpdates)
    {
        map(function (Quote\Item $gift) use ($giftItemQtyUpdates) {
            if (isset($giftItemQtyUpdates[$gift->getId()])) {
                $gift->setQty($giftItemQtyUpdates[$gift->getId()]);
            }
        }, $currentGifts);
    }

    public function markAsGift(Product $product): Product
    {
        $product->addCustomOption(static::OPTION_IS_GIFT, 1);
        return $product;
    }

    public function isGiftItem(AbstractItem $item): bool
    {
        $option = $item->getOptionByCode(static::OPTION_IS_GIFT);
        return $option && $option->getValue();
    }
}
