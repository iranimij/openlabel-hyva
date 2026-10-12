# OpenLabel Hyvä render package

Storefront rendering for [OpenLabel](https://github.com/iranimij/openlabel) on Hyvä 1.3, 1.4 and 1.5: label slots in product listings (category, search, widgets, sliders), the product page gallery and, since 1.1, the product text (next to the price, next to Add to Cart, under the name), the cart, mini cart, wish list and compare page. One generated stylesheet per store, no inline styles, and **no Tailwind rebuild needed** on a stock Hyvä theme. JavaScript only where a page needs it: one small CSP-registered script for a live countdown or labels that follow the chosen variant.

## How it hooks into Hyvä

Verified against Hyvä 1.4.2 and 1.5.2 and documented in [docs/hyva-integration.md](https://github.com/iranimij/openlabel/blob/main/docs/hyva-integration.md) in the core repository.

## Compatibility

| Magento Open Source / Mage-OS | PHP | Hyvä |
|---|---|---|
| 2.4.7, 2.4.8, 2.4.9 | 8.2, 8.3, 8.4 | 1.3.x, 1.4.x |

## Notice

OpenLabel is an independent open-source project, not affiliated with Hyvä Themes B.V. or Adobe Inc.

## License

MIT. See [LICENSE](LICENSE).
