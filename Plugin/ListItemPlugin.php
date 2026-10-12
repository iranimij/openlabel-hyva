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
use Magento\Catalog\Block\Product\View;

/**
 * Listing slots "under the name" and "next to Add to Cart" (1.1). Hyvä's product card has no child block there,
 * so the labels go right after the name link and the cart button of the rendered card. A card without those
 * elements (a custom theme) keeps its markup.
 */
class ListItemPlugin
{
    public const TEMPLATE = 'Magento_Catalog::product/list/item.phtml';

    /** position => [marker inside the opening tag, closing tag to insert after] */
    private const SLOTS = [
        'under_name' => ['class="product-item-link"', '</a>'],
        'near_cart' => ['data-addto="cart"', '</button>'],
    ];

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
     * @param View $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(View $subject, mixed $result): mixed
    {
        if (!is_string($result) || $result === '' || $subject->getTemplate() !== self::TEMPLATE
            || !$this->labels->isEnabled()
        ) {
            return $result;
        }
        $product = $subject->getData('product');
        if (!$product instanceof ProductInterface) {
            return $result;
        }
        foreach (self::SLOTS as $position => [$marker, $closing]) {
            $html = $this->labelRenderer->getInlineHtml($product, 'listing', $position);
            if ($html !== '') {
                $result = $this->insertAfter($result, $marker, $closing, $html);
            }
        }

        return $result;
    }

    /**
     * @param string $html
     * @param string $marker
     * @param string $closing
     * @param string $insert
     * @return string
     */
    private function insertAfter(string $html, string $marker, string $closing, string $insert): string
    {
        $at = strpos($html, $marker);
        $end = $at === false ? false : strpos($html, $closing, $at);
        if ($end === false) {
            return $html;
        }
        $end += strlen($closing);

        return substr($html, 0, $end) . $insert . substr($html, $end);
    }
}
