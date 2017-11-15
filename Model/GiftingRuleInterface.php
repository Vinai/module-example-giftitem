<?php

declare(strict_types=1);

namespace Example\GiftItem\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote;

interface GiftingRuleInterface
{
    const GIFT_TYPE = 'gift_type';
    
    /**
     * @param Quote $quote
     * @param Quote\Item[] $currentGifts
     * @return ProductInterface[]
     */
    public function getGiftsToAdd(Quote $quote, array $currentGifts);

    /**
     * @param Quote $quote
     * @param Quote\Item[] $currentGifts
     * @return Quote\Item[]
     */
    public function getGiftsToRemove(Quote $quote, array $currentGifts);

    /**
     * @param Quote $quote
     * @param Quote\Item[] $currentGifts
     * @return int[] Array keys: quote item IDs, array values: target qty
     */
    public function getGiftItemQtyUpdates(Quote $quote, array $currentGifts);
}
