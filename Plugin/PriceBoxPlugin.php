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
use Magento\Catalog\Pricing\Render\FinalPriceBox;
use Magento\Framework\Pricing\Render;

/**
 * Listing slot "next to the price" (1.1): after the price box of product cards (category, search, widgets,
 * sliders: the `item_list` price zone). The product page uses InlineSlotPlugin.
 */
class PriceBoxPlugin
{
    private const MARKER = 'ol-inline--near-price';

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
     * @param FinalPriceBox $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(FinalPriceBox $subject, mixed $result): mixed
    {
        if (!is_string($result)
            || trim($result) === ''
            || $subject->getData('zone') !== Render::ZONE_ITEM_LIST
            || str_contains($result, self::MARKER)
            || !$this->labels->isEnabled()
        ) {
            return $result;
        }
        $product = $subject->getSaleableItem();
        if (!$product instanceof ProductInterface) {
            return $result;
        }

        return $result . $this->labelRenderer->getInlineHtml($product, 'listing', 'near_price');
    }
}
