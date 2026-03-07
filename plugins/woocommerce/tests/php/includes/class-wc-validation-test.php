<?php

declare(strict_types=1);
/**
 * Validation functions tests
 *
 * @package WooCommerce\Tests\Validation.
 */

/**
 * Class WC_Validation_Test.
 */
class WC_Validation_Test extends \WC_Unit_Test_Case
{
    /**
     * Data provider for test_is_postcode().
     */
    public function data_provider_test_is_postcode(): array
    {
        $cz = [
            [ true, '115 03', 'CZ' ],
            [ true, 'CZ-115 03', 'CZ' ],
        ];

        $se = [
            [ true, '123 45', 'SE' ],
            [ true, '12345', 'SE' ],
            [ false, '12 345', 'SE' ],
            [ false, 'ABC 45', 'SE' ],
        ];

        $li = [
            [ true, '9482', 'LI' ],
            [ true, '9495', 'LI' ],
            [ false, '8512', 'LI' ],
            [ false, '0123', 'LI' ],
            [ false, '948A', 'LI' ],
        ];

        return array_merge($cz, $se, $li);
    }

    /**
     * Test postcode validation.
     *
     * @dataProvider data_provider_test_is_postcode
     *
     * @param bool   $expected Expected result.
     * @param string $postcode Postcode param for is_postcode.
     * @param string $country Country param for is_postcode.
     */
    public function test_is_postcode(bool $expected, string $postcode, string $country): void
    {
        $this->assertSame($expected, WC_Validation::is_postcode($postcode, $country));
    }
}
