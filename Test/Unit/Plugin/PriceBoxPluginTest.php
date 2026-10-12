<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Plugin;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Plugin\PriceBoxPlugin;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Render\FinalPriceBox;
use PHPUnit\Framework\TestCase;

class PriceBoxPluginTest extends TestCase
{
    private const PRICE = '<div class="price-box">€29.99</div>';

    private bool $enabled = true;

    public function testListingPricesGetTheLabelsNextToThem(): void
    {
        self::assertSame(self::PRICE . '[listing:near_price]', $this->plugin()->afterToHtml($this->box('item_list'), self::PRICE));
    }

    public function testOtherPriceZonesAreNotTouched(): void
    {
        self::assertSame(self::PRICE, $this->plugin()->afterToHtml($this->box('item_view'), self::PRICE), 'the product page has its own slot');
        self::assertSame(self::PRICE, $this->plugin()->afterToHtml($this->box(null), self::PRICE));
    }

    public function testNoPriceOrModuleOffMeansNoLabels(): void
    {
        self::assertSame('', $this->plugin()->afterToHtml($this->box('item_list'), ''));
        $this->enabled = false;
        self::assertSame(self::PRICE, $this->plugin()->afterToHtml($this->box('item_list'), self::PRICE));
    }

    public function testAPriceThatAlreadyHasTheLabelsIsLeftAlone(): void
    {
        $html = self::PRICE . '<span class="ol-stack ol-inline ol-inline--near-price"></span>';

        self::assertSame($html, $this->plugin()->afterToHtml($this->box('item_list'), $html));
    }

    private function box(?string $zone): FinalPriceBox
    {
        $product = $this->createStub(Product::class);
        $box = $this->createStub(FinalPriceBox::class);
        $box->method('getData')->willReturnCallback(fn (string $key = '') => $key === 'zone' ? $zone : null);
        $box->method('getSaleableItem')->willReturn($product);

        return $box;
    }

    private function plugin(): PriceBoxPlugin
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturnCallback(fn (): bool => $this->enabled);
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('getInlineHtml')->willReturnCallback(
            static fn ($product, string $area, string $position): string => '[' . $area . ':' . $position . ']'
        );

        return new PriceBoxPlugin($labels, $renderer);
    }
}
