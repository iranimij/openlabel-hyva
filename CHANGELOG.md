# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Labels in the product text (OpenLabel 1.1): next to the price (price box of every product card), under the name and next to Add to Cart (product cards), and the same three on the product page after `product.info.title`, `product.info.price` and `product.info.addtocart` (configurable in `etc/frontend/di.xml`).

### Changed
- Requires OpenLabel 1.1.

## [1.0.0] - 2026-10-11

OpenLabel 1.0 for Hyvä: labels on listings (category, search, widgets, sliders) and in the product gallery, from cached pages, without JavaScript or a Tailwind build. Tested on Hyvä 1.4.2 and 1.5.2 (1.3 best effort).

### Added
- Hyvä 1.5 product page: `#gallery-main` is the scrolling image track there (`data-track`), so the label stacks go right before it, into the gallery's positioned wrapper, and overlay the visible slide; the gallery section is the container-query root.

### Fixed
- Hyvä 1.5: `container-type: inline-size` on the new `#gallery-main` track collapsed the whole product gallery to zero width (the track sits in a shrink-to-fit flex item). Containment now applies to the Hyvä 1.3/1.4 container only.

## [1.0.0-rc1] - 2026-10-11

First release candidate of the Hyvä render package for OpenLabel 1.0 (requires `iranimij/openlabel` ^1.0). Tested on Hyvä 1.4.2; 1.3 best effort; on Hyvä 1.5 listings work, product-page labels not yet.

### Added
- M3 S6 · `bin/magento hyva:config:generate` lists the module (observer on `hyva_config_generate_before`) so theme builds can scan `view/frontend/tailwind/tailwind.config.js`; nothing depends on it. The listing plugin tags the page with every product card it sees (`cat_p_<id>`), so CMS pages with product widgets refresh when a label starts matching. Storefront acceptance specs (CSP, no inline styles, no rebuild, click-through, legibility, RTL) live in `openlabel-dev-env`.
- M3 S4 · Product page slot: child block `openlabel.product.labels` under `product.media` and an `after` plugin on `Magento\Catalog\Block\Product\View\Gallery::toHtml()` that inserts it right after the `#gallery-main` opening tag and adds `ol-anchor` to that element; galleries that render the child themselves are left alone. Configurable, grouped and bundle pages show the parent's labels (parent index rows). Image labels load eagerly like the main image. One label query per product page.
- M3 S3 · Listing slot: an `after` plugin on `Magento\Catalog\Block\Product\Image::toHtml()` (template `Magento_Catalog::product/list/image.phtml` only) wraps the product image in `<span class="ol-anchor">` with the label stacks after it, reusing the image's `loading`; covers category, search, widgets, Page Builder carousels and product sliders. Storefront-only plugin on product collections remembers loaded products (≤ 200) so the page resolves its labels with one query. `ListItemProcessor` adds `openlabel_<id>` / `openlabel_design_<id>` to Hyvä's list-item block cache tags and to the page. The generated stylesheet gets the Hyvä container roots (`.product-item`, `#gallery-main`) and hides labels in the fullscreen gallery. `hyva_default` adds the stylesheet `<link>` and the page cache identities block.
- Module skeleton `Iranimij_OpenLabelHyva` (M0): `hyva_*` layout handle files, Tailwind source stub, CI template.

[Unreleased]: https://github.com/iranimij/openlabel-hyva/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/iranimij/openlabel-hyva/compare/v1.0.0-rc1...v1.0.0
[1.0.0-rc1]: https://github.com/iranimij/openlabel-hyva/releases/tag/v1.0.0-rc1
