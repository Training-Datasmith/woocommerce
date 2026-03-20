<?php

declare (strict_types=1);
/**
 * WooCommerce Admin Mobile App Note Provider.
 *
 * Adds a note to the merchant's inbox showing the benefits of the mobile app.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
/**
 * Mobile_App
 */
class Mobile_App
{
    /**
     * Note traits.
     */
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-mobile-app';
    /**
     * Get the note.
     *
     * @return Note
     */
    public static function get_note()
    {
        // We want to show the mobile app note after day 2.
        $two_days_in_seconds = 2 * DAY_IN_SECONDS;
        if (!self::is_wc_admin_active_in_date_range('week-1', $two_days_in_seconds)) {
            return;
        }
        $content = __('Install the WooCommerce mobile app to manage orders, receive sales notifications, and view key metrics — wherever you are.', 'woocommerce');
        $note = new Note();
        $note->set_title(__('Install Woo mobile app', 'woocommerce'));
        $note->set_content($content);
        $note->set_content_data((object) []);
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('learn-more', __('Learn more', 'woocommerce'), 'https://woocommerce.com/mobile/?utm_medium=product');
        return $note;
    }
}