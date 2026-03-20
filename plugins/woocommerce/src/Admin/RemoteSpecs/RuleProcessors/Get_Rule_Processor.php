<?php

declare (strict_types=1);
/**
 * Gets the processor for the specified rule type.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Class encapsulating getting the processor for a given rule type.
 */
class Get_Rule_Processor
{
    /**
     * Get the processor for the specified rule type.
     *
     * @param string $rule_type The rule type.
     *
     * @return RuleProcessorInterface The matching processor for the specified rule type, or a FailRuleProcessor if no matching processor is found.
     */
    public static function get_processor($rule_type): \Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Plugins_Activated_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Publish_After_Time_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Publish_Before_Time_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Not_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Or_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Fail_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Pass_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Plugin_Version_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Stored_State_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Order_Count_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Wc_Admin_Active_For_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Product_Count_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Onboarding_Profile_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Is_Ecommerce_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Is_Woo_Express_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Base_Location_Country_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Base_Location_State_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Note_Status_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Option_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Woo_Commerce_Admin_Updated_Rule_Processor|\Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Total_Payments_Volume_Processor
    {
        return match ($rule_type) {
            'plugins_activated' => new Plugins_Activated_Rule_Processor(),
            'publish_after_time' => new Publish_After_Time_Rule_Processor(),
            'publish_before_time' => new Publish_Before_Time_Rule_Processor(),
            'not' => new Not_Rule_Processor(),
            'or' => new Or_Rule_Processor(),
            'fail' => new Fail_Rule_Processor(),
            'pass' => new Pass_Rule_Processor(),
            'plugin_version' => new Plugin_Version_Rule_Processor(),
            'stored_state' => new Stored_State_Rule_Processor(),
            'order_count' => new Order_Count_Rule_Processor(),
            'wcadmin_active_for' => new Wc_Admin_Active_For_Rule_Processor(),
            'product_count' => new Product_Count_Rule_Processor(),
            'onboarding_profile' => new Onboarding_Profile_Rule_Processor(),
            'is_ecommerce' => new Is_Ecommerce_Rule_Processor(),
            'is_woo_express' => new Is_Woo_Express_Rule_Processor(),
            'base_location_country' => new Base_Location_Country_Rule_Processor(),
            'base_location_state' => new Base_Location_State_Rule_Processor(),
            'note_status' => new Note_Status_Rule_Processor(),
            'option' => new Option_Rule_Processor(),
            'wca_updated' => new Woo_Commerce_Admin_Updated_Rule_Processor(),
            'total_payments_value' => new Total_Payments_Volume_Processor(),
            default => new Fail_Rule_Processor(),
        };
    }
}