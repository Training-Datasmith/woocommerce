<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Admin\Features\Features;
use WP_Upgrader;

/**
 * PTKPatterns class.
 *
 * @internal
 */
class PTKPatternsStore
{
    public const OPTION_NAME = 'ptk_patterns';

    /**
     * Hook and action name used to trigger fetching patterns.
     */
    public const FETCH_PATTERNS_ACTION = 'fetch_patterns';

    public const CATEGORY_MAPPING = [
        'testimonials' => 'reviews',
    ];

    /**
     * Constructor for the class.
     *
     * @param PTKClient $ptk_client An instance of PatternsToolkit.
     */
    public function __construct(/**
     * PatternsToolkit instance.
     */
        private readonly PTKClient $ptk_client
    ) {
        if (Features::is_enabled('pattern-toolkit-full-composability')) {
            // We want to flush the cached patterns when:
            // - The WooCommerce plugin is deactivated.
            // - The `woocommerce_allow_tracking` option is disabled.
            //
            // We also want to re-fetch the patterns and update the cache when:
            // - The `woocommerce_allow_tracking` option changes to enabled.
            // - The WooCommerce plugin is activated (if `woocommerce_allow_tracking` is enabled).
            // - The WooCommerce plugin is updated.

            add_action('woocommerce_activated_plugin', $this->flush_or_fetch_patterns(...), 10, 2);
            add_action('update_option_woocommerce_allow_tracking', $this->flush_or_fetch_patterns(...), 10, 2);
            add_action('deactivated_plugin', $this->flush_cached_patterns(...), 10, 2);
            add_action('upgrader_process_complete', $this->fetch_patterns_on_plugin_update(...), 10, 2);
            add_action('action_scheduler_ensure_recurring_actions', $this->ensure_recurring_fetch_patterns_if_enabled(...));

            // This is the scheduled action that takes care of flushing and re-fetching the patterns from the PTK API.
            add_action(self::FETCH_PATTERNS_ACTION, $this->fetch_patterns(...));
        }
    }

    /**
     * Resets the cached patterns when the `woocommerce_allow_tracking` option is disabled.
     * Resets and fetch the patterns from the PTK when it is enabled (if the scheduler
     * is initialized, it's done asynchronously via a scheduled action).
     */
    public function flush_or_fetch_patterns(): void
    {
        if ($this->allowed_tracking_is_enabled()) {
            $this->schedule_fetch_patterns();
            return;
        }

        $this->flush_cached_patterns();
    }

    /**
     * Schedule an async action to fetch the PTK patterns when the scheduler is initialized.
     */
    private function schedule_fetch_patterns(): void
    {
        if (did_action('action_scheduler_init')) {
            $this->schedule_action_if_not_pending(self::FETCH_PATTERNS_ACTION);
        } else {
            add_action(
                'action_scheduler_init',
                function (): void {
                    $this->schedule_action_if_not_pending(self::FETCH_PATTERNS_ACTION);
                }
            );
        }
    }

    /**
     * Ensure a recurring fetch patterns action is scheduled.
     * This is called by the `action_scheduler_ensure_recurring_actions` hook.
     */
    public function ensure_recurring_fetch_patterns_if_enabled(): void
    {
        if (! $this->allowed_tracking_is_enabled()) {
            return;
        }

        $this->schedule_action_if_not_pending(self::FETCH_PATTERNS_ACTION);
    }

    /**
     * Schedule an action if it's not already pending.
     *
     * @param string $action The action name to schedule.
     */
    private function schedule_action_if_not_pending(string $action): void
    {
        if (as_has_scheduled_action($action, [], 'woocommerce')) {
            return;
        }

        as_schedule_recurring_action(time(), DAY_IN_SECONDS, $action, [], 'woocommerce');
    }

    /**
     * Get the patterns from the Patterns Toolkit cache.
     *
     * @return array
     */
    public function get_patterns()
    {
        $patterns = get_option(self::OPTION_NAME);

        // If the current data doesn't exist or is invalid, schedule fetching the patterns from the PTK.
        if (false === $patterns || ! $this->ptk_client->is_valid_schema($patterns)) {
            $this->schedule_fetch_patterns();
            return [];
        }

        return $patterns;
    }

    /**
     * Filter the patterns that have external dependencies.
     *
     * @param array $patterns The patterns to filter.
     */
    private function filter_patterns(array $patterns): array
    {
        return array_values(
            array_filter(
                $patterns,
                function (array $pattern): bool {
                    if (! isset($pattern['ID'])) {
                        return true;
                    }

                    if (isset($pattern['post_type']) && 'wp_block' !== $pattern['post_type']) {
                        return false;
                    }

                    if ($this->has_external_dependencies($pattern)) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    /**
     * Re-fetch the patterns when the WooCommerce plugin is updated.
     *
     * @param WP_Upgrader $upgrader_object WP_Upgrader instance.
     * @param array       $options Array of bulk item update data.
     */
    public function fetch_patterns_on_plugin_update($upgrader_object, array $options): void
    {
        if ('update' === $options['action'] && 'plugin' === $options['type'] && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if (str_contains((string) $plugin, 'woocommerce.php')) {
                    $this->schedule_fetch_patterns();
                }
            }
        }
    }

    /**
     * Reset the cached patterns to fetch them again from the PTK.
     *
     * @since 10.4.1 Unscheduling is deferred if Action Scheduler hasn't initialized yet.
     */
    public function flush_cached_patterns(): void
    {
        delete_option(self::OPTION_NAME);

        if (! function_exists('as_unschedule_all_actions')) {
            return;
        }

        // Unschedule any existing fetch_patterns actions.
        // Defer unscheduling until Action Scheduler is ready to avoid errors during early initialization.
        if (did_action('action_scheduler_init')) {
            as_unschedule_all_actions(self::FETCH_PATTERNS_ACTION, [], 'woocommerce');
        } else {
            add_action(
                'action_scheduler_init',
                function (): void {
                    as_unschedule_all_actions(self::FETCH_PATTERNS_ACTION, [], 'woocommerce');
                }
            );
        }
    }

    /**
     * Reset the cached patterns and fetch them again from the PTK API.
     */
    public function fetch_patterns(): void
    {
        if (! $this->allowed_tracking_is_enabled()) {
            return;
        }

        $patterns = $this->ptk_client->fetch_patterns(
            [
                // This is the site where the patterns are stored. Despite the 'wpcomstaging.com' domain suggesting a staging environment, this URL points to the production environment where stable versions of the patterns are maintained.
                'site'       => 'wooblockpatterns.wpcomstaging.com',
                'categories' => [
                    '_woo_intro',
                    '_woo_featured_selling',
                    '_woo_about',
                    '_woo_reviews',
                    '_woo_social_media',
                    '_woo_woocommerce',
                    '_dotcom_imported_intro',
                    '_dotcom_imported_about',
                    '_dotcom_imported_services',
                    '_dotcom_imported_reviews',
                ],
            ]
        );

        if (is_wp_error($patterns)) {
            wc_get_logger()->warning(
                sprintf(
                    // translators: %s is a generated error message.
                    __('Failed to get WooCommerce patterns from the PTK: "%s"', 'woocommerce'),
                    $patterns->get_error_message()
                ),
            );
            return;
        }

        $patterns = $this->filter_patterns($patterns);
        $patterns = $this->map_categories($patterns);

        update_option(self::OPTION_NAME, $patterns, false);
    }

    /**
     * Check if the user allowed tracking.
     */
    private function allowed_tracking_is_enabled(): bool
    {
        return 'yes' === get_option('woocommerce_allow_tracking');
    }

    /**
     * Change the categories of the patterns to match the ones used in the CYS flow
     *
     * @param array $patterns The patterns to map categories for.
     * @return array The patterns with the categories mapped.
     */
    private function map_categories(array $patterns): array
    {
        return array_map(
            function (array $pattern): array {
                if (isset($pattern['categories'])) {
                    foreach ($pattern['categories'] as $key => $category) {
                        if (isset($category['slug']) && isset(self::CATEGORY_MAPPING[ $key ])) {
                            $new_category = self::CATEGORY_MAPPING[ $key ];
                            unset($pattern['categories'][ $key ]);
                            $pattern['categories'][ $new_category ]['slug']  = $new_category;
                            $pattern['categories'][ $new_category ]['title'] = ucfirst($new_category);
                        }
                    }
                }

                return $pattern;
            },
            $patterns
        );
    }

    /**
     * Check if the pattern has external dependencies.
     *
     * @param array $pattern The pattern to check.
     */
    private function has_external_dependencies(array $pattern): bool
    {
        if (! isset($pattern['dependencies']) || ! is_array($pattern['dependencies'])) {
            return false;
        }

        foreach ($pattern['dependencies'] as $dependency) {
            if ('woocommerce' !== $dependency) {
                return true;
            }
        }

        return false;
    }
}
