<?php

declare(strict_types=1);

namespace Example\GiftItem\Plugin;

use Example\GiftItem\Model\GiftItemManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use PHPUnit\Framework\TestCase;

class AddGiftItemPluginTest extends TestCase
{
    /**
     * @return GiftItemManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockGiftItemManager(): GiftItemManager
    {
        return $this->createMock(GiftItemManager::class);
    }

    /**
     * @return TotalsCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockTotalsCollector(): TotalsCollector
    {
        return $this->createMock(TotalsCollector::class);
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockQuote(): Quote
    {
        return $this->createMock(Quote::class);
    }

    private function createInstance($giftItemManager = null): AddGiftItemPlugin
    {
        return new AddGiftItemPlugin($giftItemManager ?? $this->createMockGiftItemManager());
    }

    public function testReturnsQuote()
    {
        $mockSubject = $this->createMockTotalsCollector();
        $mockQuote = $this->createMockQuote();
        $this->assertSame([$mockQuote], $this->createInstance()->beforeCollect($mockSubject, $mockQuote));
    }

    public function testTestDelegatesToGiftItemManager()
    {
        $mockSubject = $this->createMockTotalsCollector();
        $mockQuote = $this->createMockQuote();
        
        $giftItemManager = $this->createMockGiftItemManager();
        $giftItemManager->expects($this->once())->method('setGiftItemsForQuote')->with($mockQuote);

        $this->createInstance($giftItemManager)->beforeCollect($mockSubject, $mockQuote);
    }
}
