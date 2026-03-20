<?php

/**
 * Adds a note when the email improvements feature is enabled for existing stores
 * or when the feature is not enabled to try the new templates.
 *
 * @since 9.9.0
 */
declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
use Automattic\Woo_Commerce\Internal\Admin\Email_Improvements\Email_Improvements as EmailImprovementsFeature;
/**
 * EmailImprovements
 */
class Email_Improvements
{
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-email-improvements';
    /**
     * Get the note.
     *
     * @return Note|void
     */
    public static function get_note()
    {
        if (Email_Improvements_Feature::is_email_improvements_enabled_for_existing_stores()) {
            return self::get_email_improvements_enabled_note();
        }
        if (Email_Improvements_Feature::should_notify_merchant_about_email_improvements()) {
            return self::get_try_email_improvements_note();
        }
    }
    /**
     * Get the note for when the email improvements feature is enabled for existing stores.
     */
    private static function get_email_improvements_enabled_note(): \Automattic\Woo_Commerce\Admin\Notes\Note
    {
        $note = new Note();
        $note->set_title(__('Your store emails have had an upgrade!', 'woocommerce'));
        $note->set_content(__('We’ve made some exciting improvements to your email templates, including modern, shopper-friendly designs and new customization options. And if you’re using a block theme, you can automatically sync your theme styles! Head to your email settings to explore the new changes.', 'woocommerce'));
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('customize-your-emails', __('Customize your emails', 'woocommerce'), '?page=wc-settings&tab=email');
        return $note;
    }
    /**
     * Get the note for when the email improvements feature is disabled.
     */
    private static function get_try_email_improvements_note(): \Automattic\Woo_Commerce\Admin\Notes\Note
    {
        $note = new Note();
        $note->set_title(__('Store emails have had an upgrade!', 'woocommerce'));
        $note->set_content(__('We’ve made some exciting improvements to our email templates, including modern, shopper-friendly designs and new customization options. And if you’re using a block theme, you can automatically sync your theme styles! Head to your email settings to explore the new features.', 'woocommerce'));
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('try-the-new-templates', __('Try the new templates', 'woocommerce'), '?page=wc-settings&tab=email&try-new-templates');
        return $note;
    }
}