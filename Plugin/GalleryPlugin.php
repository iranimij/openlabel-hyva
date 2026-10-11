<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Plugin;

use Magento\Catalog\Block\Product\View\Gallery;

/**
 * Product page slot. Hyvä's gallery template has no child-block call (docs/hyva-integration.md §2), so the
 * `openlabel.product.labels` child is inserted right after the `#gallery-main` opening tag, which also gets the
 * `ol-anchor` class. A custom gallery that calls the child block itself is left alone.
 */
class GalleryPlugin
{
    public const CHILD = 'openlabel.product.labels';

    private const OPENING_TAG = '/<([a-z][a-z0-9]*)(\s[^>]*?\bid="gallery-main"[^>]*)>/is';

    /**
     * @param Gallery $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterToHtml(Gallery $subject, mixed $result): mixed
    {
        if (!is_string($result) || $result === '' || str_contains($result, ProductImagePlugin::MARKER)) {
            return $result;
        }
        if (preg_match(self::OPENING_TAG, $result, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return $result;
        }
        $stacks = (string) $subject->getChildHtml(self::CHILD);
        if (trim($stacks) === '') {
            return $result;
        }
        $attributes = $match[2][0];
        if (preg_match('/\bclass="/i', $attributes) === 1) {
            $attributes = (string) preg_replace('/\bclass="/i', 'class="ol-anchor ', $attributes, 1);
        } else {
            $attributes .= ' class="ol-anchor"';
        }
        $tag = '<' . $match[1][0] . $attributes . '>';
        $offset = $match[0][1];

        return substr($result, 0, $offset) . $tag . trim($stacks) . substr($result, $offset + strlen($match[0][0]));
    }
}
