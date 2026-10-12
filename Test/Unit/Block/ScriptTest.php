<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Block;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Iranimij\OpenLabelHyva\Block\Script;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ScriptTest extends TestCase
{
    private bool $enabled = true;

    private bool $needed = true;

    public function testTheScriptIsOnlyOnPagesThatUseIt(): void
    {
        self::assertTrue($this->block()->shouldRender());
        $this->needed = false;
        self::assertFalse($this->block()->shouldRender(), 'a page without countdowns or variant labels gets no JavaScript');
        $this->needed = true;
        $this->enabled = false;
        self::assertFalse($this->block()->shouldRender());
    }

    public function testTheScriptStaysWithinItsBudget(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/view/frontend/templates/js/labels.phtml');
        $script = preg_match('#<script>(.*?)</script>#s', $source, $match) === 1 ? $match[1] : '';

        self::assertNotSame('', $script);
        self::assertLessThanOrEqual(3 * 1024, strlen((string) gzencode($script, 9)), '10 · Front-end Review: ≤ 3 KB gzipped');
        self::assertStringContainsString('registerInlineScript', $source, 'CSP: the inline script is registered with Hyvä');
        self::assertStringContainsString('visibilitychange', $script, 'the countdown pauses in background tabs');
    }

    private function block(): Script
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('isEnabled')->willReturnCallback(fn (): bool => $this->enabled);
        $renderer = $this->createStub(LabelRenderer::class);
        $renderer->method('isScriptNeeded')->willReturnCallback(fn (): bool => $this->needed);

        /** @var Script $block */
        $block = (new ObjectManager($this))->getObject(Script::class, ['labels' => $labels, 'labelRenderer' => $renderer]);

        return $block;
    }
}
