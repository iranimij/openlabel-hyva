<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Observer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * `bin/magento hyva:config:generate`: lists this module so a theme's Tailwind build scans
 * `view/frontend/tailwind/tailwind.config.js`. Optional: labels never depend on the theme build (FE1).
 */
class RegisterTailwindSource implements ObserverInterface
{
    private const MODULE = 'Iranimij_OpenLabelHyva';

    /**
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param DirectoryList $directoryList
     */
    public function __construct(
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly DirectoryList $directoryList
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $config = $observer->getEvent()->getData('config');
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE);
        if (!$config instanceof DataObject || $path === null) {
            return;
        }
        $extensions = $config->getData('extensions');
        $extensions = is_array($extensions) ? $extensions : [];
        $root = rtrim($this->directoryList->getRoot(), '/') . '/';
        $relative = str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
        $extensions[] = ['src' => $relative];
        $config->setData('extensions', $extensions);
    }
}
