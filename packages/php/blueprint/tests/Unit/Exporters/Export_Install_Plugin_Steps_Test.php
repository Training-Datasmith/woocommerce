<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;

/**
 * Unit tests for ExportInstallPluginSteps class.
 */
class ExportInstallPluginStepsTest extends TestCase
{
    /**
     * The plugins to test.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $plugins = [
        'plugina/plugina.php' => [
            'Title'           => 'plugina',
            'Name'            => 'plugina',
            'RequiresPlugins' => [ 'pluginc' ],
        ],
        'pluginb/pluginb.php' => [
            'Title'           => 'pluginb',
            'Name'            => 'pluginb',
            'RequiresPlugins' => [ 'plugina' ],
        ],
        'pluginc/pluginc.php' => [
            'Title'           => 'pluginc',
            'Name'            => 'pluginc',
            'RequiresPlugins' => [],
        ],
    ];

    /**
     * Get a mock of the ExportInstallPluginSteps class.
     *
     * @return Mockery\MockInterface
     */
    private function get_mock()
    {
        $mock = Mock(ExportInstallPluginSteps::class)->makePartial();
        $mock->shouldReceive('wp_get_plugins')->andReturn($this->plugins);
        return $mock;
    }

    /**
     * When everything is working as expected.
     *
     * @return void
     */
    public function test_export()
    {
        $mock = $this->get_mock();
        $mock->shouldReceive('wp_plugins_api')->andReturn(
            (object) [
                'download_link' => 'download_link_url',
            ]
        );

        $result = $mock->export();
        $this->assertCount(3, $result);

        $slugs = array_map(fn ($step) => $step->prepare_json_array()['pluginData']['slug'], $result);
        $this->assertContains('plugina', $slugs);
        $this->assertContains('pluginb', $slugs);
        $this->assertContains('pluginc', $slugs);
    }

    /**
     * When a plugin does not have a download link, it should not be included in the export.
     *
     * @return void
     */
    public function test_export_does_not_include_plugins_with_unknown_download_link()
    {
        $mock = $this->get_mock();

        // Return an empty object for the plugina.
        $mock->shouldReceive('wp_plugins_api')->withArgs(
            function ($method, $args) {
                if ('plugin_information' === $method && 'plugina' === $args['slug']) {
                    return true;
                }
                return false;
            }
        )->andReturn((object) []);

        $mock->shouldReceive('wp_plugins_api')
            ->andReturn(
                (object) [
                    'download_link' => 'download_link_url',
                ]
            );

        $result = $mock->export();
        $this->assertCount(2, $result);
    }
    /**
     * Dependencies must be installed first before installing the plugins that
     * require them. Make sure we return the dependencies first.
     */
    public function test_it_should_return_dependencies_first()
    {
        $instance = new ExportInstallPluginSteps();
        $plugins  = $instance->sort_plugins_by_dep($this->plugins);

        $this->assertEquals(
            [
                'pluginc/pluginc.php' => [
                    'Title'           => 'pluginc',
                    'Name'            => 'pluginc',
                    'RequiresPlugins' => [],
                ],
                'plugina/plugina.php' => [
                    'Title'           => 'plugina',
                    'Name'            => 'plugina',
                    'RequiresPlugins' => [ 'pluginc' ],
                ],
                'pluginb/pluginb.php' => [
                    'Title'           => 'pluginb',
                    'Name'            => 'pluginb',
                    'RequiresPlugins' => [ 'plugina' ],
                ],
            ],
            $plugins
        );
    }
}
