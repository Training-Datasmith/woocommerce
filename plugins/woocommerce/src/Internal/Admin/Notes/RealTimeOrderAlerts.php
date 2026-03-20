<?php

declare (strict_types=1);
/**
 * WooCommerce Admin Real Time Order Alerts Note.
 *
 * Adds a note to download the mobile app to monitor store activity.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
/**
 * Real_Time_Order_Alerts
 */
class Real_Time_Order_Alerts
{
    /**
     * Note traits.
     */
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-real-time-order-alerts';
    /**
     * Get the note.
     *
     * @return Note
     */
    public static function get_note()
    {
        // Only add this note if the store is 3 months old.
        if (!self::is_wc_admin_active_in_date_range('month-3-6')) {
            return;
        }
        // Check that the previous mobile app note was not actioned.
        if (Mobile_App::has_note_been_actioned()) {
            return;
        }
        $content = __('Get notifications about store activity, including new orders and product reviews directly on your mobile devices with the Woo app.', 'woocommerce');
        $note = new Note();
        $note->set_title(__('Get real-time order alerts anywhere', 'woocommerce'));
        $note->set_content($content);
        $note->set_content_data((object) []);
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('learn-more', __('Learn more', 'woocommerce'), 'https://woocommerce.com/mobile/?utm_source=inbox&utm_medium=product');
        return $note;
    }
}