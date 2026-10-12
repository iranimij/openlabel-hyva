<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Integration;

use Iranimij\OpenLabel\Api\LabelRepositoryInterface;
use Iranimij\OpenLabel\Model\Rule\Condition\Combine;
use Iranimij\OpenLabel\Model\Rule\Condition\Product as ProductCondition;
use Iranimij\OpenLabel\Test\Fixture\Design as DesignFixture;
use Iranimij\OpenLabel\Test\Fixture\Label as LabelFixture;
use Iranimij\OpenLabel\Test\Fixture\ScheduledSearchIndex;
use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Plugin\InlineSlotPlugin;
use Iranimij\OpenLabelHyva\Plugin\ListItemPlugin;
use Iranimij\OpenLabelHyva\Plugin\PriceBoxPlugin;
use Iranimij\OpenLabelHyva\Plugin\ProductImagePlugin;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Pricing\Render\FinalPriceBox;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Interception\PluginListInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Labels in the product text (1.1): next to the price, next to Add to Cart, under the name.
 */
#[AppArea('frontend')]
#[DbIsolation(false)]
#[DataFixture(ScheduledSearchIndex::class)]
#[DataFixture(ProductFixture::class, ['sku' => 'ol-hyva-inline', 'price' => 20, 'special_price' => 15], 'product')]
#[DataFixture(DesignFixture::class, ['store_texts' => [0 => ['text' => 'Inline']]], 'design')]
class InlineSlotsTest extends TestCase
{
    private ?int $labelId = null;

    protected function setUp(): void
    {
        $om = Bootstrap::getObjectManager();
        $om->get(Labels::class)->_resetState();
        $om->get(LabelRenderer::class)->_resetState();
        $label = $om->get(LabelFixture::class)->apply([
            'name' => 'Hyva inline',
            'design_id' => (int) DataFixtureStorageManager::getStorage()->get('design')->getDesignId(),
            'conditions_serialized' => json_encode([
                'type' => Combine::class, 'aggregator' => 'all', 'value' => '1',
                'conditions' => [[
                    'type' => ProductCondition::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'ol-hyva-inline',
                ]],
            ]),
            'placements' => [
                ['area' => 'listing', 'position' => 'tl'],
                ['area' => 'listing', 'position' => 'under_name'],
                ['area' => 'listing', 'position' => 'near_cart'],
                ['area' => 'listing', 'position' => 'near_price'],
                ['area' => 'product', 'position' => 'near_cart'],
            ],
        ]);
        $this->labelId = (int) $label->getLabelId();
    }

    protected function tearDown(): void
    {
        $om = Bootstrap::getObjectManager();
        if ($this->labelId !== null) {
            $om->get(LabelRepositoryInterface::class)->deleteById($this->labelId);
        }
        $registry = $om->get(Registry::class);
        $registry->unregister('current_product');
        $registry->unregister('product');
    }

    public function testTheStorefrontWiresTheThreeSlots(): void
    {
        $plugins = Bootstrap::getObjectManager()->get(PluginListInterface::class);

        self::assertContains('openlabel_inline_slots', $this->afterPlugins($plugins, Layout::class, 'renderElement'));
        self::assertContains('openlabel_near_price', $this->afterPlugins($plugins, FinalPriceBox::class, 'toHtml'));
        self::assertContains('openlabel_list_item_slots', $this->afterPlugins($plugins, View::class, 'toHtml'));
        self::assertInstanceOf(InlineSlotPlugin::class, $plugins->getPlugin(Layout::class, 'openlabel_inline_slots'));
    }

    /**
     * @return string[] codes of the "after" plugins of a method
     */
    private function afterPlugins(PluginListInterface $plugins, string $type, string $method): array
    {
        $codes = [];
        $next = $plugins->getNext($type, $method);
        while (is_array($next)) {
            $codes = array_merge($codes, $next[\Magento\Framework\Interception\DefinitionInterface::LISTENER_AFTER] ?? []);
            $around = $next[\Magento\Framework\Interception\DefinitionInterface::LISTENER_AROUND] ?? null;
            $next = $around === null ? null : $plugins->getNext($type, $method, $around);
        }

        return $codes;
    }

    public function testTheProductCardGetsTheNameAndCartLabelsAndTheImageOnlyItsOwn(): void
    {
        $om = Bootstrap::getObjectManager();
        $product = $om->get(ProductRepositoryInterface::class)->get('ol-hyva-inline');
        $layout = $om->get(LayoutInterface::class);
        $card = $layout->createBlock(View::class)->setTemplate(ListItemPlugin::TEMPLATE)->setData('product', $product);
        $image = $layout->createBlock(Image::class)->setTemplate(ProductImagePlugin::LIST_TEMPLATE)
            ->setData('product_id', (int) $product->getId());

        $html = $om->get(ListItemPlugin::class)->afterToHtml(
            $card,
            '<a class="product-item-link" href="/p.html">P</a><button data-addto="cart">Add</button>'
        );
        $imageHtml = (string) $om->get(ProductImagePlugin::class)->afterToHtml($image, '<img src="p.jpg">');

        self::assertMatchesRegularExpression('#P</a><span class="ol-stack ol-stack--h ol-inline ol-inline--under-name">.*Inline#s', $html);
        self::assertMatchesRegularExpression('#Add</button><span class="ol-stack ol-stack--h ol-inline ol-inline--near-cart">.*Inline#s', $html);
        self::assertSame(1, substr_count($imageHtml, 'class="ol-stack '), 'the image slot holds the top-left stack only');
        self::assertStringNotContainsString('ol-inline', $imageHtml);
    }

    public function testTheProductPageAddToCartGetsItsLabel(): void
    {
        $om = Bootstrap::getObjectManager();
        $product = $om->get(ProductRepositoryInterface::class)->get('ol-hyva-inline');
        $om->get(Registry::class)->register('current_product', $product);

        $html = $om->get(InlineSlotPlugin::class)->afterRenderElement(
            $om->get(Layout::class),
            '<button id="product-addtocart-button">Add</button>',
            'product.info.addtocart'
        );

        self::assertMatchesRegularExpression('#Add</button><span class="ol-stack ol-stack--h ol-inline ol-inline--near-cart">.*Inline#s', (string) $html);
        self::assertSame(
            '<h1>P</h1>',
            $om->get(InlineSlotPlugin::class)->afterRenderElement($om->get(Layout::class), '<h1>P</h1>', 'product.info.title'),
            'no label placed under the name on the product page'
        );
    }
}
