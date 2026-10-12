<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenLabelHyva\Test\Integration;

use Iranimij\OpenLabel\Api\LabelRepositoryInterface;
use Iranimij\OpenLabel\Model\Rule\Condition\Combine;
use Iranimij\OpenLabel\Model\Rule\Condition\Product as ProductCondition;
use Iranimij\OpenLabel\Test\Fixture\Design as DesignFixture;
use Iranimij\OpenLabel\Test\Fixture\Label as LabelFixture;
use Iranimij\OpenLabel\Test\Fixture\ScheduledSearchIndex;
use Iranimij\OpenLabel\ViewModel\LabelRenderer;
use Iranimij\OpenLabel\ViewModel\Labels;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Item lists (1.1): the mini cart gets the item's labels through the cart section data Hyvä renders as HTML.
 */
#[AppArea('frontend')]
#[DbIsolation(false)]
#[DataFixture(ScheduledSearchIndex::class)]
#[DataFixture(ProductFixture::class, ['sku' => 'ol-hyva-minicart', 'price' => 20], 'product')]
#[DataFixture(GuestCartFixture::class, as: 'cart')]
#[DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1])]
#[DataFixture(DesignFixture::class, ['store_texts' => [0 => ['text' => 'In your cart']]], 'design')]
class ItemAreasTest extends TestCase
{
    private ?int $labelId = null;

    protected function setUp(): void
    {
        $om = Bootstrap::getObjectManager();
        $om->get(Labels::class)->_resetState();
        $om->get(LabelRenderer::class)->_resetState();
        $label = $om->get(LabelFixture::class)->apply([
            'name' => 'Hyva minicart',
            'design_id' => (int) DataFixtureStorageManager::getStorage()->get('design')->getDesignId(),
            'conditions_serialized' => json_encode([
                'type' => Combine::class, 'aggregator' => 'all', 'value' => '1',
                'conditions' => [[
                    'type' => ProductCondition::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'ol-hyva-minicart',
                ]],
            ]),
            'placements' => [['area' => 'minicart', 'position' => 'under_name']],
        ]);
        $this->labelId = (int) $label->getLabelId();
    }

    protected function tearDown(): void
    {
        if ($this->labelId !== null) {
            Bootstrap::getObjectManager()->get(LabelRepositoryInterface::class)->deleteById($this->labelId);
        }
    }

    public function testTheCartSectionDataCarriesTheLabelNextToThePrice(): void
    {
        $om = Bootstrap::getObjectManager();
        $quote = $om->get(CartRepositoryInterface::class)->get((int) DataFixtureStorageManager::getStorage()->get('cart')->getId());
        $om->get(CheckoutSession::class)->setQuoteId((int) $quote->getId());

        // The whole section, as the browser loads it: the Tax module renders each item price again first.
        $data = $om->create(Cart::class)->getSectionData()['items'][0];

        self::assertMatchesRegularExpression(
            '#<span class="ol-stack ol-stack--h ol-inline ol-inline--minicart"><span class="ol-label [^"]+">In your cart</span></span>$#',
            (string) $data['product_price']
        );
        self::assertStringNotContainsString('In your cart', (string) $data['product_name'], 'names stay plain text (titles, alt texts)');
        self::assertArrayNotHasKey('openlabel', $data);
    }
}
