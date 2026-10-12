<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Plugin;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\View\Layout;

/**
 * Product page slots for the labels in the product text (1.1): after the layout elements of the name, the price
 * and Add to Cart. The element names are a DI argument (`productPageSlots`: element name => position), so a theme
 * that renames them only needs a di.xml entry.
 */
class InlineSlotPlugin
{
    /**
     * @param Labels $labels
     * @param LabelRenderer $labelRenderer
     * @param CatalogHelper $catalogHelper
     * @param array<string, string> $productPageSlots layout element name => near_price|near_cart|under_name
     */
    public function __construct(
        private readonly Labels $labels,
        private readonly LabelRenderer $labelRenderer,
        private readonly CatalogHelper $catalogHelper,
        private readonly array $productPageSlots = []
    ) {
    }

    /**
     * @param Layout $subject
     * @param mixed $result
     * @param string $name
     * @return mixed
     */
    public function afterRenderElement(Layout $subject, mixed $result, string $name): mixed
    {
        $position = $this->productPageSlots[$name] ?? null;
        if ($position === null || !is_string($result) || trim($result) === '' || !$this->labels->isEnabled()) {
            return $result;
        }
        $product = $this->catalogHelper->getProduct();
        if (!$product instanceof ProductInterface || (int) $product->getId() === 0) {
            return $result;
        }

        return $result . $this->labelRenderer->getInlineHtml($product, 'product', $position);
    }
}
