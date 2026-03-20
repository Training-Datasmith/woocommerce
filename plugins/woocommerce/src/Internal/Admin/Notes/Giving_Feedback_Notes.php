<?php

declare (strict_types=1);
/**
 * WooCommerce Admin (Dashboard) Giving feedback notes provider
 *
 * Adds notes to the merchant's inbox about giving feedback.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
use Automattic\Woo_Commerce\Internal\Admin\Survey;
/**
 * Giving_Feedback_Notes
 */
class Giving_Feedback_Notes
{
    /**
     * Note traits.
     */
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-store-notice-giving-feedback-2';
    /**
     * Get the note.
     *
     * @return Note
     */
    public static function get_note()
    {
        if (!self::is_wc_admin_active_in_date_range('week-1-4')) {
            return;
        }
        // Otherwise, create our new note.
        $note = new Note();
        $note->set_title(__('You\'re invited to share your experience', 'woocommerce'));
        $note->set_content(__('Now that you’ve chosen us as a partner, our goal is to make sure we\'re providing the right tools to meet your needs. We\'re looking forward to having your feedback on the store setup experience so we can improve it in the future.', 'woocommerce'));
        $note->set_content_data((object) []);
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('share-feedback', __('Share feedback', 'woocommerce'), Survey::get_url('/store-setup-survey'));
        return $note;
    }
}