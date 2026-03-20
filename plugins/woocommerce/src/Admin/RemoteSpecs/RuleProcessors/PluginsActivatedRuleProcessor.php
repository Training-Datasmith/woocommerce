<?php

declare (strict_types=1);
/**
 * Rule processor for sending when the provided plugins are activated.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Plugins_Provider\Plugins_Provider;
/**
 * Rule processor for sending when the provided plugins are activated.
 */
class Plugins_Activated_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * The plugins provider.
     *
     * @var PluginsProviderInterface
     */
    protected $plugins_provider;
    /**
     * Constructor.
     *
     * @param PluginsProviderInterface $plugins_provider The plugins provider.
     */
    public function __construct($plugins_provider = null)
    {
        $this->plugins_provider = $plugins_provider ?? new Plugins_Provider();
    }
    /**
     * Process the rule.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool Whether the rule passes or not.
     */
    public function process($rule, $stored_state): bool
    {
        if (!is_countable($rule->plugins) || 0 === count($rule->plugins)) {
            return false;
        }
        $active_plugin_slugs = $this->plugins_provider->get_active_plugin_slugs();
        foreach ($rule->plugins as $plugin_slug) {
            if (!is_string($plugin_slug)) {
                $logger = wc_get_logger();
                $logger->warning(__('Invalid plugin slug provided in the plugins activated rule.', 'woocommerce'));
                return false;
            }
            if (!in_array($plugin_slug, $active_plugin_slugs, true)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Validates the rule.
     *
     * @param object $rule The rule to validate.
     *
     * @return bool Pass/fail.
     */
    public function validate($rule): bool
    {
        if (!isset($rule->plugins) || !is_array($rule->plugins)) {
            return false;
        }
        return true;
    }
}