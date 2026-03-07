<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\DiscountsUtil;

/**
 * Tests for the discounts utility class.
 */
class DiscountsUtilTest extends \WC_Unit_Test_Case
{
    /**
     * @testdox `is_coupon_emails_allowed` should return true/false based on direct match, wildcard match, case sensitivity, empty allowed list, and special characters.
     */
    public function test_is_coupon_emails_allowed()
    {
        // Direct match.
        $this->assertEquals(true, DiscountsUtil::is_coupon_emails_allowed([ 'customer@wc.local' ], [ 'customer@wc.local' ]));
        $this->assertEquals(false, DiscountsUtil::is_coupon_emails_allowed([ 'customer@wc.local' ], [ 'customer2@wc.local' ]));

        // Wildcard match.
        $this->assertEquals(true, DiscountsUtil::is_coupon_emails_allowed([ 'customer@wc.local' ], [ '*.local' ]));
        $this->assertEquals(false, DiscountsUtil::is_coupon_emails_allowed([ 'customer@wc.local' ], [ '*.test' ]));

        // Case sensitivity.
        $this->assertEquals(false, DiscountsUtil::is_coupon_emails_allowed([ 'Customer@WC.local' ], [ 'customer@wc.local' ]));

        // Empty allowed list.
        $this->assertEquals(false, DiscountsUtil::is_coupon_emails_allowed([ 'customer@wc.local' ], []));
        $this->assertEquals(false, DiscountsUtil::is_coupon_emails_allowed([], [ 'customer@wc.local' ]));

        // Special characters in email.
        $this->assertEquals(false, DiscountsUtil::is_coupon_emails_allowed([ 'special@example.com' ], [ 'special+char@example.com' ]));
    }
}
