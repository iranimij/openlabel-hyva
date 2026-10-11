<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Model;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Model\ListItemProcessor;
use Iranimij\OpenLabelHyva\Plugin\CollectionPreloadPlugin;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ListItemProcessorTest extends TestCase
{
    public function testLabelTagsAreAddedToTheItemBlockCacheTags(): void
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn(7);
        $labels = $this->createMock(Labels::class);
        $labels->method('isEnabled')->willReturn(true);
        $labels->expects(self::once())->method('remember')->with([$product]);
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('collectIdentities')->willReturn(['openlabel_3', 'openlabel_design_5']);
        /** @var Template $block */
        $block = (new ObjectManager($this))->getObject(Template::class);
        $block->setData('cache_tags', ['cat_p_7']);

        (new ListItemProcessor($labels, $renderer))->beforeListItemToHtml($block, $product);

        self::assertSame(['cat_p_7', 'openlabel_3', 'openlabel_design_5'], $block->getData('cache_tags'));
    }

    public function testNothingHappensWhenDisabled(): void
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturn(false);
        $renderer = $this->createMock(LabelRenderer::class);
        $renderer->expects(self::never())->method('collectIdentities');
        /** @var Template $block */
        $block = (new ObjectManager($this))->getObject(Template::class);

        (new ListItemProcessor($labels, $renderer))->beforeListItemToHtml($block, $this->createStub(Product::class));

        self::assertNull($block->getData('cache_tags'));
    }

    public function testLoadedCollectionsAreRemembered(): void
    {
        $items = [3 => $this->createStub(Product::class), 4 => $this->createStub(Product::class)];
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
        $collection->method('isLoaded')->willReturn(true);
        $labels = $this->createMock(Labels::class);
        $labels->method('isEnabled')->willReturn(true);
        $labels->expects(self::once())->method('remember')->with(array_values($items));

        $result = (new CollectionPreloadPlugin($labels))->afterLoad($collection, $collection);

        self::assertSame($collection, $result);
    }

    public function testHugeCollectionsAreNotRemembered(): void
    {
        $items = array_fill(0, CollectionPreloadPlugin::MAX_PRODUCTS + 1, $this->createStub(Product::class));
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
        $collection->method('isLoaded')->willReturn(true);
        $labels = $this->createMock(Labels::class);
        $labels->method('isEnabled')->willReturn(true);
        $labels->expects(self::never())->method('remember');

        (new CollectionPreloadPlugin($labels))->afterLoad($collection, $collection);
    }

    public function testAnUnloadedCollectionIsNotForcedToLoad(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('isLoaded')->willReturn(false);
        $collection->expects(self::never())->method('getItems');
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturn(true);

        (new CollectionPreloadPlugin($labels))->afterLoad($collection, $collection);
    }
}
