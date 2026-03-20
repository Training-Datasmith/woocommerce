<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

/**
 * Class OrgThemeResourceStorage
 */
class OrgThemeResourceStorage extends OrgPluginResourceStorage
{
    /**
     * Get the download link.
     *
     * @param string $slug The slug of the theme to be downloaded.
     *
     * @return string|null The download link.
     */
    protected function get_download_link($slug): ?string
    {
        $info = $this->wp_themes_api(
            'theme_information',
            [
                'slug'   => $slug,
                'fields' => [
                    'sections' => false,
                ],
            ]
        );

        if (is_wp_error($info)) {
            return null;
        }

        return $info->download_link ?? null;
    }

    /**
     * Get the supported resource.
     *
     * @return string The supported resource.
     */
    public function get_supported_resource(): string
    {
        return 'wordpress.org/themes';
    }
}
