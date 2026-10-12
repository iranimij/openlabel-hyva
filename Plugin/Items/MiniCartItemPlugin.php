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
use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Checkout\CustomerData\Cart;
use Magento\Quote\Model\Quote\Item;

/**
 * Mini cart (1.1): Hyvä renders the cart section's `product_price` of each item as HTML, so the item's
 * "Mini cart" labels show next to its price without any JavaScript of ours. The item data carries them under
 * `openlabel`; afterGetSectionData() appends them to the price once the Tax and Weee modules have rendered it
 * again (their plugins replace `product_price` of every item).
 */
class MiniCartItemPlugin
{
    public const KEY = 'openlabel';

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
     * @param AbstractItem $subject
     * @param array<string, mixed> $result
     * @param Item $item
     * @return array<string, mixed>
     */
    public function afterGetItemData(AbstractItem $subject, array $result, Item $item): array
    {
        $product = $item->getProduct();
        if (!$product instanceof ProductInterface || !$this->labels->isEnabled()) {
            return $result;
        }
        $html = $this->labelRenderer->getAreaHtml($product, 'minicart');
        if ($html !== '') {
            $result[self::KEY] = $html;
        }

        return $result;
    }

    /**
     * @param Cart $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetSectionData(Cart $subject, mixed $result): mixed
    {
        if (!is_array($result) || !is_array($result['items'] ?? null)) {
            return $result;
        }
        foreach ($result['items'] as $key => $item) {
            if (is_array($item) && isset($item[self::KEY])) {
                $result['items'][$key]['product_price'] = (string) ($item['product_price'] ?? '') . $item[self::KEY];
                unset($result['items'][$key][self::KEY]);
            }
        }

        return $result;
    }
}
