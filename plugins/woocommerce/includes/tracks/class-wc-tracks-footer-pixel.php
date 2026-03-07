<?php

declare(strict_types=1);
/**
 * Send Tracks events on behalf of a user using pixel images in page footer.
 *
 * @package WooCommerce\Tracks
 */

defined('ABSPATH') || exit;

/**
 * WC_Tracks_Footer_Pixel class.
 */
class WC_Tracks_Footer_Pixel
{
    /**
     * Singleton instance.
     *
     * @var WC_Tracks_Footer_Pixel
     */
    protected static $instance;

    /**
     * Events to send to Tracks.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Instantiate the singleton.
     *
     * @return WC_Tracks_Footer_Pixel
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new WC_Tracks_Footer_Pixel();
        }

        return self::$instance;
    }

    /**
     * Constructor - attach hooks to the singleton instance.
     */
    public function __construct()
    {
        add_action('admin_footer', $this->render_tracking_pixels(...));
        add_action('shutdown', $this->send_tracks_requests(...));
    }

    /**
     * Record a Tracks event
     *
     * @param  array $event Array of event properties.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function record_event($event): \WC_Tracks_Event|true
    {
        if (! $event instanceof WC_Tracks_Event) {
            $event = new WC_Tracks_Event($event);
        }

        if (is_wp_error($event)) {
            return $event;
        }

        self::instance()->add_event($event);

        return true;
    }

    /**
     * Add a Tracks event to the queue.
     *
     * @param WC_Tracks_Event $event Event to track.
     */
    public function add_event($event): void
    {
        $this->events[] = $event;
    }

    /**
     * Add events as tracking pixels to page footer.
     */
    public function render_tracking_pixels(): void
    {
        if (empty($this->events)) {
            return;
        }

        foreach ($this->events as $event) {
            $pixel = $event->build_pixel_url();

            if (! $pixel) {
                continue;
            }

            // Add the Request Timestamp and no cache parameter just before the HTTP request.
            $pixel = WC_Tracks_Client::add_request_timestamp_and_nocache($pixel);

            echo '<img style="position: fixed;" src="', esc_url($pixel), '" />';
        }

        $this->events = [];
    }

    /**
     * Fire off API calls for events that weren't converted to pixels.
     *
     * This handles wp_redirect().
     */
    public function send_tracks_requests(): void
    {
        if (empty($this->events)) {
            return;
        }

        foreach ($this->events as $event) {
            WC_Tracks_Client::record_event($event);
        }
    }

    /**
     * Get all events.
     */
    public static function get_events()
    {
        return self::instance()->events;
    }

    /**
     * Clear all queued events.
     */
    public static function clear_events(): void
    {
        self::instance()->events = [];
    }
}
