<?php

declare (strict_types=1);
/**
 * Define deprecated classes to support changing the naming convention of
 * admin notes.
 */
namespace Automattic\Woo_Commerce\Admin\Notes;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Deprecated_Class_Facade;
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
/**
 * WC_Admin_Note.
 *
 * @deprecated since 4.8.0, use Note
 */
class WC_Admin_Note extends Deprecated_Class_Facade
{
    // These constants must be redeclared as to not break plugins that use them.
    public const E_WC_ADMIN_NOTE_ERROR = Note::E_WC_ADMIN_NOTE_ERROR;
    public const E_WC_ADMIN_NOTE_WARNING = Note::E_WC_ADMIN_NOTE_WARNING;
    public const E_WC_ADMIN_NOTE_UPDATE = Note::E_WC_ADMIN_NOTE_UPDATE;
    public const E_WC_ADMIN_NOTE_INFORMATIONAL = Note::E_WC_ADMIN_NOTE_INFORMATIONAL;
    public const E_WC_ADMIN_NOTE_MARKETING = Note::E_WC_ADMIN_NOTE_MARKETING;
    public const E_WC_ADMIN_NOTE_SURVEY = Note::E_WC_ADMIN_NOTE_SURVEY;
    public const E_WC_ADMIN_NOTE_PENDING = Note::E_WC_ADMIN_NOTE_PENDING;
    public const E_WC_ADMIN_NOTE_UNACTIONED = Note::E_WC_ADMIN_NOTE_UNACTIONED;
    public const E_WC_ADMIN_NOTE_ACTIONED = Note::E_WC_ADMIN_NOTE_ACTIONED;
    public const E_WC_ADMIN_NOTE_SNOOZED = Note::E_WC_ADMIN_NOTE_SNOOZED;
    public const E_WC_ADMIN_NOTE_EMAIL = Note::E_WC_ADMIN_NOTE_EMAIL;
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Admin\Notes\Note::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
    /**
     * Note constructor. Loads note data.
     *
     * @param mixed $data Note data, object, or ID.
     */
    public function __construct($data = '')
    {
        $this->instance = new static::$facade_over_classname($data);
    }
}
/**
 * WC_Admin_Notes.
 *
 * @deprecated since 4.8.0, use Notes
 */
class WC_Admin_Notes extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Admin\Notes\Notes::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Customize_Store_With_Blocks.
 *
 * @deprecated since 4.8.0, use CustomizeStoreWithBlocks
 */
class WC_Admin_Notes_Customize_Store_With_Blocks extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Customize_Store_With_Blocks::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Edit_Products_On_The_Move.
 *
 * @deprecated since 4.8.0, use EditProductsOnTheMove
 */
class WC_Admin_Notes_Edit_Products_On_The_Move extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Edit_Products_On_The_Move::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_EU_VAT_Number.
 *
 * @deprecated since 4.8.0, use EUVATNumber
 */
class WC_Admin_Notes_EU_VAT_Number extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Euvat_Number::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Facebook_Marketing_Expert.
 *
 * @deprecated since 4.8.0, use FacebookMarketingExpert
 */
class WC_Admin_Notes_Facebook_Marketing_Expert extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\FacebookMarketingExpert';
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_First_Product.
 *
 * @deprecated since 4.8.0, use FirstProduct
 */
class WC_Admin_Notes_First_Product extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\First_Product::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Giving_Feedback_Notes.
 *
 * @deprecated since 4.8.0, use GivingFeedbackNotes
 */
class WC_Admin_Notes_Giving_Feedback_Notes extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Giving_Feedback_Notes::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Install_JP_And_WCS_Plugins.
 *
 * @deprecated since 4.8.0, use InstallJPAndWCSPlugins
 */
class WC_Admin_Notes_Install_JP_And_WCS_Plugins extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Install_Jp_And_Wcs_Plugins::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Launch_Checklist.
 *
 * @deprecated since 4.8.0, use LaunchChecklist
 */
class WC_Admin_Notes_Launch_Checklist extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Launch_Checklist::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Migrate_From_Shopify.
 *
 * @deprecated since 4.8.0, use MigrateFromShopify
 */
class WC_Admin_Notes_Migrate_From_Shopify extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Migrate_From_Shopify::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Mobile_App.
 *
 * @deprecated since 4.8.0, use MobileApp
 */
class WC_Admin_Notes_Mobile_App extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Mobile_App::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_New_Sales_Record.
 *
 * @deprecated since 4.8.0, use NewSalesRecord
 */
class WC_Admin_Notes_New_Sales_Record extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\New_Sales_Record::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Onboarding_Email_Marketing.
 *
 * @deprecated since 4.8.0, use OnboardingEmailMarketing
 */
class WC_Admin_Notes_Onboarding_Email_Marketing extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\Notes\OnboardingEmailMarketing';
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Onboarding_Payments.
 *
 * @deprecated since 4.8.0, use OnboardingPayments
 */
class WC_Admin_Notes_Onboarding_Payments extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Onboarding_Payments::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Online_Clothing_Store.
 *
 * @deprecated since 4.8.0, use OnlineClothingStore
 */
class WC_Admin_Notes_Online_Clothing_Store extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Online_Clothing_Store::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Order_Milestones.
 *
 * @deprecated since 4.8.0, use OrderMilestones
 */
class WC_Admin_Notes_Order_Milestones extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Order_Milestones::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Performance_On_Mobile.
 *
 * @deprecated since 4.8.0, use PerformanceOnMobile
 */
class WC_Admin_Notes_Performance_On_Mobile extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Performance_On_Mobile::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Personalize_Store.
 *
 * @deprecated since 4.8.0, use PersonalizeStore
 */
class WC_Admin_Notes_Personalize_Store extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Personalize_Store::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Real_Time_Order_Alerts.
 *
 * @deprecated since 4.8.0, use RealTimeOrderAlerts
 */
class WC_Admin_Notes_Real_Time_Order_Alerts extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Real_Time_Order_Alerts::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Selling_Online_Courses.
 *
 * @deprecated since 4.8.0, use SellingOnlineCourses
 */
class WC_Admin_Notes_Selling_Online_Courses extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Selling_Online_Courses::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Tracking_Opt_In.
 *
 * @deprecated since 4.8.0, use TrackingOptIn
 */
class WC_Admin_Notes_Tracking_Opt_In extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Tracking_Opt_In::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_Woo_Subscriptions_Notes.
 *
 * @deprecated since 4.8.0, use WooSubscriptionsNotes
 */
class WC_Admin_Notes_Woo_Subscriptions_Notes extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Woo_Subscriptions_Notes::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_WooCommerce_Payments.
 *
 * @deprecated since 4.8.0, use WooCommercePayments
 */
class Wc_admin_notes_woo_Commerce_payments extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Woo_Commerce_Payments::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}
/**
 * WC_Admin_Notes_WooCommerce_Subscriptions.
 *
 * @deprecated since 4.8.0, use WooCommerceSubscriptions
 */
class Wc_admin_notes_woo_Commerce_subscriptions extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Notes\Woo_Commerce_Subscriptions::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '4.8.0';
}