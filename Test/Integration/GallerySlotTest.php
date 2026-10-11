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
use Iranimij\OpenLabel\Test\Integration\Helper\QueryCounter;
use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Block\ProductLabels;
use Iranimij\OpenLabelHyva\Plugin\GalleryPlugin;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Product page slot (docs/hyva-integration.md §2, 02 · Architecture §10: ≤ 1 extra query).
 */
#[AppArea('frontend')]
#[DbIsolation(false)]
#[DataFixture(ScheduledSearchIndex::class)]
class GallerySlotTest extends TestCase
{
    private ?int $labelId = null;

    protected function setUp(): void
    {
        $om = Bootstrap::getObjectManager();
        $om->get(Labels::class)->_resetState();
        $om->get(LabelRenderer::class)->_resetState();
    }

    protected function tearDown(): void
    {
        if ($this->labelId !== null) {
            Bootstrap::getObjectManager()->get(LabelRepositoryInterface::class)->deleteById($this->labelId);
        }
    }

    #[DataFixture(ProductFixture::class, ['sku' => 'ol-hyva-pdp', 'price' => 40, 'special_price' => 30], 'product')]
    #[DataFixture(DesignFixture::class, ['store_texts' => [0 => ['text' => '-{SAVE_PERCENT}%']]], 'design')]
    public function testProductPageGalleryGetsItsLabelsWithOneQuery(): void
    {
        $om = Bootstrap::getObjectManager();
        $storage = DataFixtureStorageManager::getStorage();
        $this->labelId = (int) $om->get(LabelFixture::class)->apply([
            'name' => 'Hyva pdp',
            'design_id' => (int) $storage->get('design')->getDesignId(),
            'placements' => [['area' => 'product', 'position' => 'tr']],
            'conditions_serialized' => json_encode([
                'type' => Combine::class, 'aggregator' => 'all', 'value' => '1',
                'conditions' => [[
                    'type' => ProductCondition::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'ol-hyva-pdp',
                ]],
            ]),
        ])->getLabelId();
        $product = $om->get(ProductRepositoryInterface::class)->get('ol-hyva-pdp', false, 1, true);
        // The page's price box computes the (memoized) final price anyway; only what labels add is counted.
        $product->getPriceInfo()->getPrice('final_price')->getAmount();
        $layout = $om->get(LayoutInterface::class);
        $gallery = $layout->createBlock(Gallery::class, 'product.media')->setData('product', $product);
        $layout->addBlock(ProductLabels::class, GalleryPlugin::CHILD, 'product.media');

        $html = '';
        $counter = $om->get(QueryCounter::class);
        $queries = $counter->count(function () use ($om, $gallery, &$html): void {
            $html = $om->get(GalleryPlugin::class)->afterToHtml(
                $gallery,
                '<div class="w-full"><div id="gallery-main" class="relative mb-6"><img></div></div>'
            );
        });

        self::assertSame(1, $queries, implode("\n", $counter->last));
        self::assertMatchesRegularExpression(
            '#<div id="gallery-main" class="ol-anchor relative mb-6"><span class="ol-stack ol-stack--v ol-pos-te">'
            . '<span class="ol-label ol-label--text ol-d-\d+ ol-shape-pill">-25%</span></span><img>#',
            $html
        );
    }
}
