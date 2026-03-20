<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Domain;

use Automattic\Woo_Commerce\Blocks\Domain\Services\Feature_Gating;
use Automattic\Woo_Commerce\Blocks\Options;
/**
 * Main package class.
 *
 * Returns information about the package and handles init.
 *
 * @since 2.5.0
 */
class Package
{
    /**
     * Holds locally the plugin_dir_url to avoid recomputing it.
     *
     * @var string
     */
    private $plugin_dir_url;
    /**
     * Holds the feature gating class instance.
     */
    private ?\Automattic\Woo_Commerce\Blocks\Domain\Services\Feature_Gating $feature_gating = null;
    /**
     * Constructor
     *
     * @param string        $version        Version of the plugin.
     * @param string $path Path to the main plugin file.
     * @param FeatureGating $deprecated     Deprecated Feature gating class.
     */
    public function __construct(
        /**
         * Holds the current version of the blocks plugin.
         */
        private $version,
        /**
         * Holds the main path to the blocks plugin directory.
         */
        private $path,
        $deprecated = null
    )
    {
        if (null !== $deprecated) {
            wc_deprecated_argument('FeatureGating', '9.6', 'FeatureGating class is deprecated, please use wp_get_environment_type() instead.');
            $this->feature_gating = new Feature_Gating();
        }
    }
    /**
     * Returns the version of WooCommerce Blocks.
     *
     * Note: since Blocks was merged into WooCommerce Core, the version of
     * WC Blocks doesn't update anymore. Use
     * `Constants::get_constant( 'WC_VERSION' )` when possible to get the
     * WooCommerce Core version.
     *
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }
    /**
     * Returns the version of WooCommerce Blocks stored in the database.
     *
     * @return string
     */
    public function get_version_stored_on_db()
    {
        return get_option(Options::WC_BLOCK_VERSION, '');
    }
    /**
     * Sets the version of WooCommerce Blocks in the database.
     * This is useful during the first installation or after the upgrade process.
     */
    public function set_version_stored_on_db(): void
    {
        update_option(Options::WC_BLOCK_VERSION, $this->get_version());
    }
    /**
     * Returns the path to the plugin directory.
     *
     * @param string $relative_path  If provided, the relative path will be
     *                               appended to the plugin path.
     */
    public function get_path(string $relative_path = ''): string
    {
        return trailingslashit($this->path) . $relative_path;
    }
    /**
     * Returns the url to the blocks plugin directory.
     *
     * @param string $relative_url If provided, the relative url will be
     *                             appended to the plugin url.
     */
    public function get_url(string $relative_url = ''): string
    {
        if (!$this->plugin_dir_url) {
            // Append index.php so WP does not return the parent directory.
            $this->plugin_dir_url = plugin_dir_url($this->path . '/index.php');
        }
        return $this->plugin_dir_url . $relative_url;
    }
    /**
     * Returns an instance of the FeatureGating class.
     *
     * @return FeatureGating
     */
    public function feature()
    {
        return $this->feature_gating;
    }
}