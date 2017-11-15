<?php

declare(strict_types=1);

namespace Example\GiftItem\Test\Integration\Block;

use Example\GiftItem\Block\GiftItemInfo;
use Example\GiftItem\Model\GiftItemManager;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Item;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class GiftItemInfoTest extends TestCase
{
    private function createInstance(): GiftItemInfo
    {
        return ObjectManager::getInstance()->create(GiftItemInfo::class);
    }

    public function testIsTemplateBlock()
    {
        $block = $this->createInstance();
        $this->assertInstanceOf(Template::class, $block);
        $this->assertSame('Example_GiftItem::gift-info.phtml', $block->getTemplate());
        $this->assertFileExists($block->getTemplateFile());
    }

    public function testReturnsEmptyStringForNonGiftItem()
    {
        $block = $this->createInstance();
        $nonGiftItem = ObjectManager::getInstance()->create(Item::class);
        $block->setData('item', $nonGiftItem);
        $this->assertSame('', $block->toHtml());
    }

    public function testReturnsHtmlForGiftItem()
    {
        /** @var Item $giftItem */
        $giftItem = ObjectManager::getInstance()->create(Item::class);
        $giftItem->addOption(['code' => GiftItemManager::OPTION_IS_GIFT, 'value' => 1]);

        $block = $this->createInstance();
        $block->setData('item', $giftItem);
        $this->assertNotEmpty($block->toHtml());
    }
}
