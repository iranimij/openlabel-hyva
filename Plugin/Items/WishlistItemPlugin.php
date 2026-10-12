<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Plugin\Items;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Wishlist\Block\Customer\Wishlist\Item\Column\Info;

/**
 * Wish list (1.1): the item's "Wish list" labels after its name column.
 */
class WishlistItemPlugin
{
    /**
     * @param Labels $labels
     * @param LabelRenderer $labelRenderer
     */
    public function __construct(
        private readonly Labels $labels,
        private readonly LabelRenderer $labelRenderer
    ) {
    }

    /**
     * @param Info $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(Info $subject, mixed $result): mixed
    {
        if (!is_string($result) || trim($result) === '' || !$this->labels->isEnabled()) {
            return $result;
        }
        $item = $subject->getData('item');
        $product = $item !== null && method_exists($item, 'getProduct') ? $item->getProduct() : null;
        if (!$product instanceof ProductInterface) {
            return $result;
        }

        return $result . $this->labelRenderer->getAreaHtml($product, 'wishlist');
    }
}
