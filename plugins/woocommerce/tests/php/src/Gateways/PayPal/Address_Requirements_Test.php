<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\AddressRequirements as PayPalAddressRequirements;

/**
 * Tests for the AddressRequirements helper class.
 */
class AddressRequirementsTest extends \WC_Unit_Test_Case
{
    /**
     * Tests for `country_requires_city`.
     *
     * @param string $country  ISO 3166-1 alpha-2 country code.
     * @param bool   $expected Expected result.
     * @return void
     *
     * @dataProvider provide_test_country_requires_city
     */
    public function test_country_requires_city($country, $expected)
    {
        $address_requirements = wc_get_container()->get(PayPalAddressRequirements::class)::instance();
        $this->assertSame($expected, $address_requirements->country_requires_city($country));
    }

    /**
     * Data provider for `test_country_requires_city`.
     *
     * @return array[]
     */
    public function provide_test_country_requires_city()
    {
        return [
            'empty'            => [
                'country'  => '',
                'expected' => false,
            ],
            'invalid'          => [
                'country'  => 'XX',
                'expected' => false,
            ],
            'does not require' => [
                'country'  => 'CW',
                'expected' => false,
            ],
            'requires'         => [
                'country'  => 'US',
                'expected' => true,
            ],
        ];
    }

    /**
     * Tests for `country_requires_postal_code`.
     *
     * @param string $country  ISO 3166-1 alpha-2 country code.
     * @param bool   $expected Expected result.
     * @return void
     *
     * @dataProvider provide_test_country_requires_postal_code
     */
    public function test_country_requires_postal_code($country, $expected)
    {
        $address_requirements = wc_get_container()->get(PayPalAddressRequirements::class)::instance();
        $this->assertSame($expected, $address_requirements->country_requires_postal_code($country));
    }

    /**
     * Data provider for `test_country_requires_postal_code`.
     *
     * @return array[]
     */
    public function provide_test_country_requires_postal_code()
    {
        return [
            'empty'            => [
                'country'  => '',
                'expected' => false,
            ],
            'invalid'          => [
                'country'  => 'XX',
                'expected' => false,
            ],
            'does not require' => [
                'country'  => 'IE',
                'expected' => false,
            ],
            'requires'         => [
                'country'  => 'US',
                'expected' => true,
            ],
        ];
    }
}
