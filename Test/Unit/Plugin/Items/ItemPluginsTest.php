<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Plugin\Items;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Plugin\Items\CartItemPlugin;
use Iranimij\OpenLabelHyva\Plugin\Items\CompareListPlugin;
use Iranimij\OpenLabelHyva\Plugin\Items\MiniCartItemPlugin;
use Iranimij\OpenLabelHyva\Plugin\Items\WishlistItemPlugin;
use Magento\Catalog\Block\Product\Compare\ListCompare;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Checkout\CustomerData\Cart;
use Magento\Framework\Escaper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Wishlist\Block\Customer\Wishlist\Item\Column\Info;
use Magento\Wishlist\Model\Item as WishlistItem;
use PHPUnit\Framework\TestCase;

/**
 * Item lists (1.1): cart page, mini cart, wish list and compare list.
 */
class ItemPluginsTest extends TestCase
{
    private bool $enabled = true;

    /** @var string[] areas asked for */
    private array $areas = [];

    private string $html = '<span class="ol-stack ol-inline">L</span>';

    public function testTheCartItemGetsItsLabelsAfterTheNameHeading(): void
    {
        $block = $this->createStub(Renderer::class);
        $block->method('getProduct')->willReturn($this->product(7));
        $card = '<article><h3 class="product-item-name text-xl"><a href="/bag.html"><strong>Bag</strong></a></h3><p class="price-box">€5</p></article>';

        $html = $this->cart()->afterToHtml($block, $card);

        self::assertStringContainsString('</a></h3>' . $this->html . '<p class="price-box">', $html);
        self::assertSame(['cart'], $this->areas);
        self::assertSame('<article>no name</article>', $this->cart()->afterToHtml($block, '<article>no name</article>'));
    }

    public function testTheMiniCartItemCarriesItsLabelsNextToItsPrice(): void
    {
        $item = $this->createStub(QuoteItem::class);
        $item->method('getProduct')->willReturn($this->product(7));

        $data = $this->miniCart()->afterGetItemData($this->createStub(AbstractItem::class), ['product_price' => '<span>€5</span>'], $item);
        self::assertSame($this->html, $data['openlabel']);
        self::assertSame(['minicart'], $this->areas);

        // The Tax module renders the price again; the labels are added to the final one.
        $data['product_price'] = '<span>€5.95 incl. tax</span>';
        $section = $this->miniCart()->afterGetSectionData($this->createStub(Cart::class), ['items' => [$data, ['product_price' => 'x']]]);
        self::assertSame('<span>€5.95 incl. tax</span>' . $this->html, $section['items'][0]['product_price']);
        self::assertArrayNotHasKey('openlabel', $section['items'][0]);
        self::assertSame('x', $section['items'][1]['product_price']);
    }

    public function testTheWishListItemGetsItsLabelsAfterItsName(): void
    {
        $item = $this->createStub(WishlistItem::class);
        $item->method('getProduct')->willReturn($this->product(7));
        $column = $this->createStub(Info::class);
        $column->method('getData')->willReturnCallback(static fn (string $key = '') => $key === 'item' ? $item : null);

        self::assertSame('<span>Bag</span>' . $this->html, $this->wishlist()->afterToHtml($column, '<span>Bag</span>'));
        self::assertSame(['wishlist'], $this->areas);
    }

    public function testEachComparedProductGetsItsLabelsAfterItsName(): void
    {
        $list = $this->createStub(ListCompare::class);
        $list->method('getItems')->willReturn([$this->product(7), $this->product(8)]);
        $list->method('getProductUrl')->willReturnCallback(static fn (Product $p): string => '/p' . $p->getId() . '.html');
        $table = '<td><a href="/p7.html" class="block"><img></a><strong>
            <a href="/p7.html" title="A">A</a>
        </strong></td><td><a href="/p8.html"><img></a><strong><a href="/p8.html">B</a></strong></td>';

        $html = $this->compare()->afterToHtml($list, $table);

        self::assertSame(2, substr_count($html, $this->html));
        self::assertMatchesRegularExpression('#A</a>\s*</strong>' . preg_quote($this->html, '#') . '</td>#', $html);
        self::assertStringEndsWith('B</a></strong>' . $this->html . '</td>', $html);
    }

    public function testNothingChangesWhenLabelsAreOff(): void
    {
        $this->enabled = false;
        $block = $this->createStub(Renderer::class);
        $card = '<h3 class="product-item-name">Bag</h3>';

        self::assertSame($card, $this->cart()->afterToHtml($block, $card));
        self::assertSame([], $this->areas);
    }

    private function product(int $id): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);

        return $product;
    }

    private function labels(): Labels
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturnCallback(fn (): bool => $this->enabled);

        return $labels;
    }

    private function renderer(): LabelRenderer
    {
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('getAreaHtml')->willReturnCallback(function ($product, string $area): string {
            $this->areas[] = $area;

            return $this->html;
        });

        return $renderer;
    }

    private function cart(): CartItemPlugin
    {
        return new CartItemPlugin($this->labels(), $this->renderer());
    }

    private function miniCart(): MiniCartItemPlugin
    {
        return new MiniCartItemPlugin($this->labels(), $this->renderer());
    }

    private function wishlist(): WishlistItemPlugin
    {
        return new WishlistItemPlugin($this->labels(), $this->renderer());
    }

    private function compare(): CompareListPlugin
    {
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeUrl')->willReturnArgument(0);

        return new CompareListPlugin($this->labels(), $this->renderer(), $escaper);
    }
}
