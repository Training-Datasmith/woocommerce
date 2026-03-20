<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\CLI\Migrator\Commands;

use Automattic\Woo_Commerce\Internal\CLI\Migrator\Core\Credential_Manager;
use Automattic\Woo_Commerce\Internal\CLI\Migrator\Core\Platform_Registry;
use WP_CLI;
/**
 * The command for resetting platform credentials.
 */
class Reset_Command
{
    /**
     * The credential manager.
     */
    private Credential_Manager $credential_manager;
    /**
     * The platform registry.
     */
    private Platform_Registry $platform_registry;
    /**
     * Initialize the command with its dependencies.
     *
     * @param CredentialManager $credential_manager The credential manager.
     * @param PlatformRegistry  $platform_registry  The platform registry.
     *
     * @internal
     */
    final public function init(Credential_Manager $credential_manager, Platform_Registry $platform_registry): void
    {
        $this->credential_manager = $credential_manager;
        $this->platform_registry = $platform_registry;
    }
    /**
     * Resets (deletes) the credentials for a given platform.
     *
     * ## OPTIONS
     *
     * [--platform=<platform>]
     * : The platform to reset credentials for. Defaults to 'shopify'.
     *
     * ## EXAMPLES
     *
     *     wp wc migrate reset
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        // Resolve and validate the platform.
        $platform = $this->platform_registry->resolve_platform($assoc_args);
        $platform_display_name = $this->platform_registry->get_platform_display_name($platform);
        if (!$this->credential_manager->has_credentials($platform)) {
            WP_CLI::warning("No credentials found for '{$platform_display_name}' to reset.");
            return;
        }
        $this->credential_manager->delete_credentials($platform);
        WP_CLI::success("Credentials for the '{$platform_display_name}' platform have been cleared.");
    }
}