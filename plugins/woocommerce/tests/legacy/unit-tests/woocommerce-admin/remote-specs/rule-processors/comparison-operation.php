<?php

/**
 * ComparisonOperation Tests
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotification
 */

declare(strict_types=1);

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\ComparisonOperation;

/**
 * class WC_Admin_Tests_RemoteSpecs_RuleProcessors_Comparison_Operation
 */
class WC_Admin_Tests_RemoteSpecs_RuleProcessors_Comparison_Operation extends WC_Unit_Test_Case
{
    /**
     * @var ComparisonOperation $operation
     */
    private $operation;

    /**
     * setUp
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->operation = new ComparisonOperation();
    }

    /**
     * Test range
     *
     * @group fast
     * @return void
     */
    public function test_range()
    {
        $this->assertTrue($this->operation->compare(1, [ 1, 10 ], 'range'));
        $this->assertFalse($this->operation->compare(11, [ 1, 10 ], 'range'));
        $this->assertFalse($this->operation->compare(11, [ 1, 10, 2 ], 'range'));
        $this->assertFalse($this->operation->compare(11, 'string', 'range'));
    }

    /**
     * Test contains
     *
     * @group fast
     * @return void
     */
    public function test_contains()
    {
        $this->assertTrue($this->operation->compare([ 'test', 'test1' ], 'test', 'contains'));
        $this->assertFalse($this->operation->compare([ 'a', 'b' ], 'test', 'contains'));
    }

    /**
     * Test !contains
     *
     * @group fast
     * @return void
     */
    public function test_not_contains()
    {
        $this->assertTrue($this->operation->compare([ 'test1', 'test2' ], 'test', '!contains'));
        $this->assertFalse($this->operation->compare([ 'test' ], 'test', '!contains'));
    }

    /**
     * Test in
     *
     * @group fast
     * @return void
     */
    public function test_in()
    {
        $this->assertTrue($this->operation->compare('test', [ 'test', 'test2' ], 'in'));
        $this->assertFalse($this->operation->compare('test', [ 'test1', 'test2' ], 'in'));
    }

    /**
     * Test !in
     *
     * @group fast
     * @return void
     */
    public function test_not_in()
    {
        $this->assertTrue($this->operation->compare('test', [ 'test1', 'test2' ], '!in'));
        $this->assertFalse($this->operation->compare('test', [ 'test', 'test2' ], '!in'));
    }
}
