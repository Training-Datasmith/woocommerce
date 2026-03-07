<?php

declare(strict_types=1);
/**
 * Unit tests for validation.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Class WC_Tests_Validation.
 * @since 2.3
 */
class WC_Tests_Validation extends WC_Unit_Test_Case
{
    /**
     * Test is_email().
     *
     * @since 2.3
     */
    public function test_is_email()
    {
        $this->assertEquals('email@domain.com', WC_Validation::is_email('email@domain.com'));
        $this->assertFalse(WC_Validation::is_email('not a mail'));
        $this->assertFalse(WC_Validation::is_email('http://test.com'));
    }

    /**
     * Data provider for test_is_phone.
     *
     * @since 2.4
     */
    public function data_provider_test_is_phone()
    {
        return [
            [ true, WC_Validation::is_phone('+00 000 00 00 000') ],
            [ true, WC_Validation::is_phone('+00-000-00-00-000') ],
            [ true, WC_Validation::is_phone('(000) 00 00 000') ],
            [ true, WC_Validation::is_phone('+00.000.00.00.000') ],
            [ false, WC_Validation::is_phone('+00 aaa dd ee fff') ],
        ];
    }

    /**
     * Test is_phone().
     *
     * @param mixed $assert Expected value.
     * @param mixed $values Actual value.
     *
     * @dataProvider data_provider_test_is_phone
     * @since 2.3
     */
    public function test_is_phone($assert, $values)
    {
        $this->assertEquals($assert, $values);
    }

    /**
     * Data provider for test_is_postcode().
     *
     * @since 2.4
     */
    public function data_provider_test_is_postcode()
    {
        $it = [
            [ true, WC_Validation::is_postcode('99999', 'IT') ],
            [ false, WC_Validation::is_postcode('9999', 'IT') ],
            [ false, WC_Validation::is_postcode('ABC 999', 'IT') ],
            [ false, WC_Validation::is_postcode('ABC-999', 'IT') ],
            [ false, WC_Validation::is_postcode('ABC_123', 'IT') ],
        ];

        $gb = [
            [ true, WC_Validation::is_postcode('A9 9AA', 'GB') ],
            [ false, WC_Validation::is_postcode('99999', 'GB') ],
        ];

        $us = [
            [ true, WC_Validation::is_postcode('99999', 'US') ],
            [ true, WC_Validation::is_postcode('99999-9999', 'US') ],
            [ false, WC_Validation::is_postcode('ABCDE', 'US') ],
            [ false, WC_Validation::is_postcode('ABCDE-9999', 'US') ],
        ];

        $ch = [
            [ true, WC_Validation::is_postcode('9999', 'CH') ],
            [ false, WC_Validation::is_postcode('99999', 'CH') ],
            [ false, WC_Validation::is_postcode('ABCDE', 'CH') ],
        ];

        $br = [
            [ true, WC_Validation::is_postcode('99999-999', 'BR') ],
            [ true, WC_Validation::is_postcode('99999999', 'BR') ],
            [ false, WC_Validation::is_postcode('99999 999', 'BR') ],
            [ false, WC_Validation::is_postcode('99999-ABC', 'BR') ],
        ];

        $ca = [
            [ true, WC_Validation::is_postcode('A9A 9A9', 'CA') ],
            [ true, WC_Validation::is_postcode('A9A9A9', 'CA') ],
            [ true, WC_Validation::is_postcode('a9a9a9', 'CA') ],
            [ false, WC_Validation::is_postcode('D0A 9A9', 'CA') ],
            [ false, WC_Validation::is_postcode('99999', 'CA') ],
            [ false, WC_Validation::is_postcode('ABC999', 'CA') ],
            [ false, WC_Validation::is_postcode('0A0A0A', 'CA') ],
        ];

        $nl = [
            [ true, WC_Validation::is_postcode('3852GC', 'NL') ],
            [ true, WC_Validation::is_postcode('3852 GC', 'NL') ],
            [ true, WC_Validation::is_postcode('3852 gc', 'NL') ],
            [ false, WC_Validation::is_postcode('3852SA', 'NL') ],
            [ false, WC_Validation::is_postcode('3852 SA', 'NL') ],
            [ false, WC_Validation::is_postcode('3852 sa', 'NL') ],
        ];

        $si = [
            [ true, WC_Validation::is_postcode('1234', 'SI') ],
            [ true, WC_Validation::is_postcode('1000', 'SI') ],
            [ true, WC_Validation::is_postcode('9876', 'SI') ],
            [ false, WC_Validation::is_postcode('12345', 'SI') ],
            [ false, WC_Validation::is_postcode('0123', 'SI') ],
        ];

        $ba = [
            [ true, WC_Validation::is_postcode('71000', 'BA') ],
            [ true, WC_Validation::is_postcode('78256', 'BA') ],
            [ true, WC_Validation::is_postcode('89240', 'BA') ],
            [ false, WC_Validation::is_postcode('61000', 'BA') ],
            [ false, WC_Validation::is_postcode('7850', 'BA') ],
        ];

        $jp = [
            [ true, WC_Validation::is_postcode('1340088', 'JP') ],
            [ true, WC_Validation::is_postcode('134-0088', 'JP') ],
            [ false, WC_Validation::is_postcode('1340-088', 'JP') ],
            [ false, WC_Validation::is_postcode('12345', 'JP') ],
            [ false, WC_Validation::is_postcode('0123', 'JP') ],
        ];

        $dk = [
            [ true, WC_Validation::is_postcode('DK-9000', 'DK') ],
            [ true, WC_Validation::is_postcode('DK-1448', 'DK') ],
            [ true, WC_Validation::is_postcode('1448', 'DK') ],
            [ true, WC_Validation::is_postcode('1050', 'DK') ],
            [ false, WC_Validation::is_postcode('FO-110', 'DK') ],
            [ false, WC_Validation::is_postcode('DK-3900', 'DK') ],
            [ false, WC_Validation::is_postcode('3900', 'DK') ],
            [ false, WC_Validation::is_postcode('51467', 'DK') ],
        ];

        return array_merge($it, $gb, $us, $ch, $br, $ca, $nl, $si, $ba, $jp, $dk);
    }

    /**
     * Test is_postcode().
     *
     * @param mixed $assert Expected value.
     * @param mixed $values Actual value.
     *
     * @dataProvider data_provider_test_is_postcode
     * @since 2.4
     */
    public function test_is_postcode($assert, $values)
    {
        $this->assertEquals($assert, $values);
    }

    /**
     * Data provider for test_is_gb_postcode.
     *
     * @since 2.4
     */
    public function data_provider_test_is_gb_postcode()
    {
        return [
            [ true, WC_Validation::is_gb_postcode('AA9A 9AA') ],
            [ true, WC_Validation::is_gb_postcode('A9A 9AA') ],
            [ true, WC_Validation::is_gb_postcode('A9 9AA') ],
            [ true, WC_Validation::is_gb_postcode('A99 9AA') ],
            [ true, WC_Validation::is_gb_postcode('AA99 9AA') ],
            [ true, WC_Validation::is_gb_postcode('BFPO 801') ],
            [ false, WC_Validation::is_gb_postcode('99999') ],
            [ false, WC_Validation::is_gb_postcode('9999 999') ],
            [ false, WC_Validation::is_gb_postcode('999 999') ],
            [ false, WC_Validation::is_gb_postcode('99 999') ],
            [ false, WC_Validation::is_gb_postcode('9A A9A') ],
        ];
    }

    /**
     * Test is_gb_postcode().
     *
     * @param mixed $assert Expected value.
     * @param mixed $values Actual value.
     *
     * @dataProvider data_provider_test_is_gb_postcode
     * @since 2.4
     */
    public function test_is_gb_postcode($assert, $values)
    {
        $this->assertEquals($assert, $values);
    }

    /**
     * Data provider for test_format_postcode.
     *
     * @since 2.4
     */
    public function data_provider_test_format_postcode()
    {
        return [
            [ '99999', WC_Validation::format_postcode('99999', 'IT') ],
            [ '99999', WC_Validation::format_postcode(' 99999 ', 'IT') ],
            [ '99999', WC_Validation::format_postcode('999 99', 'IT') ],
            [ 'ABCDE', WC_Validation::format_postcode('abcde', 'IT') ],
            [ 'AB CDE', WC_Validation::format_postcode('abcde', 'GB') ],
            [ 'AB CDE', WC_Validation::format_postcode('abcde', 'CA') ],
        ];
    }

    /**
     * Test format_postcode().
     *
     * @param mixed $assert Expected value.
     * @param mixed $values Actual value.
     *
     * @dataProvider data_provider_test_format_postcode
     * @since 2.4
     */
    public function test_format_postcode($assert, $values)
    {
        $this->assertEquals($assert, $values);
    }

    /**
     * Test format_phone().
     *
     * @since 2.4
     */
    public function test_format_phone()
    {
        $this->assertEquals('+00-000-00-00-000', WC_Validation::format_phone('+00.000.00.00.000'));
        $this->assertEquals('+00 000 00 00 000', WC_Validation::format_phone('+00 000 00 00 000'));
    }
}
