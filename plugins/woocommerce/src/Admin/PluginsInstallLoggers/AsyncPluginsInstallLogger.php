<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Plugins_Install_Loggers;

/**
 * A logger to log plugin installation progress in real time to an option.
 */
class Async_Plugins_Install_Logger implements Plugins_Install_Logger
{
    /**
     * Constructor.
     *
     * @param string $option_name option name.
     */
    public function __construct(
        /**
         * Variable to store logs.
         */
        private readonly string $option_name
    )
    {
        add_option($this->option_name, ['created_time' => time(), 'status' => 'pending', 'plugins' => []], '', 'no');
        // Set status as failed in case we run out of execution time.
        register_shutdown_function(function (): void {
            $error = error_get_last();
            if (isset($error['type']) && E_ERROR === $error['type']) {
                $option = $this->get();
                $option['status'] = 'failed';
                $this->update($option);
            }
        });
    }
    /**
     * Update the option.
     *
     * @param array $data New data.
     *
     * @return bool
     */
    private function update(array $data)
    {
        return update_option($this->option_name, $data);
    }
    /**
     * Retrieve the option.
     *
     * @return false|mixed|void
     */
    private function get()
    {
        return get_option($this->option_name);
    }
    /**
     * Add requested plugin.
     *
     * @param string $plugin_name plugin name.
     */
    public function install_requested(string $plugin_name): void
    {
        $option = $this->get();
        if (!isset($option['plugins'][$plugin_name])) {
            $option['plugins'][$plugin_name] = ['status' => 'installing', 'errors' => [], 'install_duration' => 0];
        }
        $this->update($option);
    }
    /**
     * Add installed plugin.
     *
     * @param string $plugin_name plugin name.
     * @param int    $duration time took to install plugin.
     */
    public function installed(string $plugin_name, int $duration): void
    {
        $option = $this->get();
        $option['plugins'][$plugin_name]['status'] = 'installed';
        $option['plugins'][$plugin_name]['install_duration'] = $duration;
        $this->update($option);
    }
    /**
     * Change status to activated.
     *
     * @param string $plugin_name plugin name.
     */
    public function activated(string $plugin_name): void
    {
        $option = $this->get();
        $option['plugins'][$plugin_name]['status'] = 'activated';
        $this->update($option);
    }
    /**
     * Add an error.
     *
     * @param string      $plugin_name plugin name.
     * @param string|null $error_message error message.
     */
    public function add_error(string $plugin_name, ?string $error_message = null): void
    {
        $option = $this->get();
        $option['plugins'][$plugin_name]['errors'][] = $error_message;
        $option['plugins'][$plugin_name]['status'] = 'failed';
        $option['status'] = 'failed';
        wc_admin_record_tracks_event('coreprofiler_store_extension_installed_and_activated', ['success' => false, 'extension' => $this->get_plugin_track_key($plugin_name), 'error_message' => $error_message]);
        $this->update($option);
    }
    /**
     * Record completed_time.
     *
     * @param array $data return data from install_plugins().
     */
    public function complete($data = []): void
    {
        $option = $this->get();
        $option['complete_time'] = time();
        $option['status'] = 'complete';
        $this->track($data);
        $this->update($option);
    }
    private function get_plugin_track_key($id): string
    {
        $slug = explode(':', (string) $id)[0];
        return preg_match('/^woocommerce(-|_)payments$/', $slug) ? 'wcpay' : explode(':', str_replace('-', '_', $slug))[0];
    }
    /**
     * Returns time frame for a given time in milliseconds.
     *
     * @param int $timeInMs - time in milliseconds
     *
     * @return string - Time frame.
     */
    public function get_timeframe($time_in_ms)
    {
        $time_frames = [['name' => '0-2s', 'max' => 2], ['name' => '2-5s', 'max' => 5], ['name' => '5-10s', 'max' => 10], ['name' => '10-15s', 'max' => 15], ['name' => '15-20s', 'max' => 20], ['name' => '20-30s', 'max' => 30], ['name' => '30-60s', 'max' => 60], ['name' => '>60s']];
        foreach ($time_frames as $time_frame) {
            if (!isset($time_frame['max'])) {
                return $time_frame['name'];
            }
            if ($time_in_ms < $time_frame['max'] * 1000) {
                return $time_frame['name'];
            }
        }
    }
    private function track(array $data): void
    {
        $track_data = ['success' => true, 'installed_extensions' => array_map(fn($extension) => $this->get_plugin_track_key($extension), $data['installed']), 'total_time' => $this->get_timeframe((time() - $data['start_time']) * 1000)];
        foreach ($data['installed'] as $plugin) {
            if (!isset($data['time'][$plugin])) {
                continue;
            }
            $plugin_track_key = $this->get_plugin_track_key($plugin);
            $install_time = $this->get_timeframe($data['time'][$plugin]);
            $track_data['install_time_' . $plugin_track_key] = $install_time;
            wc_admin_record_tracks_event('coreprofiler_store_extension_installed_and_activated', ['success' => true, 'extension' => $plugin_track_key, 'install_time' => $install_time]);
        }
        wc_admin_record_tracks_event('coreprofiler_store_extensions_installed_and_activated', $track_data);
    }
}