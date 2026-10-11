<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Plugin;

use Iranimij\OpenLabel\Api\Data\ResolvedLabelInterface;
use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Plugin\ProductImagePlugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductImagePluginTest extends TestCase
{
    private const IMG = '<img src="a.jpg" width="240" height="300">';

    private bool $enabled = true;

    private bool $hasLabels = true;

    private ?ProductInterface $remembered = null;

    /** @var array<int, string> */
    private array $loadingSeen = [];

    public function testListImageIsWrappedWithTheLabelStacks(): void
    {
        $this->remembered = $this->createStub(ProductInterface::class);

        $html = $this->plugin()->afterToHtml($this->image(), self::IMG);

        self::assertSame('<span class="ol-anchor">' . self::IMG . '<span class="ol-stack">L</span></span>', $html);
        self::assertSame(['lazy'], $this->loadingSeen);
    }

    public function testTheProductImageLoadingAttributeIsReused(): void
    {
        $this->remembered = $this->createStub(ProductInterface::class);

        $this->plugin()->afterToHtml($this->image(['loading' => 'eager']), self::IMG);

        self::assertSame(['eager'], $this->loadingSeen);
    }

    public function testOtherImageTemplatesAreUntouched(): void
    {
        $image = $this->image();
        $image->setTemplate('Magento_Catalog::product/image_with_borders.phtml');

        self::assertSame(self::IMG, $this->plugin()->afterToHtml($image, self::IMG));
    }

    public function testProductsWithoutLabelsAreUntouched(): void
    {
        $this->hasLabels = false;

        self::assertSame(self::IMG, $this->plugin()->afterToHtml($this->image(), self::IMG));
    }

    public function testDisabledModuleLeavesTheImageAlone(): void
    {
        $this->enabled = false;

        self::assertSame(self::IMG, $this->plugin()->afterToHtml($this->image(), self::IMG));
    }

    public function testThemeThatAlreadyRendersTheStacksIsNotRenderedTwice(): void
    {
        $html = self::IMG . '<span class="ol-stack ol-pos-ts"></span>';

        self::assertSame($html, $this->plugin()->afterToHtml($this->image(), $html));
    }

    public function testProductNotFromACollectionIsLoaded(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')->with(42)
            ->willReturn($this->createStub(ProductInterface::class));

        $html = $this->plugin($repository)->afterToHtml($this->image(), self::IMG);

        self::assertStringStartsWith('<span class="ol-anchor">', $html);
    }

    public function testMissingProductLeavesTheImageAlone(): void
    {
        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException());

        self::assertSame(self::IMG, $this->plugin($repository)->afterToHtml($this->image(), self::IMG));
    }

    /**
     * @param array<string, string> $customAttributes
     * @return Image
     */
    private function image(array $customAttributes = []): Image
    {
        /** @var Image $image */
        $image = (new ObjectManager($this))->getObject(Image::class);
        $image->setTemplate(ProductImagePlugin::LIST_TEMPLATE);
        $image->setData('product_id', 42);
        $image->setData('custom_attributes', $customAttributes);

        return $image;
    }

    private function plugin(?ProductRepositoryInterface $repository = null): ProductImagePlugin
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturnCallback(fn (): bool => $this->enabled);
        $labels->method('getForProduct')->willReturnCallback(
            fn (): array => $this->hasLabels ? [$this->createStub(ResolvedLabelInterface::class)] : []
        );
        $labels->method('getRememberedProduct')->willReturnCallback(fn (): ?ProductInterface => $this->remembered);
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('getStacksHtml')->willReturnCallback(
            function (ProductInterface $product, string $area, string $loading): string {
                $this->loadingSeen[] = $loading;

                return $area === 'listing' ? '<span class="ol-stack">L</span>' : '';
            }
        );

        return new ProductImagePlugin(
            $labels,
            $renderer,
            $repository ?? $this->createStub(ProductRepositoryInterface::class),
            $this->createStub(LoggerInterface::class)
        );
    }
}
