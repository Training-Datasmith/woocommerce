<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\ResourceStorages\ResourceStorage;

/**
 * Class ResourceStorages
 */
class ResourceStorages
{
    /**
     * Storage collection.
     *
     * @var ResourceStorages[]
     */
    protected array $storages = [];

    /**
     * Add a downloader.
     *
     * @param ResourceStorage $downloader The downloader to add.
     */
    public function add_storage(ResourceStorage $downloader): void
    {
        $supported_resource = $downloader->get_supported_resource();
        if (! isset($this->storages[ $supported_resource ])) {
            $this->storages[ $supported_resource ] = [];
        }
        $this->storages[ $supported_resource ][] = $downloader;
    }

    /**
     * Check if the resource is supported.
     *
     * @param string $resource_type The resource type to check.
     */
    public function is_supported_resource($resource_type): bool
    {
        return isset($this->storages[ $resource_type ]);
    }

    /**
     * Download the resource.
     *
     * @param string $slug The slug of the resource to download.
     * @param string $resource_type The resource type to download.
     *
     * @return false|string
     */
    public function download($slug, $resource_type)
    {
        if (! isset($this->storages[ $resource_type ])) {
            return false;
        }
        $storages = $this->storages[ $resource_type ];
        foreach ($storages as $storage) {
            $found = $storage->download($slug);
            if ($found) {
                return $found;
            }
        }

        return false;
    }
}
