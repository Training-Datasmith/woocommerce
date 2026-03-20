<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email;

use Automattic\Woo_Commerce\Internal\Register_Hooks_Interface;
/**
 * Helper class for syncing email styles with theme styles.
 *
 * @internal Just for internal use.
 */
class Email_Style_Sync implements Register_Hooks_Interface
{
    /**
     * Option name for auto-sync setting.
     */
    public const AUTO_SYNC_OPTION = 'woocommerce_email_auto_sync_with_theme';
    /**
     * Flag to prevent recursive syncing.
     */
    private bool $is_syncing = false;
    /**
     * Register hooks and filters.
     */
    public function register(): void
    {
        // Hook into theme change events.
        add_action('after_switch_theme', $this->sync_email_styles_with_theme(...));
        add_action('customize_save_after', $this->sync_email_styles_with_theme(...));
        // Hook into theme.json and global styles changes.
        add_action('wp_theme_json_data_updated', $this->sync_email_styles_with_theme(...));
        add_action('rest_after_insert_global_styles', $this->sync_email_styles_with_theme(...));
        add_action('update_option_wp_global_styles', $this->sync_email_styles_with_theme(...));
        add_action('save_post_wp_global_styles', $this->sync_email_styles_with_theme(...));
        // Hook into the theme editor save action.
        add_action('wp_ajax_wp_save_styles', $this->sync_email_styles_with_theme(...), 999);
        // Hook into auto-sync option update to trigger sync when enabled.
        add_action('update_option_' . self::AUTO_SYNC_OPTION, $this->maybe_sync_on_option_update(...), 10, 3);
    }
    /**
     * Trigger sync when auto-sync option is enabled.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $new_value The new option value.
     * @param string $option    The option name.
     */
    public function maybe_sync_on_option_update($old_value, $new_value, $option): void
    {
        if ('yes' === $new_value && 'yes' !== $old_value) {
            // Force sync regardless of current auto-sync setting since we know it's being enabled.
            $this->is_syncing = true;
            try {
                $this->update_email_colors();
            } finally {
                $this->is_syncing = false;
            }
        }
    }
    /**
     * Check if auto-sync is enabled.
     *
     * @return bool Whether auto-sync is enabled.
     */
    public function is_auto_sync_enabled(): bool
    {
        return 'yes' === get_option(self::AUTO_SYNC_OPTION, 'no');
    }
    /**
     * Set auto-sync enabled status.
     *
     * @param bool $enabled Whether auto-sync should be enabled.
     * @return bool Whether the option was updated.
     */
    public function set_auto_sync(bool $enabled)
    {
        return update_option(self::AUTO_SYNC_OPTION, $enabled ? 'yes' : 'no');
    }
    /**
     * Sync email styles with theme styles if auto-sync is enabled.
     *
     * Uses a flag to prevent recursive calls.
     */
    public function sync_email_styles_with_theme(): void
    {
        if ($this->is_syncing || !$this->is_auto_sync_enabled() || !wp_theme_has_theme_json()) {
            return;
        }
        $this->is_syncing = true;
        try {
            $this->update_email_colors();
        } finally {
            $this->is_syncing = false;
        }
    }
    /**
     * Update email colors from theme colors.
     */
    protected function update_email_colors()
    {
        $colors = Email_Colors::get_default_colors();
        if (empty($colors)) {
            return;
        }
        if (!empty($colors['base'])) {
            update_option('woocommerce_email_base_color', $colors['base']);
        }
        if (!empty($colors['bg'])) {
            update_option('woocommerce_email_background_color', $colors['bg']);
        }
        if (!empty($colors['body_bg'])) {
            update_option('woocommerce_email_body_background_color', $colors['body_bg']);
        }
        if (!empty($colors['body_text'])) {
            update_option('woocommerce_email_text_color', $colors['body_text']);
        }
        if (!empty($colors['footer_text'])) {
            update_option('woocommerce_email_footer_text_color', $colors['footer_text']);
        }
    }
}