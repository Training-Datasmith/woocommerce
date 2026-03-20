<?php

declare (strict_types=1);
/**
 * Handles running payment method specs
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Remote_Free_Extensions;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Remote_Specs\Remote_Specs_Engine;
/**
 * Remote Payment Methods engine.
 * This goes through the specs and gets eligible payment methods.
 */
class Init extends Remote_Specs_Engine
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_updated', self::delete_specs_transient(...));
    }
    /**
     * Go through the specs and run them.
     *
     * @param array $allowed_bundles Optional array of allowed bundles to be returned.
     * @return array
     */
    public static function get_extensions($allowed_bundles = [])
    {
        $locale = get_user_locale();
        $specs = self::get_specs();
        $results = Evaluate_Extension::evaluate_bundles($specs, $allowed_bundles);
        $specs_to_return = $results['bundles'];
        $specs_to_save = null;
        $plugins = array_filter($results['bundles'], fn(array $bundle) => count($bundle['plugins']) > 0);
        if (empty($plugins)) {
            // When no plugins are visible, replace it with defaults and save for 3 hours.
            $specs_to_save = Default_Free_Extensions::get_all();
            $specs_to_return = Evaluate_Extension::evaluate_bundles($specs_to_save, $allowed_bundles)['bundles'];
        } elseif (count($results['errors']) > 0) {
            // When suggestions is not empty but has errors, save it for 3 hours.
            $specs_to_save = $specs;
        }
        // When plugins is not empty but has errors, save it for 3 hours.
        if (count($results['errors']) > 0) {
            self::log_errors($results['errors']);
        }
        if ($specs_to_save) {
            Remote_Free_Extensions_Data_Source_Poller::get_instance()->set_specs_transient([$locale => $specs_to_save], 3 * HOUR_IN_SECONDS);
        }
        return $specs_to_return;
    }
    /**
     * Delete the specs transient.
     */
    public static function delete_specs_transient(): void
    {
        Remote_Free_Extensions_Data_Source_Poller::get_instance()->delete_specs_transient();
    }
    /**
     * Get specs or fetch remotely if they don't exist.
     */
    public static function get_specs()
    {
        if ('no' === get_option('woocommerce_show_marketplace_suggestions', 'yes')) {
            return Default_Free_Extensions::get_all();
        }
        $specs = Remote_Free_Extensions_Data_Source_Poller::get_instance()->get_specs_from_data_sources();
        // Fetch specs if they don't yet exist.
        if (false === $specs || !is_array($specs) || 0 === count($specs)) {
            return Default_Free_Extensions::get_all();
        }
        return $specs;
    }
}