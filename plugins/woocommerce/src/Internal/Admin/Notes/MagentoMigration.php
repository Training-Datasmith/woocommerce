<?php

declare (strict_types=1);
/**
 * WooCommerce Admin note on how to migrate from Magento.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Notes;

use Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Profile;
defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Note_Traits;
/**
 * MagentoMigration
 */
class Magento_Migration
{
    /**
     * Note traits.
     */
    use Note_Traits;
    /**
     * Name of the note for use in the database.
     */
    public const NOTE_NAME = 'wc-admin-magento-migration';
    /**
     * Attach hooks.
     */
    public function __construct()
    {
        add_action('update_option_' . Onboarding_Profile::DATA_OPTION, self::possibly_add_note(...));
        add_action('woocommerce_admin_magento_migration_note', self::save_note(...));
    }
    /**
     * Add the note if it passes predefined conditions.
     */
    public static function possibly_add_note()
    {
        $onboarding_profile = get_option(Onboarding_Profile::DATA_OPTION, []);
        if (empty($onboarding_profile)) {
            return;
        }
        if (!isset($onboarding_profile['other_platform']) || 'magento' !== $onboarding_profile['other_platform']) {
            return;
        }
        if (!isset($onboarding_profile['setup_client']) || $onboarding_profile['setup_client']) {
            return;
        }
        WC()->queue()->schedule_single(time() + 5 * MINUTE_IN_SECONDS, 'woocommerce_admin_magento_migration_note');
    }
    /**
     * Save the note to the database.
     */
    public static function save_note(): void
    {
        $note = self::get_note();
        if (self::note_exists()) {
            return;
        }
        $note->save();
    }
    /**
     * Get the note.
     */
    public static function get_note(): \Automattic\Woo_Commerce\Admin\Notes\Note
    {
        $note = new Note();
        $note->set_title(__('How to Migrate from Magento to WooCommerce', 'woocommerce'));
        $note->set_content(__('Changing platforms might seem like a big hurdle to overcome, but it is easier than you might think to move your products, customers, and orders to WooCommerce. This article will help you with going through this process.', 'woocommerce'));
        $note->set_content_data((object) []);
        $note->set_type(Note::E_WC_ADMIN_NOTE_INFORMATIONAL);
        $note->set_name(self::NOTE_NAME);
        $note->set_source('woocommerce-admin');
        $note->add_action('learn-more', __('Learn more', 'woocommerce'), 'https://woocommerce.com/posts/how-migrate-from-magento-to-woocommerce/?utm_source=inbox');
        return $note;
    }
}