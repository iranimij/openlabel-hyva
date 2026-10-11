<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Plugin;

use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Storefront only: every loaded product collection is remembered, so the first label lookup of the page resolves
 * all of them in one query (02 · Architecture §10) and the variables reuse the loaded prices.
 */
class CollectionPreloadPlugin
{
    /** Larger collections (exports, feeds) are not product lists; their products resolve on demand. */
    public const MAX_PRODUCTS = 200;

    private bool $running = false;

    /**
     * @param Labels $labels
     */
    public function __construct(private readonly Labels $labels)
    {
    }

    /**
     * @param Collection $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterLoad(Collection $subject, mixed $result): mixed
    {
        // getItems() loads an unloaded collection again (core plugins can nest loads): never re-enter.
        if ($this->running || !$subject->isLoaded()) {
            return $result;
        }
        $this->running = true;
        try {
            $items = $subject->getItems();
            if ($items !== [] && count($items) <= self::MAX_PRODUCTS && $this->labels->isEnabled()) {
                $this->labels->remember(array_values(array_filter(
                    $items,
                    static fn ($item): bool => $item instanceof ProductInterface
                )));
            }
        } finally {
            $this->running = false;
        }

        return $result;
    }
}
