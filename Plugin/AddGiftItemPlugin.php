<?php

declare(strict_types=1);

namespace Example\GiftItem\Plugin;

use Example\GiftItem\Model\GiftItemManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;

class AddGiftItemPlugin
{
    /**
     * @var GiftItemManager
     */
    private $giftItemManager;

    public function __construct(GiftItemManager $giftItemManager)
    {
        $this->giftItemManager = $giftItemManager;
    }

    public function beforeCollect(TotalsCollector $subject, Quote $quote)
    {
        $this->giftItemManager->setGiftItemsForQuote($quote);
        return [$quote];
    }
}
