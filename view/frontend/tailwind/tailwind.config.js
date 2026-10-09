/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 *
 * Tailwind source registered through `bin/magento hyva:config:generate` so a theme build can scan
 * OpenLabel templates. Nothing in OpenLabel depends on this file for correctness: all structural
 * CSS ships in the generated per-store stylesheet (see docs/hyva-integration.md).
 */
module.exports = {
    content: [
        '../templates/**/*.phtml'
    ]
};
