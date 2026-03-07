<?php

declare(strict_types=1);
/**
 * Test the class that evaluates payment gateway suggestion visibility.
 *
 * @package WooCommerce\Admin\Tests\PaymentGatewaySuggestions
 */

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;

/**
 * class WC_Admin_Tests_PaymentGatewaySuggestions_EvaluateSuggestion
 */
class WC_Admin_Tests_PaymentGatewaySuggestions_EvaluateSuggestion extends WC_Unit_Test_Case
{
    /**
     * Mock gateway option.
     */
    public const MOCK_OPTION = 'woocommerce_admin_mock_gateway_option';

    /**
     * The mock logger.
     *
     * @var WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_logger;

    /**
     * Run setup code for unit tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        // Have a mock logger used by the rule evaluator.
        $this->mock_logger = $this->getMockBuilder('WC_Logger_Interface')->getMock();
        add_filter('woocommerce_logging_class', [ $this, 'override_wc_logger' ]);
    }

    /**
     * Tear down.
     */
    public function tearDown(): void
    {
        delete_option(self::MOCK_OPTION);
        remove_filter('woocommerce_logging_class', [ $this, 'override_wc_logger' ]);

        parent::tearDown();
    }

    /**
     * Test that the gateway is returned as is when no rules are provided.
     */
    public function test_no_rules()
    {
        $suggestion = [
            'id' => 'mock-gateway',
        ];
        $evaluated  = EvaluateSuggestion::evaluate((object) $suggestion);
        $this->assertEquals((object) $suggestion, $evaluated);
    }

    /**
     * Test that the gateway is not visible when rules do not pass.
     */
    public function test_is_not_visible()
    {
        $suggestion = [
            'id'         => 'mock-gateway',
            'is_visible' => (object) [
                'type'        => 'option',
                'option_name' => self::MOCK_OPTION,
                'value'       => 'a',
                'default'     => null,
                'operation'   => '=',
            ],
        ];
        $evaluated  = EvaluateSuggestion::evaluate((object) $suggestion);
        $this->assertFalse($evaluated->is_visible);
    }

    /**
     * Test that the gateway is returned when visibility rules pass.
     */
    public function test_is_visible()
    {
        $suggestion = [
            'id'         => 'mock-gateway',
            'is_visible' => (object) [
                'type'        => 'option',
                'option_name' => self::MOCK_OPTION,
                'value'       => 'a',
                'default'     => null,
                'operation'   => '=',
            ],
        ];
        update_option(self::MOCK_OPTION, 'a');
        $evaluated = EvaluateSuggestion::evaluate((object) $suggestion);
        $this->assertTrue($evaluated->is_visible);
    }

    /**
     * Test that suggestion evaluation logs debug logs when logging is enabled.
     */
    public function test_evaluation_logs()
    {
        add_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');

        $suggestion = [
            'id'         => 'mock-gateway',
            'is_visible' => [
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
                // Only the top-level rules are logged, not the operands.
                (object) [
                    'type'     => 'or',
                    'operands' => [
                        (object) [
                            'type' => 'fail',
                        ],
                        (object) [
                            'type' => 'pass',
                        ],
                    ],
                ],
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'b', // This will fail the rule.
                    'default'     => null,
                    'operation'   => '=',
                ],
            ],
        ];
        update_option(self::MOCK_OPTION, 'a');

        $this->mock_logger_debug_calls(
            [
                [
                    '[mock-gateway] option: passed',
                    [ 'source' => 'unit-tests' ],
                ],
                [
                    '[mock-gateway] or: passed',
                    [ 'source' => 'unit-tests' ],
                ],
                [
                    '[mock-gateway] option: failed',
                    [ 'source' => 'unit-tests' ],
                ],
            ]
        );

        EvaluateSuggestion::evaluate((object) $suggestion, [ 'source' => 'unit-tests' ]);

        remove_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');
    }

    /**
     * Test that suggestion evaluation doesn't log debug logs when logging is disabled.
     */
    public function test_evaluation_doesnt_log()
    {
        add_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_false');

        $suggestion = [
            'id'         => 'mock-gateway',
            'is_visible' => [
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'b', // This will fail the rule.
                    'default'     => null,
                    'operation'   => '=',
                ],
            ],
        ];
        update_option(self::MOCK_OPTION, 'a');

        // No debug logs.
        $this->mock_logger_debug_calls();

        EvaluateSuggestion::evaluate((object) $suggestion, [ 'source' => 'unit-tests' ]);

        remove_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_false');
    }

    /**
     * Test that suggestion evaluation logs when rule is not an object.
     */
    public function test_evaluation_logs_when_rule_not_object()
    {
        add_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');

        $suggestion = [
            'id'         => 'mock-gateway',
            'is_visible' => [
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
                [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
            ],
        ];
        update_option(self::MOCK_OPTION, 'a');

        $this->mock_logger_debug_calls(
            [
                [
                    '[mock-gateway] option: passed',
                    [ 'source' => 'unit-tests' ],
                ],
                [
                    '[mock-gateway] rule not an object: failed',
                    [ 'source' => 'unit-tests' ],
                ],
            ]
        );

        EvaluateSuggestion::evaluate((object) $suggestion, [ 'source' => 'unit-tests' ]);

        remove_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');
    }

    /**
     * Test that suggestion evaluation logs an anonymous spec.
     */
    public function test_evaluation_logs_anonymous_spec()
    {
        add_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');

        $suggestion = [
            'id'         => '', // empty ID and no 'title' field.
            'is_visible' => [
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
                [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
            ],
        ];
        update_option(self::MOCK_OPTION, 'a');

        $this->mock_logger_debug_calls(
            [
                [
                    '[anonymous-suggestion] option: passed',
                    [ 'source' => 'unit-tests' ],
                ],
                [
                    '[anonymous-suggestion] rule not an object: failed',
                    [ 'source' => 'unit-tests' ],
                ],
            ]
        );

        EvaluateSuggestion::evaluate((object) $suggestion, [ 'source' => 'unit-tests' ]);

        remove_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');
    }

    /**
     * Test that suggestion evaluation logs to the default source.
     */
    public function test_evaluation_logs_to_default_source()
    {
        add_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');

        $suggestion = [
            'id'         => 'mock-gateway',
            'is_visible' => [
                (object) [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
                [
                    'type'        => 'option',
                    'option_name' => self::MOCK_OPTION,
                    'value'       => 'a',
                    'default'     => null,
                    'operation'   => '=',
                ],
            ],
        ];
        update_option(self::MOCK_OPTION, 'a');

        $this->mock_logger_debug_calls(
            [
                [
                    '[mock-gateway] option: passed',
                    [ 'source' => 'wc-payment-gateway-suggestions' ],
                ],
                [
                    '[mock-gateway] rule not an object: failed',
                    [ 'source' => 'wc-payment-gateway-suggestions' ],
                ],
            ]
        );

        EvaluateSuggestion::evaluate((object) $suggestion);

        remove_filter('woocommerce_admin_remote_specs_evaluator_should_log', '__return_true');
    }

    /**
     * Test that the memo is set correctly.
     */
    public function test_memo_set_correctly()
    {
        $specs = [
            [
                'id'         => 'test-gateway-1',
                'is_visible' => true,
            ],
            [
                'id'         => 'test-gateway-2',
                'is_visible' => false,
            ],
        ];

        $result = TestableEvaluateSuggestion::evaluate_specs($specs);
        $memo   = TestableEvaluateSuggestion::get_memo_for_tests();

        $this->assertCount(1, $memo);
        $memo_key = array_keys($memo)[0];
        $this->assertEquals($result, $memo[ $memo_key ]);
        $this->assertCount(1, $result['suggestions']);
        $this->assertEquals('test-gateway-1', $result['suggestions'][0]->id);
    }

    /**
     * Overrides the WC logger.
     *
     * @return mixed
     */
    public function override_wc_logger()
    {
        return $this->mock_logger;
    }

    /**
     * Set expectations for the logger debug calls with each consecutive call args.
     *
     * @param array $calls_args List of expected arguments for each call.
     *
     * @return void
     */
    private function mock_logger_debug_calls(array $calls_args = [])
    {
        if (empty($calls_args)) {
            $this->mock_logger
                ->expects($this->never())
                ->method('debug');

            return;
        }

        $this->mock_logger
            ->expects($this->exactly(count($calls_args)))
            ->method('debug')
            ->willReturnCallback(
                function (...$args) use (&$calls_args) {
                    $expected_args = array_shift($calls_args);
                    $this->assertSame($expected_args, $args);
                }
            );
    }
}

//phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4.IncorrectClassName
/**
 * TestableEvaluateSuggestion class.
 */
class TestableEvaluateSuggestion extends EvaluateSuggestion
{
    /**
     * Get the memo for testing.
     *
     * @return array
     */
    public static function get_memo_for_tests()
    {
        return self::$memo;
    }
}
//phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4.IncorrectClassName
