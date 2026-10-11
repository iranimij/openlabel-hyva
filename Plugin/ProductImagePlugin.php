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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Listing slot. Hyvä's list item has no child block inside the image link (docs/hyva-integration.md §1), so the
 * product image of `Magento_Catalog::product/list/image.phtml` is wrapped in a positioned `ol-anchor` with the
 * label stacks after the image. Covers category, search, widgets, Page Builder carousels and product sliders,
 * which all render through Hyvä's list item.
 */
class ProductImagePlugin
{
    public const LIST_TEMPLATE = 'Magento_Catalog::product/list/image.phtml';
    public const MARKER = 'ol-stack';

    /**
     * @param Labels $labels
     * @param LabelRenderer $labelRenderer
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Labels $labels,
        private readonly LabelRenderer $labelRenderer,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Image $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(Image $subject, mixed $result): mixed
    {
        if (!is_string($result)
            || $result === ''
            || $subject->getTemplate() !== self::LIST_TEMPLATE
            || str_contains($result, self::MARKER)
            || !$this->labels->isEnabled()
        ) {
            return $result;
        }
        $productId = (int) $subject->getData('product_id');
        if ($productId === 0 || $this->labels->getForProduct($productId) === []) {
            return $result;
        }
        $product = $this->labels->getRememberedProduct($productId) ?? $this->load($productId);
        if ($product === null) {
            return $result;
        }
        $customAttributes = $subject->getData('custom_attributes');
        $loading = is_array($customAttributes) ? (string) ($customAttributes['loading'] ?? 'lazy') : 'lazy';
        $stacks = $this->labelRenderer->getStacksHtml($product, 'listing', $loading);

        return $stacks === '' ? $result : '<span class="ol-anchor">' . $result . $stacks . '</span>';
    }

    /**
     * Products rendered outside a loaded collection (rare: custom code calling the image block directly).
     *
     * @param int $productId
     * @return ProductInterface|null
     */
    private function load(int $productId): ?ProductInterface
    {
        try {
            $product = $this->productRepository->getById($productId);
            $this->labels->remember([$product]);

            return $product;
        } catch (NoSuchEntityException $e) {
            $this->logger->info('OpenLabel: product ' . $productId . ' not found for its labels.');

            return null;
        }
    }
}
