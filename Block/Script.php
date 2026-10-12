<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Block;

use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * The one OpenLabel script (1.1): ticks {COUNTDOWN}s and shows the labels of the chosen product page variant.
 * Rendered at the end of the page, after every label, and only when one of them needs it.
 */
class Script extends Template
{
    /**
     * @param Context $context
     * @param Labels $labels
     * @param LabelRenderer $labelRenderer
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Labels $labels,
        private readonly LabelRenderer $labelRenderer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function shouldRender(): bool
    {
        return $this->labels->isEnabled() && $this->labelRenderer->isScriptNeeded();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return $this->shouldRender() ? parent::_toHtml() : '';
    }
}
