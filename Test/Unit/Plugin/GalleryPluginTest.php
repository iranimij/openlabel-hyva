<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Plugin;

use Iranimij\OpenLabelHyva\Plugin\GalleryPlugin;
use Magento\Catalog\Block\Product\View\Gallery;
use PHPUnit\Framework\TestCase;

class GalleryPluginTest extends TestCase
{
    private const STACKS = '<span class="ol-stack ol-pos-ts">L</span>';

    public function testStacksGoRightAfterTheGalleryOpeningTagWhichBecomesTheAnchor(): void
    {
        $html = '<div class="w-full"><div id="gallery-main" class="relative mb-6" aria-live="polite"><img></div></div>';

        self::assertSame(
            '<div class="w-full"><div id="gallery-main" class="ol-anchor relative mb-6" aria-live="polite">'
            . self::STACKS . '<img></div></div>',
            (new GalleryPlugin())->afterToHtml($this->gallery(self::STACKS), $html)
        );
    }

    public function testClassIsAddedWhenTheContainerHasNone(): void
    {
        $html = "<div\n    id=\"gallery-main\"\n    x-data=\"gallery\"><img></div>";

        self::assertSame(
            "<div\n    id=\"gallery-main\"\n    x-data=\"gallery\" class=\"ol-anchor\">" . self::STACKS . '<img></div>',
            (new GalleryPlugin())->afterToHtml($this->gallery(self::STACKS), $html)
        );
    }

    public function testHyva15TrackKeepsItsMarkupAndTheStacksGoInTheWrapperBeforeIt(): void
    {
        // Hyvä 1.5: #gallery-main is the scrolling image track (data-track) inside a positioned wrapper; labels
        // inside the track would scroll away with the first slide.
        $html = '<section id="gallery"><div class="relative"><div class="nav"></div>'
            . "<div\n    id=\"gallery-main\"\n    data-track\n    class=\"flex overflow-x-auto\"\n><div>slide</div></div></div></section>";

        self::assertSame(
            '<section id="gallery"><div class="relative"><div class="nav"></div>' . self::STACKS
            . "<div\n    id=\"gallery-main\"\n    data-track\n    class=\"flex overflow-x-auto\"\n><div>slide</div></div></div></section>",
            (new GalleryPlugin())->afterToHtml($this->gallery(self::STACKS), $html)
        );
    }

    public function testNothingChangesWithoutLabels(): void
    {
        $html = '<div id="gallery-main" class="relative"><img></div>';

        self::assertSame($html, (new GalleryPlugin())->afterToHtml($this->gallery(''), $html));
    }

    public function testCustomGalleryThatRendersTheChildItselfIsLeftAlone(): void
    {
        $html = '<div id="gallery-main" class="relative">' . self::STACKS . '<img></div>';

        self::assertSame($html, (new GalleryPlugin())->afterToHtml($this->gallery(self::STACKS), $html));
    }

    public function testGalleryWithoutTheHyvaContainerIsLeftAlone(): void
    {
        $html = '<div class="gallery-placeholder"><img></div>';

        self::assertSame($html, (new GalleryPlugin())->afterToHtml($this->gallery(self::STACKS), $html));
    }

    private function gallery(string $childHtml): Gallery
    {
        $gallery = $this->createStub(Gallery::class);
        $gallery->method('getChildHtml')->willReturn($childHtml);

        return $gallery;
    }
}
