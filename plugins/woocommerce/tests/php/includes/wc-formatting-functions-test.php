<?php

declare(strict_types=1);
/**
 * Formatting functions tests
 *
 * @package WooCommerce\Tests\Formatting.
 */

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Formatting_Functions_Test extends \WC_Unit_Test_Case
{
    /**
     * Data provider for test_wc_sanitize_coupon_code.
     *
     * @return array[]
     */
    public function data_provider_test_wc_sanitize_coupon_code(): array
    {
        return [
            [ 'DUMMYCOUPON', 'DUMMYCOUPON' ],
            [ 'a&amp;a', 'a&a' ],
            [ "test's", "test's" ],
        ];
    }

    /**
     * Test wc_sanitize_coupon_code() function.
     *
     * @dataProvider data_provider_test_wc_sanitize_coupon_code
     *
     * @param string $assert Expected result.
     * @param string $input Input for wc_sanitize_coupon_code().
     */
    public function test_wc_sanitize_coupon_code(string $assert, string $input)
    {
        $this->assertSame($assert, wc_sanitize_coupon_code($input));
    }

    /**
     * Data provider for test_wc_format_postcode.
     *
     * @return array[]
     * @see WC_Tests_Formatting_Functions::test_wc_format_postcode for US, GB, BR, JP, NL, LV
     */
    public function data_provider_test_wc_format_postcode(): array
    {
        $ie = [
            [ 'D02 AF30', 'D02AF30', 'IE' ],
        ];

        $pt = [
            [ '1000-205', '1000205', 'PT' ],
        ];

        $dk = [
            [ '1234', '1234', 'DK' ],
            [ 'DK-1234', 'DK-1234', 'DK' ],
            [ 'DK-1234', 'dk-1234', 'DK' ],
        ];

        $se = [
            [ '113 52', '11352', 'SE' ],
        ];

        $sk = [
            [ '811 02', '81102', 'SK' ],
            [ 'SK-811 02', 'SK-81102', 'SK' ],
            [ 'SK-811 02', 'sk-81102', 'SK' ],
        ];

        $cz = [
            [ '115 03', '11503', 'CZ' ],
            [ 'CZ-115 03', 'CZ-11503', 'CZ' ],
            [ 'CZ-115 03', 'cz-11503', 'CZ' ],
        ];

        return array_merge($ie, $pt, $dk, $se, $sk, $cz);
    }

    /**
     * Test wc_format_postcode() function.
     *
     * @dataProvider data_provider_test_wc_format_postcode
     *
     * @param string $assert Expected result.
     * @param string $postcode Postcode input for wc_format_postcode().
     * @param string $country Country input for wc_format_postcode().
     */
    public function test_wc_format_postcode(string $assert, string $postcode, string $country)
    {
        $this->assertSame($assert, wc_format_postcode($postcode, $country), "Test formatting of $postcode postcodes.");
    }

    /**
     * Test wc_is_stock_amount_integer function.
     *
     * @testdox wc_is_stock_amount_integer should return true when stock amounts are integers and false when they are floats.
     */
    public function test_wc_is_stock_amount_integer()
    {
        // Remove all filters from woocommerce_stock_amount.
        remove_all_filters('woocommerce_stock_amount');

        // Test with floatval applied to the filter.
        add_filter('woocommerce_stock_amount', 'floatval');
        $this->assertFalse(wc_is_stock_amount_integer(), 'Should return false when floatval is applied to stock amount filter.');

        // Remove floatval filter and add intval filter.
        remove_all_filters('woocommerce_stock_amount');
        add_filter('woocommerce_stock_amount', 'intval');
        $this->assertTrue(wc_is_stock_amount_integer(), 'Should return true when intval is applied to stock amount filter.');
    }
}
