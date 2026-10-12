<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Plugin;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Plugin\ListItemPlugin;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class ListItemPluginTest extends TestCase
{
    /** The relevant parts of Hyvä's product card (Magento_Catalog::product/list/item.phtml, 1.4 and 1.5). */
    private const CARD = '<form class="product-item"><div class="mt-2"><a class="product-item-link" href="/bag.html">Bag</a></div>'
        . '<div class="pt-1">price</div><div class="mt-auto"><button class="btn btn-primary" data-addto="cart"><span>Add</span></button>'
        . '<button data-addto="wishlist">♡</button></div></form>';

    private bool $enabled = true;

    /** @var array<string, string> position => html the renderer returns */
    private array $inline = ['under_name' => '[name]', 'near_cart' => '[cart]'];

    public function testUnderTheNameAndNextToAddToCart(): void
    {
        $html = $this->plugin()->afterToHtml($this->card(), self::CARD);

        self::assertStringContainsString('href="/bag.html">Bag</a>[name]</div>', $html);
        self::assertStringContainsString('<span>Add</span></button>[cart]<button data-addto="wishlist">', $html);
    }

    public function testACardWithoutACartButtonGetsOnlyTheNameLabels(): void
    {
        $card = str_replace('data-addto="cart"', '', self::CARD);

        $html = $this->plugin()->afterToHtml($this->card(), $card);

        self::assertStringContainsString('Bag</a>[name]', $html);
        self::assertStringNotContainsString('[cart]', $html);
    }

    public function testCardsWithoutInlineLabelsAreUnchanged(): void
    {
        $this->inline = [];

        self::assertSame(self::CARD, $this->plugin()->afterToHtml($this->card(), self::CARD));
    }

    public function testOtherTemplatesAndTheDisabledModuleAreLeftAlone(): void
    {
        self::assertSame(self::CARD, $this->plugin()->afterToHtml($this->card('Magento_Catalog::product/view/addto.phtml'), self::CARD));
        $this->enabled = false;
        self::assertSame(self::CARD, $this->plugin()->afterToHtml($this->card(), self::CARD));
    }

    public function testACardWithoutAProductIsLeftAlone(): void
    {
        self::assertSame(self::CARD, $this->plugin()->afterToHtml($this->card(ListItemPlugin::TEMPLATE, false), self::CARD));
    }

    private function card(string $template = ListItemPlugin::TEMPLATE, bool $withProduct = true): View
    {
        $product = $withProduct ? $this->createStub(Product::class) : null;
        $block = $this->createStub(View::class);
        $block->method('getTemplate')->willReturn($template);
        $block->method('getData')->willReturnCallback(fn (string $key = '') => $key === 'product' ? $product : null);

        return $block;
    }

    private function plugin(): ListItemPlugin
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturnCallback(fn (): bool => $this->enabled);
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('getInlineHtml')->willReturnCallback(
            fn ($product, string $area, string $position): string => $area === 'listing' ? ($this->inline[$position] ?? '') : ''
        );

        return new ListItemPlugin($labels, $renderer);
    }
}
