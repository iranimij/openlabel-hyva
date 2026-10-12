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
use Magento\Checkout\Block\Cart\Item\Renderer;

/**
 * Shopping cart (1.1): the item's "Shopping cart" labels right after its name heading
 * (`h3.product-item-name` of Hyvä's `php-cart/item/default.phtml`).
 */
class CartItemPlugin
{
    private const NAME = 'class="product-item-name';

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
     * @param Renderer $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(Renderer $subject, mixed $result): mixed
    {
        $at = is_string($result) ? strpos($result, self::NAME) : false;
        if ($at === false || !$this->labels->isEnabled()) {
            return $result;
        }
        $product = $subject->getProduct();
        $end = strpos($result, '</h3>', $at);
        if (!$product instanceof ProductInterface || $end === false) {
            return $result;
        }
        $html = $this->labelRenderer->getAreaHtml($product, 'cart');

        return $html === '' ? $result : substr($result, 0, $end + 5) . $html . substr($result, $end + 5);
    }
}
