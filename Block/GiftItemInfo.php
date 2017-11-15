<?php

declare(strict_types=1);

namespace Example\GiftItem\Block;

use Example\GiftItem\Model\GiftItemManager;
use Magento\Framework\View\Element\Template;

class GiftItemInfo extends Template
{
    /**
     * @var GiftItemManager
     */
    private $giftItemManager;

    public function __construct(Template\Context $context, GiftItemManager $giftItemManager, array $data = [])
    {
        parent::__construct($context, $data);
        $this->giftItemManager = $giftItemManager;
        $this->setTemplate('Example_GiftItem::gift-info.phtml');
    }

    protected function _toHtml()
    {
        $item = $this->getData('item');
        if ($item && $this->giftItemManager->isGiftItem($item)) {
            return parent::_toHtml();
        }
        return '';
    }

}
