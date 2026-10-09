<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private const MODULE_NAME = 'Iranimij_OpenLabelHyva';

    public function testModuleIsRegisteredWithComponentRegistrar(): void
    {
        $path = (new ComponentRegistrar())->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);

        self::assertNotNull($path, 'registration.php must register ' . self::MODULE_NAME);
        self::assertSame(realpath(dirname(__DIR__, 2)), realpath((string) $path));
    }

    public function testModuleLoadsAfterOpenLabelAndHyvaTheme(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 2) . '/etc/module.xml');

        self::assertNotFalse($xml);
        self::assertSame(self::MODULE_NAME, (string) $xml->module['name']);
        $sequence = array_map(static fn ($m) => (string) $m['name'], iterator_to_array($xml->module->sequence->module, false));
        self::assertContains('Iranimij_OpenLabel', $sequence);
        self::assertContains('Hyva_Theme', $sequence);
    }

    public function testHyvaThemeIsSuggestedNotRequired(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);

        self::assertArrayHasKey('hyva-themes/magento2-default-theme', $composer['suggest']);
        self::assertArrayNotHasKey('hyva-themes/magento2-default-theme', $composer['require']);
        self::assertArrayHasKey('iranimij/openlabel', $composer['require']);
    }

    public function testEveryHyvaLayoutHandleFileExists(): void
    {
        foreach (['hyva_catalog_category_view', 'hyva_catalogsearch_result_index', 'hyva_catalog_product_view', 'hyva_default'] as $handle) {
            self::assertFileExists(dirname(__DIR__, 2) . '/view/frontend/layout/' . $handle . '.xml');
        }
    }
}
