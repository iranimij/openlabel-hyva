<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Plugin\Items;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\Compare\ListCompare;
use Magento\Framework\Escaper;

/**
 * Compare products (1.1): each product's "Compare products" labels after its name (`<strong><a href=...>` of
 * Hyvä's compare list). Products are recognised by their URL, as the template prints it.
 */
class CompareListPlugin
{
    /**
     * @param Labels $labels
     * @param LabelRenderer $labelRenderer
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly Labels $labels,
        private readonly LabelRenderer $labelRenderer,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @param ListCompare $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(ListCompare $subject, mixed $result): mixed
    {
        if (!is_string($result) || $result === '' || !$this->labels->isEnabled()) {
            return $result;
        }
        foreach ($subject->getItems() as $product) {
            if (!$product instanceof Product) {
                continue;
            }
            $html = $this->labelRenderer->getAreaHtml($product, 'compare');
            if ($html === '') {
                continue;
            }
            $url = preg_quote((string) $this->escaper->escapeUrl($subject->getProductUrl($product)), '#');
            $result = (string) preg_replace_callback(
                '#<strong>\s*<a href="' . $url . '"[^>]*>.*?</a>\s*</strong>#s',
                static fn (array $match): string => $match[0] . $html,
                $result,
                1
            );
        }

        return $result;
    }
}
