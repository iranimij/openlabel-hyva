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
use Iranimij\OpenLabelHyva\Block\Script;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableFixture;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Product page labels that follow the chosen variant (1.1): every variant's labels are in the page, hidden, and
 * the script only switches them; parent and variants are resolved with one query.
 */
#[AppArea('frontend')]
#[DbIsolation(false)]
#[DataFixture(ScheduledSearchIndex::class)]
#[DataFixture(AttributeFixture::class, as: 'attr')]
#[DataFixture(ProductFixture::class, ['sku' => 'ol-hyva-var-a', 'price' => 50, '$attr.attribute_code$' => '$attr.option_1$'], 'var_a')]
#[DataFixture(ProductFixture::class, ['sku' => 'ol-hyva-var-b', 'price' => 60, '$attr.attribute_code$' => '$attr.option_2$'], 'var_b')]
#[DataFixture(ConfigurableFixture::class, ['sku' => 'ol-hyva-var', '_options' => ['$attr$'], '_links' => ['$var_a$', '$var_b$']], 'conf')]
#[DataFixture(DesignFixture::class, ['store_texts' => [0 => ['text' => 'Variant A']]], 'design')]
class VariantLabelsTest extends TestCase
{
    private ?int $labelId = null;

    protected function setUp(): void
    {
        $om = Bootstrap::getObjectManager();
        $om->get(Labels::class)->_resetState();
        $om->get(LabelRenderer::class)->_resetState();
        $label = $om->get(LabelFixture::class)->apply([
            'name' => 'Hyva variant',
            'apply_to_parent' => true,
            'design_id' => (int) DataFixtureStorageManager::getStorage()->get('design')->getDesignId(),
            'conditions_serialized' => json_encode([
                'type' => Combine::class, 'aggregator' => 'all', 'value' => '1',
                'conditions' => [[
                    'type' => ProductCondition::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'ol-hyva-var-a',
                ]],
            ]),
            'placements' => [['area' => 'product', 'position' => 'tr']],
        ]);
        $this->labelId = (int) $label->getLabelId();
    }

    protected function tearDown(): void
    {
        if ($this->labelId !== null) {
            Bootstrap::getObjectManager()->get(LabelRepositoryInterface::class)->deleteById($this->labelId);
        }
    }

    public function testEveryVariantsLabelsAreInThePageWithOneLabelQuery(): void
    {
        $om = Bootstrap::getObjectManager();
        $parent = $om->get(ProductRepositoryInterface::class)->get('ol-hyva-var', false, 1, true);
        $parent->getTypeInstance()->getUsedProducts($parent); // loaded by the options block before the gallery
        $storage = DataFixtureStorageManager::getStorage();
        $variantA = (int) $storage->get('var_a')->getId();
        $renderer = $om->get(LabelRenderer::class);

        $html = '';
        $queries = $om->get(QueryCounter::class)->count(function () use ($renderer, $parent, &$html): void {
            $html = $renderer->getProductPageStacksHtml($parent);
        });

        self::assertSame(1, $queries, 'the configurable and its variants: one label query');
        self::assertMatchesRegularExpression('#<span class="ol-label [^"]+" data-ol-variant="0">Variant A</span>#', $html, 'shown as the page opens (apply to parent)');
        self::assertMatchesRegularExpression('#<span class="ol-label [^"]+ ol-label--hidden" data-ol-variant="' . $variantA . '">Variant A</span>#', $html);
        self::assertStringNotContainsString('data-ol-variant="' . (int) $storage->get('var_b')->getId() . '"', $html, 'variant B has no label');
        self::assertTrue($renderer->isScriptNeeded());
    }

    public function testTheScriptBlockRendersOnlyWhenAPageNeedsIt(): void
    {
        $om = Bootstrap::getObjectManager();
        $block = $om->get(LayoutInterface::class)->createBlock(Script::class)
            ->setTemplate('Iranimij_OpenLabelHyva::js/labels.phtml');

        self::assertSame('', $block->toHtml(), 'nothing rendered yet');

        $parent = $om->get(ProductRepositoryInterface::class)->get('ol-hyva-var', false, 1, true);
        $om->get(LabelRenderer::class)->getProductPageStacksHtml($parent);

        $html = $block->toHtml();
        self::assertStringContainsString('<script>', $html);
        self::assertStringContainsString("'configurable-selection-changed'", $html);
    }
}
