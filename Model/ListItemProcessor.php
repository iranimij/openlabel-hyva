<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Model;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Hyvä `additional_item_renderer_processors` entry: Hyvä caches each list item block for an hour, so the item's
 * cache tags get `openlabel_<label_id>` and `openlabel_design_<design_id>`, and the page is tagged too, even when
 * the item comes from the block cache and the image is not rendered again.
 */
class ListItemProcessor implements ArgumentInterface
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
     * @param AbstractBlock $itemRendererBlock
     * @param ProductInterface $product
     * @return void
     */
    public function beforeListItemToHtml(AbstractBlock $itemRendererBlock, ProductInterface $product): void
    {
        if (!$this->labels->isEnabled()) {
            return;
        }
        $this->labels->remember([$product]);
        $tags = $this->labelRenderer->collectIdentities((int) $product->getId());
        if ($tags === []) {
            return;
        }
        $existing = $itemRendererBlock->getData('cache_tags');
        $itemRendererBlock->setData(
            'cache_tags',
            array_values(array_unique(array_merge(is_array($existing) ? $existing : [], $tags)))
        );
    }
}
