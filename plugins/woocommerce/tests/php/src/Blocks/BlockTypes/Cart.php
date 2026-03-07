<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\CartCheckoutUtilsMock;

/**
 * Tests for the Cart block type
 *
 * @since $VID:$
 */
class Cart extends \WP_UnitTestCase
{
    /**
     * We ensure deep sort works with all sort of arrays.
     */
    public function test_deep_sort_with_accents()
    {
        $test_array_1 = [
            '0',
            '1',
            [ '2', '3' ],
        ];
        $test_array_2 = [
            [ '0', '1' ],
            '2',
            '3',
        ];
        $this->assertEquals($test_array_1, CartCheckoutUtilsMock::deep_sort_test($test_array_1), '');
        $this->assertEquals($test_array_2, CartCheckoutUtilsMock::deep_sort_test($test_array_2), '');
    }
}
