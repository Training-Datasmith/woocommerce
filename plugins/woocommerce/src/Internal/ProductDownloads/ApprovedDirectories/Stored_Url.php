<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories;

/**
 * Representation of an approved directory URL, bundling the ID and URL in a single entity.
 */
class StoredUrl
{
    /**
     * Sets up the approved directory rule.
     *
     * @param int    $id      The approved directory ID.
     * @param string $url     The approved directory URL.
     * @param bool   $enabled Indicates if the approved directory rule is enabled.
     */
    public function __construct(
        /**
         * The approved directory ID.
         */
        private readonly int $id,
        /**
         * The approved directory URL.
         */
        private readonly string $url,
        /**
         * If the individual rule is enabled or disabled.
         */
        private readonly bool $enabled
    ) {
    }

    /**
     * Supplies the ID of the approved directory.
     */
    public function get_id(): int
    {
        return $this->id;
    }

    /**
     * Supplies the approved directory URL.
     */
    public function get_url(): string
    {
        return $this->url;
    }

    /**
     * Indicates if this rule is enabled or not (rules can be temporarily disabled).
     */
    public function is_enabled(): bool
    {
        return $this->enabled;
    }
}
