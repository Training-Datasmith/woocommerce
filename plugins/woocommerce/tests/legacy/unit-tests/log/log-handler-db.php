<?php

declare(strict_types=1);

/**
 * Class WC_Tests_Log_Handler_DB
 * @package WooCommerce\Tests\Log
 * @since 3.0.0
 */
class WC_Tests_Log_Handler_DB extends WC_Unit_Test_Case
{
    public function setUp(): void
    {
        parent::setUp();

        $this->handler = new WC_Log_Handler_DB([ 'threshold' => 'debug' ]);
    }

    /**
     * Test handle writes to database correctly.
     *
     * @since 3.0.0
     */
    public function test_handle()
    {
        global $wpdb;

        $time    = time();
        $context = [
            1,
            2,
            'a',
            'b',
            'key' => 'value',
        ];

        $this->handler->handle($time, 'debug', 'msg_debug', [ 'source' => 'source_debug' ]);
        $this->handler->handle($time, 'info', 'msg_info', [ 'source' => 'source_info' ]);
        $this->handler->handle($time, 'notice', 'msg_notice', [ 'source' => 'source_notice' ]);
        $this->handler->handle($time, 'warning', 'msg_warning', [ 'source' => 'source_warning' ]);
        $this->handler->handle($time, 'error', 'msg_error', [ 'source' => 'source_error' ]);
        $this->handler->handle($time, 'critical', 'msg_critical', [ 'source' => 'source_critical' ]);
        $this->handler->handle($time, 'alert', 'msg_alert', [ 'source' => 'source_alert' ]);
        $this->handler->handle($time, 'emergency', 'msg_emergency', [ 'source' => 'source_emergency' ]);

        $this->handler->handle($time, 'debug', 'context_test', $context);

        $log_entries = $wpdb->get_results("SELECT timestamp, level, message, source, context FROM {$wpdb->prefix}woocommerce_log", ARRAY_A);

        $expected_ts = date('Y-m-d H:i:s', $time);
        $expected    = [
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('debug'),
                'message'   => 'msg_debug',
                'source'    => 'source_debug',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('info'),
                'message'   => 'msg_info',
                'source'    => 'source_info',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('notice'),
                'message'   => 'msg_notice',
                'source'    => 'source_notice',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('warning'),
                'message'   => 'msg_warning',
                'source'    => 'source_warning',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('error'),
                'message'   => 'msg_error',
                'source'    => 'source_error',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('critical'),
                'message'   => 'msg_critical',
                'source'    => 'source_critical',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('alert'),
                'message'   => 'msg_alert',
                'source'    => 'source_alert',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('emergency'),
                'message'   => 'msg_emergency',
                'source'    => 'source_emergency',
                'context'   => '',
            ],
            [
                'timestamp' => $expected_ts,
                'level'     => WC_Log_Levels::get_level_severity('debug'),
                'message'   => 'context_test',
                'source'    => pathinfo(__FILE__, PATHINFO_FILENAME),
                'context'   => wp_json_encode($context, JSON_PRETTY_PRINT),
            ],
        ];

        $this->assertEquals($log_entries, $expected);
    }

    /**
     * Test flush.
     *
     * @since 3.0.0
     */
    public function test_flush()
    {
        global $wpdb;

        $time = time();

        $this->handler->handle($time, 'debug', '', []);

        $log_entries = $wpdb->get_results("SELECT timestamp, level, message, source FROM {$wpdb->prefix}woocommerce_log");
        $this->assertCount(1, $log_entries);

        WC_Log_Handler_DB::flush();

        $log_entries = $wpdb->get_results("SELECT timestamp, level, message, source FROM {$wpdb->prefix}woocommerce_log");
        $this->assertCount(0, $log_entries);
    }

    public function test_delete_logs_before_timestamp()
    {
        global $wpdb;

        $time = time();
        $this->handler->handle($time, 'debug', '', []);
        $this->handler->handle($time - 10, 'debug', '', []);

        $this->handler->delete_logs_before_timestamp($time);

        $log_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}woocommerce_log");
        $this->assertEquals(1, $log_count);
    }

}
