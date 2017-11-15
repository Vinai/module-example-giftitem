<?php

declare(strict_types=1);

namespace Example\GiftItem\Model\Totals;

use Example\GiftItem\Model\GiftItemManager;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote\Item\AbstractItem;

use function array_filter as filter;
use function array_map as map;
use function array_sum as sum;

class GiftItemAddressTotal extends AbstractTotal
{
    public function getGiftItemTotalSum(AbstractItem ...$items)
    {
        return $this->sumGiftItems('getRowTotal', ...$items);
    }

    public function getGiftItemBaseTotalSum(AbstractItem ...$items)
    {
        return $this->sumGiftItems('getBaseRowTotal', ...$items);
    }

    private function sumGiftItems($fn, AbstractItem ...$items)
    {
        return sum($this->mapGiftItems($fn, ...$items));
    }

    private function mapGiftItems($fn, AbstractItem ...$items)
    {
        return map(function (AbstractItem $item) use ($fn) {
            return is_callable($fn) ? $fn($item) : $item->{$fn}();
        }, filter($items, [$this, 'isGift']));
    }

    private function isGift(AbstractItem $item)
    {
        $option = $item->getOptionByCode(GiftItemManager::OPTION_IS_GIFT);

        return $option && $option->getValue();
    }

    private function zeroItem(AbstractItem $item)
    {
        $item->setData('calculation_price', 0);
        $item->setData('base_calculation_price', 0);
        $item->calcRowTotal();
    }

    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        $total->addTotalAmount('subtotal', -1 * $this->getGiftItemTotalSum(...$shippingAssignment->getItems()));
        $total->addBaseTotalAmount('subtotal', -1 * $this->getGiftItemBaseTotalSum(...$shippingAssignment->getItems()));
        $this->mapGiftItems([$this, 'zeroItem'], ...$shippingAssignment->getItems());

        return $this;
    }
}
