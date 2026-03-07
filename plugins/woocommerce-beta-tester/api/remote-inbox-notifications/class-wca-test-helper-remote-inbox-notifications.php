<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

require_once __DIR__ . '/class-wc-beta-tester-remote-inbox-notifications-helper.php';

register_woocommerce_admin_test_helper_rest_route(
    '/remote-inbox-notifications',
    WCA_Test_Helper_Remote_Inbox_Notifications::get_items(...),
    [
        'methods' => 'GET',
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/remote-inbox-notifications/(?P<id>\d+)/delete',
    WCA_Test_Helper_Remote_Inbox_Notifications::delete(...),
    [
        'methods' => 'POST',
        'args'    => [
            'id' => [
                'description' => 'Rest API endpoint.',
                'type'        => 'integer',
                'required'    => true,
            ],
        ],
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/remote-inbox-notifications/(?P<name>(.*)+)/test',
    WCA_Test_Helper_Remote_Inbox_Notifications::test(...),
    [
        'methods' => 'GET',
        'args'    => [
            'name' => [
                'description' => 'Note name.',
                'type'        => 'string',
                'required'    => true,
            ],
        ],
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/remote-inbox-notifications/delete-all',
    WCA_Test_Helper_Remote_Inbox_Notifications::delete_all_items(...),
    [
        'methods' => 'POST',
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/remote-inbox-notifications/import',
    WCA_Test_Helper_Remote_Inbox_Notifications::import(...),
    [
        'methods' => 'POST',
    ]
);

/**
 * Class WCA_Test_Helper_Remote_Inbox_Notifications.
 */
class WCA_Test_Helper_Remote_Inbox_Notifications
{
    /**
     * Delete a notification.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function delete($request)
    {
        WC_Beta_Tester_Remote_Inbox_Notifications_Helper::delete_by_id($request->get_param('id'));

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => 'Remote inbox notification deleted.',
            ],
            200
        );
    }

    /**
     * Delete all notifications.
     *
     * @return WP_REST_Response
     */
    public static function delete_all_items()
    {
        $deleted = WC_Beta_Tester_Remote_Inbox_Notifications_Helper::delete_all();
        return new WP_REST_Response($deleted, 200);
    }

    /**
     * Return a list of class-based notes.
     * These should be excluded from the list of notes to be displayed in the inbox as we don't have control over them.
     */
    private static function get_notes_to_exclude(): array
    {
        $vars       = [ 'other_note_classes', 'note_classes_to_added_or_updated' ];
        $note_names = [];
        $reflection = new ReflectionClass(\Automattic\WooCommerce\Internal\Admin\Events::class);
        foreach ($vars as $var) {
            $property = $reflection->getProperty($var);
            $notes      = $property->getValue();
            $note_names = array_merge(
                $note_names,
                array_map(
                    fn ($note) => $note::NOTE_NAME,
                    $notes
                )
            );
        }

        return $note_names;
    }

    /**
     * Return all notifications.
     *
     * @return WP_REST_Response
     */
    public static function get_items()
    {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_admin_notes ORDER BY note_id desc", ARRAY_A);

        $notes_added_via_classes = self::get_notes_to_exclude();
        $items                   = array_filter(
            $items,
            fn (array $item) => ! in_array($item['name'], $notes_added_via_classes, true)
        );

        return new WP_REST_Response(array_values($items), 200);
    }

    /**
     * Test and run a remote inbox notification.
     *
     * @param WP_REST_Request $request The full request data.
     *
     * @return WP_REST_Response
     */
    public static function test($request)
    {
        $name   = $request->get_param('name');
        $result = WC_Beta_Tester_Remote_Inbox_Notifications_Helper::test($name, null, true);

        if ($result instanceof WP_Error) {
            $message = $result->get_error_data();
        } else {
            $message = $name . ': All rules passed successfully';
        }

        return new WP_REST_Response(
            [
                'success' => true === $result,
                'message' => $message,
            ],
            200
        );
    }

    /**
     * Import remote inbox notifications.
     *
     * @param WP_REST_Request $request The full request data.
     *
     * @return WP_REST_Response
     */
    public static function import($request)
    {
        // Get the JSON data from the request body.
        $specs = json_decode(wp_json_encode($request->get_json_params()));
        WC_Beta_Tester_Remote_Inbox_Notifications_Helper::import($specs);

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => 'Remote inbox notifications imported.',
            ],
            200
        );
    }
}
