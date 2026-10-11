<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Integration;

use Iranimij\OpenLabel\Api\LabelRepositoryInterface;
use Iranimij\OpenLabel\Model\Css\StylesheetGenerator;
use Iranimij\OpenLabel\Model\Rule\Condition\Combine;
use Iranimij\OpenLabel\Model\Rule\Condition\Product as ProductCondition;
use Iranimij\OpenLabel\Test\Fixture\Design as DesignFixture;
use Iranimij\OpenLabel\Test\Fixture\Label as LabelFixture;
use Iranimij\OpenLabel\Test\Fixture\ScheduledSearchIndex;
use Iranimij\OpenLabel\Test\Integration\Helper\QueryCounter;
use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Model\ListItemProcessor;
use Iranimij\OpenLabelHyva\Plugin\ProductImagePlugin;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Listing slot wired through the storefront DI (docs/hyva-integration.md §1, 02 · Architecture §10).
 */
#[AppArea('frontend')]
#[DbIsolation(false)]
#[DataFixture(ScheduledSearchIndex::class)]
class ListingSlotTest extends TestCase
{
    private const SKU_PREFIX = 'ol-hyva-list-';
    private const LISTING_SIZE = 36;

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

    // 2.4.7: method-level fixtures replace the class-level ones, so the search index is scheduled here too.
    #[DataFixture(ScheduledSearchIndex::class)]
    #[DataFixture(DesignFixture::class, ['store_texts' => [0 => ['text' => '-{SAVE_PERCENT}%']]], 'design')]
    public function testA36ProductListingGetsItsLabelsWithOneQuery(): void
    {
        $this->label();
        $om = Bootstrap::getObjectManager();
        $products = $this->loadListing();
        $layout = $om->get(LayoutInterface::class);
        $plugin = $om->get(ProductImagePlugin::class);

        $html = [];
        $queries = $om->get(QueryCounter::class)->count(function () use ($products, $layout, $plugin, &$html): void {
            foreach ($products as $product) {
                $image = $layout->createBlock(Image::class)
                    ->setTemplate(ProductImagePlugin::LIST_TEMPLATE)
                    ->setData('product_id', (int) $product->getId());
                $html[] = $plugin->afterToHtml($image, '<img src="p.jpg" width="240" height="300">');
            }
        });

        self::assertSame(1, $queries, 'the loaded collection was remembered: one label query for the page');
        self::assertCount(self::LISTING_SIZE, $html);
        self::assertMatchesRegularExpression(
            '#^<span class="ol-anchor"><img src="p.jpg" width="240" height="300"><span class="ol-stack [^"]+"><span class="ol-label ol-label--text ol-d-\d+ ol-shape-pill">-25%</span></span>\s*</span>$#',
            $html[0]
        );
    }

    // 2.4.7: method-level fixtures replace the class-level ones, so the search index is scheduled here too.
    #[DataFixture(ScheduledSearchIndex::class)]
    #[DataFixture(DesignFixture::class, [], 'design')]
    public function testListItemProcessorTagsTheBlockCacheEntry(): void
    {
        $this->label();
        $om = Bootstrap::getObjectManager();
        $product = $this->loadListing()[0];
        $block = $om->get(LayoutInterface::class)->createBlock(Template::class)->setData('cache_tags', ['cat_p_1']);

        $om->get(ListItemProcessor::class)->beforeListItemToHtml($block, $product);

        $designId = (int) DataFixtureStorageManager::getStorage()->get('design')->getDesignId();
        self::assertSame(['cat_p_1', 'openlabel_' . $this->labelId, 'openlabel_design_' . $designId], $block->getData('cache_tags'));
    }

    public function testHyvaContainerRootsAreInTheGeneratedStylesheet(): void
    {
        $css = Bootstrap::getObjectManager()->get(StylesheetGenerator::class)->build();

        self::assertStringContainsString('.product-item{container-type: inline-size}', $css);
        self::assertStringContainsString('#gallery-main{container-type: inline-size}', $css);
        self::assertStringContainsString('.fixed #gallery-main>.ol-stack{display: none}', $css);
    }

    private function label(): void
    {
        $label = Bootstrap::getObjectManager()->get(LabelFixture::class)->apply([
            'name' => 'Hyva listing',
            'design_id' => (int) DataFixtureStorageManager::getStorage()->get('design')->getDesignId(),
            'conditions_serialized' => json_encode([
                'type' => Combine::class, 'aggregator' => 'all', 'value' => '1',
                'conditions' => [[
                    'type' => ProductCondition::class, 'attribute' => 'sku', 'operator' => '{}', 'value' => self::SKU_PREFIX,
                ]],
            ]),
        ]);
        $this->labelId = (int) $label->getLabelId();
    }

    /**
     * @return \Magento\Catalog\Model\Product[]
     */
    private function loadListing(): array
    {
        $om = Bootstrap::getObjectManager();
        $skus = array_map(static fn (int $i): string => sprintf('%s%02d', self::SKU_PREFIX, $i), range(1, self::LISTING_SIZE));
        $existing = $om->create(CollectionFactory::class)->create()->addAttributeToFilter('sku', ['in' => $skus])
            ->getColumnValues('sku');
        foreach (array_diff($skus, $existing) as $sku) {
            $om->get(ProductFixture::class)->apply(['sku' => $sku, 'price' => 20, 'special_price' => 15]);
        }
        if (array_diff($skus, $existing) !== []) {
            $om->get(LabelRepositoryInterface::class)->save(
                $om->get(LabelRepositoryInterface::class)->getById((int) $this->labelId)
            );
        }

        $collection = $om->create(CollectionFactory::class)->create()
            ->addAttributeToFilter('sku', ['in' => $skus])
            ->addAttributeToSelect(['name', 'special_price', 'price'])
            ->addPriceData(0, 1)
            ->setOrder('sku', 'ASC');
        $collection->load();

        return array_values($collection->getItems());
    }
}
