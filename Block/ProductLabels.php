<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Block;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

/**
 * `openlabel.product.labels`, child of `product.media`: the product page's label stacks. Custom themes render it
 * with `$block->getChildHtml('openlabel.product.labels')` inside their positioned gallery container.
 */
class ProductLabels extends AbstractBlock
{
    /**
     * @param Context $context
     * @param Labels $labels
     * @param LabelRenderer $labelRenderer
     * @param Registry $registry
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Labels $labels,
        private readonly LabelRenderer $labelRenderer,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $product = $this->product();
        if ($product === null || !$this->labels->isEnabled()) {
            return '';
        }
        $this->labels->remember([$product]);

        // The main product image is above the fold: image labels load eagerly like it.
        // Configurable products also carry the labels of each variant, shown by the script when one is chosen.
        return $this->labelRenderer->getProductPageStacksHtml($product, 'eager');
    }

    /**
     * @return ProductInterface|null
     */
    private function product(): ?ProductInterface
    {
        $parent = $this->getParentBlock();
        $product = $parent instanceof AbstractBlock ? $parent->getData('product') : null;
        if (!$product instanceof ProductInterface && $parent !== false && method_exists($parent, 'getProduct')) {
            $product = $parent->getProduct();
        }
        if (!$product instanceof ProductInterface) {
            $product = $this->registry->registry('current_product');
        }

        return $product instanceof ProductInterface && $product->getId() ? $product : null;
    }
}
