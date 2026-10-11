# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- M3 S3 · Listing slot: an `after` plugin on `Magento\Catalog\Block\Product\Image::toHtml()` (template `Magento_Catalog::product/list/image.phtml` only) wraps the product image in `<span class="ol-anchor">` with the label stacks after it, reusing the image's `loading`; covers category, search, widgets, Page Builder carousels and product sliders. Storefront-only plugin on product collections remembers loaded products (≤ 200) so the page resolves its labels with one query. `ListItemProcessor` adds `openlabel_<id>` / `openlabel_design_<id>` to Hyvä's list-item block cache tags and to the page. The generated stylesheet gets the Hyvä container roots (`.product-item`, `#gallery-main`) and hides labels in the fullscreen gallery. `hyva_default` adds the stylesheet `<link>` and the page cache identities block.
- Module skeleton `Iranimij_OpenLabelHyva` (M0): `hyva_*` layout handle files, Tailwind source stub, CI template.
