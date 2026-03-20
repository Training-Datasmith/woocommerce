<?php

declare (strict_types=1);
/**
 * WooCommerce Admin Payment Reminder Me later
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
use Automattic\Woo_Commerce\Internal\Admin\Wc_Pay_Welcome_Page;
defined('ABSPATH') || exit;
/**
 * PaymentsRemindMeLater
 */
class Payments_Remind_Me_Later
{
    /**
     * Note traits.
     */
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-payments-remind-me-later';
    /**
     * Should this note exist?
     */
    public static function is_applicable()
    {
        return self::should_display_note();
    }
    /**
     * Returns true if we should display the note.
     */
    public static function should_display_note(): bool
    {
        // A WooPayments incentive must be visible.
        if (!Wc_Pay_Welcome_Page::instance()->has_incentive()) {
            return false;
        }
        // Less than 3 days since viewing welcome page.
        $view_timestamp = get_option('wcpay_welcome_page_viewed_timestamp', false);
        if (!$view_timestamp || time() - $view_timestamp < 3 * DAY_IN_SECONDS) {
            return false;
        }
        return true;
    }
    /**
     * Get the note.
     *
     * @return Note
     */
    public static function get_note()
    {
        if (!self::should_display_note()) {
            return;
        }
        /* translators: 1: Payment provider name. */
        $content = sprintf(__('Save up to $800 in fees by managing transactions with %1$s. With %1$s, you can securely accept major cards, Apple Pay, and payments in over 100 currencies.', 'woocommerce'), 'WooPayments');
        $note = new Note();
        /* translators: %s: Payment provider name. */
        $note->set_title(sprintf(__('Save big with %s', 'woocommerce'), 'WooPayments'));
        $note->set_content($content);
        $note->set_content_data((object) []);
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('learn-more', __('Learn more', 'woocommerce'), admin_url('admin.php?page=wc-admin&path=/wc-pay-welcome-page'));
        return $note;
    }
}