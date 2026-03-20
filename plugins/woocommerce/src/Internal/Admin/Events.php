<?php

declare (strict_types=1);
/**
 * Handle cron events.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Features\Payment_Gateway_Suggestions\Payment_Gateway_Suggestions_Data_Source_Poller;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Remote_Inbox_Notifications\Remote_Inbox_Notifications_Data_Source_Poller;
use Automattic\Woo_Commerce\Admin\Remote_Inbox_Notifications\Remote_Inbox_Notifications_Engine;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Customize_Store_With_Blocks;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Customizing_Product_Catalog;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Edit_Products_On_The_Move;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Email_Improvements;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Euvat_Number;
use Automattic\Woo_Commerce\Internal\Admin\Notes\First_Product;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Install_Jp_And_Wcs_Plugins;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Launch_Checklist;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Magento_Migration;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Manage_Orders_On_The_Go;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Marketing_Jetpack;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Migrate_From_Shopify;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Mobile_App;
use Automattic\Woo_Commerce\Internal\Admin\Notes\New_Sales_Record;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Onboarding_Payments;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Online_Clothing_Store;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Order_Milestones;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Payments_More_Info_Needed;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Payments_Remind_Me_Later;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Performance_On_Mobile;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Personalize_Store;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Real_Time_Order_Alerts;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Scheduled_Updates_Promotion;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Selling_Online_Courses;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Tracking_Opt_In;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Unsecured_Report_Files;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Woo_Commerce_Payments;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Woo_Commerce_Subscriptions;
use Automattic\Woo_Commerce\Internal\Admin\Notes\Woo_Subscriptions_Notes;
use Automattic\Woo_Commerce\Internal\Admin\Remote_Free_Extensions\Remote_Free_Extensions_Data_Source_Poller;
use Automattic\Woo_Commerce\Internal\Admin\Schedulers\Mailchimp_Scheduler;
/**
 * Events Class.
 */
class Events
{
    /**
     * The single instance of the class.
     *
     * @var object
     */
    protected static $instance;
    /**
     * Constructor
     */
    protected function __construct()
    {
    }
    /**
     * Array of note class to be added or updated.
     */
    private static array $note_classes_to_added_or_updated = [Customize_Store_With_Blocks::class, Customizing_Product_Catalog::class, Edit_Products_On_The_Move::class, Email_Improvements::class, Euvat_Number::class, First_Product::class, Launch_Checklist::class, Magento_Migration::class, Manage_Orders_On_The_Go::class, Marketing_Jetpack::class, Migrate_From_Shopify::class, Mobile_App::class, New_Sales_Record::class, Onboarding_Payments::class, Online_Clothing_Store::class, Payments_More_Info_Needed::class, Payments_Remind_Me_Later::class, Performance_On_Mobile::class, Personalize_Store::class, Real_Time_Order_Alerts::class, Scheduled_Updates_Promotion::class, Tracking_Opt_In::class, Woo_Commerce_Payments::class, Woo_Commerce_Subscriptions::class];
    /**
     * The other note classes that are added in other places.
     */
    private static array $other_note_classes = [Install_Jp_And_Wcs_Plugins::class, Order_Milestones::class, Selling_Online_Courses::class, Unsecured_Report_Files::class, Woo_Subscriptions_Notes::class];
    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    final public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * Cron event handlers.
     */
    public function init(): void
    {
        add_action('wc_admin_daily', $this->do_wc_admin_daily(...));
        add_filter('woocommerce_get_note_from_db', $this->get_note_from_db(...), 10, 1);
        // Initialize the WC_Notes_Refund_Returns Note to attach hook.
        \WC_Notes_Refund_Returns::init();
    }
    /**
     * Daily events to run.
     *
     * Note: Order_Milestones::possibly_add_note is hooked to this as well.
     */
    public function do_wc_admin_daily(): void
    {
        $this->possibly_add_notes();
        $this->possibly_delete_notes();
        $this->possibly_update_notes();
        $this->possibly_refresh_data_source_pollers();
        if ($this->is_remote_inbox_notifications_enabled()) {
            Remote_Inbox_Notifications_Data_Source_Poller::get_instance()->read_specs_from_data_sources();
            Remote_Inbox_Notifications_Engine::run();
        }
        if (Features::is_enabled('core-profiler')) {
            (new Mailchimp_Scheduler())->run();
        }
    }
    /**
     * Get note.
     *
     * @param Note $note_from_db The note object from the database.
     */
    public function get_note_from_db($note_from_db)
    {
        if (!$note_from_db instanceof Note || get_user_locale() === $note_from_db->get_locale()) {
            return $note_from_db;
        }
        $note_classes = array_merge(self::$note_classes_to_added_or_updated, self::$other_note_classes);
        foreach ($note_classes as $note_class) {
            if (defined("{$note_class}::NOTE_NAME") && $note_class::NOTE_NAME === $note_from_db->get_name()) {
                $note_from_class = method_exists($note_class, 'get_note') ? $note_class::get_note() : null;
                if ($note_from_class instanceof Note) {
                    $note = clone $note_from_db;
                    $note->set_title($note_from_class->get_title());
                    $note->set_content($note_from_class->get_content());
                    $actions = $note_from_class->get_actions();
                    foreach ($actions as $action) {
                        $matching_action = $note->get_action($action->name);
                        if ($matching_action && $matching_action->id) {
                            $action->id = $matching_action->id;
                        }
                    }
                    $note->set_actions($actions);
                    return $note;
                }
                break;
            }
        }
        return $note_from_db;
    }
    /**
     * Adds notes that should be added.
     */
    protected function possibly_add_notes()
    {
        foreach (self::$note_classes_to_added_or_updated as $note_class) {
            if (method_exists($note_class, 'possibly_add_note')) {
                $note_class::possibly_add_note();
            }
        }
    }
    /**
     * Deletes notes that should be deleted.
     */
    protected function possibly_delete_notes()
    {
        Payments_Remind_Me_Later::delete_if_not_applicable();
        Payments_More_Info_Needed::delete_if_not_applicable();
    }
    /**
     * Updates notes that should be updated.
     */
    protected function possibly_update_notes()
    {
        foreach (self::$note_classes_to_added_or_updated as $note_class) {
            if (method_exists($note_class, 'possibly_update_note')) {
                $note_class::possibly_update_note();
            }
        }
    }
    /**
     * Checks if remote inbox notifications are enabled.
     *
     * @return bool Whether remote inbox notifications are enabled.
     */
    protected function is_remote_inbox_notifications_enabled(): bool
    {
        // Check if the feature flag is disabled.
        if (!Features::is_enabled('remote-inbox-notifications')) {
            return false;
        }
        // Check if the site has opted out of marketplace suggestions.
        if (get_option('woocommerce_show_marketplace_suggestions', 'yes') !== 'yes') {
            return false;
        }
        // All checks have passed.
        return true;
    }
    /**
     * Checks if merchant email notifications are enabled.
     *
     * @return bool Whether merchant email notifications are enabled.
     */
    protected function is_merchant_email_notifications_enabled(): bool
    {
        // Check if the feature flag is disabled.
        if (get_option('woocommerce_merchant_email_notifications', 'no') !== 'yes') {
            return false;
        }
        // All checks have passed.
        return true;
    }
    /**
     *   Refresh transient for the following DataSourcePollers on wc_admin_daily cron job.
     *   - PaymentGatewaySuggestionsDataSourcePoller
     *   - RemoteFreeExtensionsDataSourcePoller
     */
    protected function possibly_refresh_data_source_pollers()
    {
        $completed_tasks = get_option('woocommerce_task_list_tracked_completed_tasks', []);
        if (!in_array('payments', $completed_tasks, true) && !in_array('woocommerce-payments', $completed_tasks, true)) {
            Payment_Gateway_Suggestions_Data_Source_Poller::get_instance()->read_specs_from_data_sources();
        }
        if (!in_array('store_details', $completed_tasks, true) && !in_array('marketing', $completed_tasks, true)) {
            Remote_Free_Extensions_Data_Source_Poller::get_instance()->read_specs_from_data_sources();
        }
    }
}