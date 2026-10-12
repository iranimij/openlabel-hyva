<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Plugin;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Plugin\InlineSlotPlugin;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Layout;
use PHPUnit\Framework\TestCase;

class InlineSlotPluginTest extends TestCase
{
    private bool $enabled = true;

    private ?Product $product = null;

    /** @var array<int, array{string, string}> area and position asked for */
    private array $asked = [];

    protected function setUp(): void
    {
        $this->product = $this->createStub(Product::class);
        $this->product->method('getId')->willReturn(7);
    }

    public function testProductPageElementsGetTheirInlineLabelsAfterThem(): void
    {
        $plugin = $this->plugin();
        $layout = $this->createStub(Layout::class);

        self::assertSame('<h1>Bag</h1>[under_name]', $plugin->afterRenderElement($layout, '<h1>Bag</h1>', 'product.info.title'));
        self::assertSame('<div>€5</div>[near_price]', $plugin->afterRenderElement($layout, '<div>€5</div>', 'product.info.price'));
        self::assertSame('<button>Add</button>[near_cart]', $plugin->afterRenderElement($layout, '<button>Add</button>', 'product.info.addtocart'));
        self::assertSame([['product', 'under_name'], ['product', 'near_price'], ['product', 'near_cart']], $this->asked);
    }

    public function testOtherElementsAreNotTouched(): void
    {
        self::assertSame('<div>x</div>', $this->plugin()->afterRenderElement($this->createStub(Layout::class), '<div>x</div>', 'product.info.review'));
        self::assertSame([], $this->asked);
    }

    public function testAnEmptyElementGetsNoLabels(): void
    {
        self::assertSame('', $this->plugin()->afterRenderElement($this->createStub(Layout::class), '', 'product.info.price'), 'no price, nothing to stand next to');
    }

    public function testWithoutAProductOrWithTheModuleOffNothingChanges(): void
    {
        $this->enabled = false;
        self::assertSame('<h1>Bag</h1>', $this->plugin()->afterRenderElement($this->createStub(Layout::class), '<h1>Bag</h1>', 'product.info.title'));

        $this->enabled = true;
        $this->product = null;
        self::assertSame('<h1>Bag</h1>', $this->plugin()->afterRenderElement($this->createStub(Layout::class), '<h1>Bag</h1>', 'product.info.title'));
    }

    private function plugin(): InlineSlotPlugin
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturnCallback(fn (): bool => $this->enabled);
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('getInlineHtml')->willReturnCallback(function ($product, string $area, string $position): string {
            $this->asked[] = [$area, $position];

            return '[' . $position . ']';
        });
        $helper = $this->createStub(CatalogHelper::class);
        $helper->method('getProduct')->willReturnCallback(fn (): ?Product => $this->product);

        return new InlineSlotPlugin($labels, $renderer, $helper, [
            'product.info.title' => 'under_name',
            'product.info.price' => 'near_price',
            'product.info.addtocart' => 'near_cart',
        ]);
    }
}
