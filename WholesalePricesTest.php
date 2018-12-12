<?php

namespace XLiteWeb\tests;

use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * @author cerber
 */
class testProductWholesalePrices extends \XLiteWeb\AXLiteWeb
{
    /**
     * @dataProvider provider
     */
    public function testWholesalePrices($data)
    {
        $this->clearSession($this->getStorefrontDriver());

        // Create wholesale prices
        $adminProductWholesalePrices = $this->AdminProductWholesalePrices;

        $adminProductWholesalePrices->loadProductId(true,$data['productId']);

        foreach ($data['prices'] as $membershipPrice) {
            foreach ($membershipPrice['prices'] as $price) {
                if ($price['qtyStart'] == 1 && $price['price'] == $data['basePrice']) {
                    continue;
                }
                $adminProductWholesalePrices->newWholesalePrice($price['qtyStart'], $price['price'], $price['type'], $membershipPrice['membership']);
            }
        }
        $adminProductWholesalePrices->saveChanges();

        // Check prices
        $productPage = $this->CustomerProduct;

        foreach ($data['prices'] as $membershipPrices) {
            if ($membershipPrices['customer']) {
                $productPage->login($membershipPrices['customer']['email'], $membershipPrices['customer']['password']);

            }
            $productPage->load(false,$data['productId']);
            $this->checkWholesalePrices($membershipPrices['prices'], $productPage);

            // Check prices on the cart page
            $productPage->setQTY(1);
            $productPage->addToCart();
            $cartPage = $this->CustomerCart;
            $cartPage->load();
            $this->checkWholesalePricesOnCart($membershipPrices['prices'], $cartPage);
            $cartPage->clearCart();
        }

        // Remove wholesale prices
        $adminProductWholesalePrices->deleteAllWholesalePrices();
        $adminProductWholesalePrices->saveChanges();

        // Check prices
        $this->clearSession($this->getStorefrontDriver());
        foreach ($data['prices'] as $membershipPrices) {
            if ($membershipPrices['customer']) {
                $productPage->login($membershipPrices['customer']['email'], $membershipPrices['customer']['password']);

            }
            $productPage->load(false,$data['productId']);
            $this->checkWholesalePrices($membershipPrices['prices'], $productPage, $data['basePrice']);
        }
    }

    public function checkWholesalePrices($originalPrices, $page, $basePrice = null)
    {
        $qtyTypes = ['qtyStart', 'qtyEnd'];
        foreach ($originalPrices as $price) {
            foreach ($qtyTypes as $qtyType) {
                if ($price[$qtyType] != 1) {
                    $page->setQTY($price[$qtyType]);
                }
                $this->assertEquals(
                    $basePrice ? $basePrice : (isset($price['absolutePrice']) ? $price['absolutePrice'] : $price['price']),
                    $page->getPriceOnProductPage(),
                    'Incorrect wholesale price on the product page for qty = ' . $price[$qtyType]);
            }
        }
    }

    public function checkWholesalePricesOnCart($originalPrices, $page, $basePrice = null)
    {
        $qtyTypes = ['qtyStart', 'qtyEnd'];
        foreach ($originalPrices as $price) {
            foreach ($qtyTypes as $qtyType) {
                if ($price[$qtyType] != 1) {
                    $page->setFirstItemQTY($price[$qtyType]);
                }
                $this->assertEquals(
                    $basePrice ? $basePrice : (isset($price['absolutePrice']) ? $price['absolutePrice'] : $price['price']),
                    $page->getFirstItemPrice(),
                    'Incorrect wholesale price on the cart page for qty = ' . $price[$qtyType]);
            }
        }
    }

    public function provider()
    {
        $dataset = [];

        $customerWithMembership = [
            'email'     => 'customer+membership@example.com',
            'password'  => 'guest'
        ];

        $dataset["First"] = [
            [
                'productId' => '37',
                'basePrice' => 650,
                'prices' => [
                    [
                        'membership' => 0,
                        'customer' => null,
                        'prices' => [
                            ['price' => 650, 'type' => '$', 'qtyStart' => 1, 'qtyEnd' => 4],
                            ['price' => 550, 'type' => '$', 'qtyStart' => 5, 'qtyEnd' => 6],
                        ]
                    ],
                    [
                        'membership' => 1,
                        'customer' => $customerWithMembership,
                        'prices' => [
                            ['price' => 650, 'type' => '$', 'qtyStart' => 1, 'qtyEnd' => 4],
                            ['price' => 50, 'type' => '%', 'qtyStart' => 5, 'qtyEnd' => 6, 'absolutePrice' => 325],
                        ]
                    ]
                ]
            ]
        ];

        return $dataset;
    }
}
