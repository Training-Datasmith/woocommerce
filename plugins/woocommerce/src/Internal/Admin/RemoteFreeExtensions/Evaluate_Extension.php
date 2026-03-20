<?php

declare (strict_types=1);
/**
 * Evaluates the spec and returns a status.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Remote_Free_Extensions;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Plugins_Helper;
use Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Evaluate_Overrides;
use Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Rule_Evaluator;
/**
 * Evaluates the extension and returns it.
 */
class Evaluate_Extension
{
    /**
     * Evaluates the extension and returns it.
     *
     * @param object $extension The extension to evaluate.
     * @return object The evaluated extension.
     */
    private static function evaluate(\stdClass $extension): \stdClass
    {
        global $wp_version;
        $rule_evaluator = new Rule_Evaluator();
        if (isset($extension->is_visible)) {
            $is_visible = $rule_evaluator->evaluate($extension->is_visible);
            $extension->is_visible = $is_visible;
        } else {
            $extension->is_visible = true;
        }
        // Run PHP and WP version chcecks.
        if (true === $extension->is_visible) {
            if (isset($extension->min_php_version) && !version_compare(PHP_VERSION, $extension->min_php_version, '>=')) {
                $extension->is_visible = false;
            }
            if (isset($extension->min_wp_version) && !version_compare($wp_version, $extension->min_wp_version, '>=')) {
                $extension->is_visible = false;
            }
        }
        $installed_plugins = Plugins_Helper::get_installed_plugin_slugs();
        $activated_plugins = Plugins_Helper::get_active_plugin_slugs();
        $extension->is_installed = in_array(explode(':', (string) $extension->key)[0], $installed_plugins, true);
        $extension->is_activated = in_array(explode(':', (string) $extension->key)[0], $activated_plugins, true);
        return $extension;
    }
    /**
     * Evaluates the specs and returns the bundles with visible extensions.
     *
     * @param array $specs extensions spec array.
     * @param array $allowed_bundles Optional array of allowed bundles to be returned.
     * @return array The bundles and errors.
     */
    public static function evaluate_bundles($specs, $allowed_bundles = []): array
    {
        $bundles = [];
        $evaluate_order = new Evaluate_Overrides();
        $context = [];
        foreach ($specs as $spec) {
            $spec = (object) $spec;
            $bundle = (array) $spec;
            $bundle['plugins'] = [];
            if (!empty($allowed_bundles) && !in_array($spec->key, $allowed_bundles, true)) {
                continue;
            }
            $errors = [];
            foreach ($spec->plugins as $plugin) {
                try {
                    $extension = self::evaluate((object) $plugin);
                    if (!property_exists($extension, 'is_visible') || $extension->is_visible) {
                        $bundle['plugins'][] = $extension;
                    }
                } catch (\Throwable $e) {
                    $errors[] = $e;
                }
            }
            $context['plugins'] = $bundle['plugins'];
            $bundle['plugins'] = $evaluate_order->evaluate($bundle['plugins'], $context);
            $bundles[] = $bundle;
        }
        return ['bundles' => $bundles, 'errors' => $errors];
    }
}