<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Unit\Observer;

use Iranimij\OpenLabelHyva\Observer\RegisterTailwindSource;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class RegisterTailwindSourceTest extends TestCase
{
    public function testModuleIsAddedToTheHyvaExtensionsList(): void
    {
        $config = new DataObject(['extensions' => [['src' => 'vendor/other/module']]]);

        $this->observer('/var/www/html/vendor/iranimij/openlabel-hyva')->execute($this->event($config));

        self::assertSame(
            [['src' => 'vendor/other/module'], ['src' => 'vendor/iranimij/openlabel-hyva']],
            $config->getData('extensions')
        );
    }

    public function testFirstExtensionCreatesTheList(): void
    {
        $config = new DataObject();

        $this->observer('/var/www/html/app/code/Iranimij/OpenLabelHyva')->execute($this->event($config));

        self::assertSame([['src' => 'app/code/Iranimij/OpenLabelHyva']], $config->getData('extensions'));
    }

    private function observer(string $path): RegisterTailwindSource
    {
        $registrar = $this->createStub(ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($path);

        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/var/www/html');

        return new RegisterTailwindSource($registrar, $directoryList);
    }

    private function event(DataObject $config): Observer
    {
        return new Observer(['event' => new Event(['config' => $config])]);
    }
}
