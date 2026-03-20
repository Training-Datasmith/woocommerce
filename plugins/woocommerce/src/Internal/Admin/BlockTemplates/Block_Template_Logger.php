<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Block_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Container_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Template_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Container_Interface;
/**
 * Logger for block template modifications.
 */
class Block_Template_Logger
{
    public const BLOCK_ADDED = 'block_added';
    public const BLOCK_REMOVED = 'block_removed';
    public const BLOCK_MODIFIED = 'block_modified';
    public const BLOCK_ADDED_TO_DETACHED_CONTAINER = 'block_added_to_detached_container';
    public const HIDE_CONDITION_ADDED = 'hide_condition_added';
    public const HIDE_CONDITION_REMOVED = 'hide_condition_removed';
    public const HIDE_CONDITION_ADDED_TO_DETACHED_BLOCK = 'hide_condition_added_to_detached_block';
    public const ERROR_AFTER_BLOCK_ADDED = 'error_after_block_added';
    public const ERROR_AFTER_BLOCK_REMOVED = 'error_after_block_removed';
    public const LOG_HASH_TRANSIENT_BASE_NAME = 'wc_block_template_events_log_hash_';
    /**
     * Event types.
     *
     * @var array
     */
    public static $event_types = [self::BLOCK_ADDED => ['level' => \WC_Log_Levels::DEBUG, 'message' => 'Block added to template.'], self::BLOCK_REMOVED => ['level' => \WC_Log_Levels::NOTICE, 'message' => 'Block removed from template.'], self::BLOCK_MODIFIED => ['level' => \WC_Log_Levels::NOTICE, 'message' => 'Block modified in template.'], self::BLOCK_ADDED_TO_DETACHED_CONTAINER => ['level' => \WC_Log_Levels::WARNING, 'message' => 'Block added to detached container. Block will not be included in the template, since the container will not be included in the template.'], self::HIDE_CONDITION_ADDED => ['level' => \WC_Log_Levels::NOTICE, 'message' => 'Hide condition added to block.'], self::HIDE_CONDITION_REMOVED => ['level' => \WC_Log_Levels::NOTICE, 'message' => 'Hide condition removed from block.'], self::HIDE_CONDITION_ADDED_TO_DETACHED_BLOCK => ['level' => \WC_Log_Levels::WARNING, 'message' => 'Hide condition added to detached block. Block will not be included in the template, so the hide condition is not needed.'], self::ERROR_AFTER_BLOCK_ADDED => ['level' => \WC_Log_Levels::WARNING, 'message' => 'Error after block added to template.'], self::ERROR_AFTER_BLOCK_REMOVED => ['level' => \WC_Log_Levels::WARNING, 'message' => 'Error after block removed from template.']];
    /**
     * Singleton instance.
     *
     * @var BlockTemplateLogger
     */
    protected static $instance;
    /**
     * Logger instance.
     *
     * @var \WC_Logger
     */
    protected object $logger;
    /**
     * All template events.
     */
    private array $all_template_events = [];
    /**
     * Templates.
     */
    private array $templates = [];
    /**
     * Threshold severity.
     *
     * @var int
     */
    private $threshold_severity;
    /**
     * Get the singleton instance.
     */
    public static function get_instance(): Block_Template_Logger
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->logger = wc_get_logger();
        $threshold = get_option('woocommerce_block_template_logging_threshold', \WC_Log_Levels::WARNING);
        if (!\WC_Log_Levels::is_valid_level($threshold)) {
            $threshold = \WC_Log_Levels::INFO;
        }
        $this->threshold_severity = \WC_Log_Levels::get_level_severity($threshold);
        add_action('woocommerce_block_template_after_add_block', function (Block_Interface $block): void {
            $is_detached = method_exists($block->get_parent(), 'is_detached') && $block->get_parent()->is_detached();
            $this->log($is_detached ? $this::BLOCK_ADDED_TO_DETACHED_CONTAINER : $this::BLOCK_ADDED, $block);
        }, 0);
        add_action('woocommerce_block_template_after_remove_block', function (Block_Interface $block): void {
            $this->log($this::BLOCK_REMOVED, $block);
        }, 0);
        add_action('woocommerce_block_template_after_add_hide_condition', function (Block_Interface $block): void {
            $this->log($block->is_detached() ? $this::HIDE_CONDITION_ADDED_TO_DETACHED_BLOCK : $this::HIDE_CONDITION_ADDED, $block);
        }, 0);
        add_action('woocommerce_block_template_after_remove_hide_condition', function (Block_Interface $block): void {
            $this->log($this::HIDE_CONDITION_REMOVED, $block);
        }, 0);
        add_action('woocommerce_block_template_after_add_block_error', function (Block_Interface $block, string $action, \Exception $exception): void {
            $this->log($this::ERROR_AFTER_BLOCK_ADDED, $block, ['action' => $action, 'exception' => $exception]);
        }, 0, 3);
        add_action('woocommerce_block_template_after_remove_block_error', function (Block_Interface $block, string $action, \Exception $exception): void {
            $this->log($this::ERROR_AFTER_BLOCK_REMOVED, $block, ['action' => $action, 'exception' => $exception]);
        }, 0, 3);
    }
    /**
     * Get all template events for a given template as a JSON like array.
     *
     * @param string $template_id Template ID.
     */
    public function template_events_to_json(string $template_id): array
    {
        if (!isset($this->all_template_events[$template_id])) {
            return [];
        }
        $template_events = $this->all_template_events[$template_id];
        return $this->to_json($template_events);
    }
    /**
     * Get all template events as a JSON like array.
     *
     * @param array $template_events Template events.
     *
     * @return array The JSON.
     */
    private function to_json(array $template_events): array
    {
        $json = [];
        foreach ($template_events as $template_event) {
            $container = $template_event['container'];
            $block = $template_event['block'];
            $json[] = ['level' => $template_event['level'], 'event_type' => $template_event['event_type'], 'message' => $template_event['message'], 'container' => $container instanceof Block_Interface ? ['id' => $container->get_id(), 'name' => $container->get_name()] : null, 'block' => ['id' => $block->get_id(), 'name' => $block->get_name()], 'additional_info' => $this->format_info($template_event['additional_info'])];
        }
        return $json;
    }
    /**
     * Log all template events for a given template to the log file.
     *
     * @param string $template_id Template ID.
     */
    public function log_template_events_to_file(string $template_id): void
    {
        if (!isset($this->all_template_events[$template_id])) {
            return;
        }
        $template_events = $this->all_template_events[$template_id];
        $hash = $this->generate_template_events_hash($template_events);
        if (!$this->has_template_events_changed($template_id, $hash)) {
            // Nothing has changed since the last time this was logged,
            // so don't log it again.
            return;
        }
        $this->set_template_events_log_hash($template_id, $hash);
        $template = $this->templates[$template_id];
        foreach ($template_events as $template_event) {
            $info = array_merge(['template' => $template, 'container' => $template_event['container'], 'block' => $template_event['block']], $template_event['additional_info']);
            $message = $this->format_message($template_event['message'], $info);
            $this->logger->log($template_event['level'], $message, ['source' => 'block_template']);
        }
    }
    /**
     * Has the template events changed since the last time they were logged?
     *
     * @param string $template_id Template ID.
     * @param string $events_hash Events hash.
     */
    private function has_template_events_changed(string $template_id, string $events_hash): bool
    {
        $previous_hash = get_transient(self::LOG_HASH_TRANSIENT_BASE_NAME . $template_id);
        return $previous_hash !== $events_hash;
    }
    /**
     * Generate a hash for a given set of template events.
     *
     * @param array $template_events Template events.
     */
    private function generate_template_events_hash(array $template_events): string
    {
        return md5(wp_json_encode($this->to_json($template_events)));
    }
    /**
     * Set the template events hash for a given template.
     *
     * @param string $template_id Template ID.
     * @param string $hash        Hash of template events.
     */
    private function set_template_events_log_hash(string $template_id, string $hash): void
    {
        set_transient(self::LOG_HASH_TRANSIENT_BASE_NAME . $template_id, $hash);
    }
    /**
     * Log an event.
     *
     * @param string         $event_type      Event type.
     * @param BlockInterface $block           Block.
     * @param array          $additional_info Additional info.
     */
    private function log(string $event_type, Block_Interface $block, array $additional_info = []): void
    {
        if (!isset(self::$event_types[$event_type])) {
            /* translators: 1: WC_Logger::log 2: level */
            wc_doing_it_wrong(__METHOD__, sprintf(__('%1$s was called with an invalid event type "%2$s".', 'woocommerce'), '<code>BlockTemplateLogger::log</code>', $event_type), '8.4');
        }
        $event_type_info = isset(self::$event_types[$event_type]) ? array_merge(self::$event_types[$event_type], ['event_type' => $event_type]) : ['level' => \WC_Log_Levels::ERROR, 'event_type' => $event_type, 'message' => 'Unknown error.'];
        if (!$this->should_handle($event_type_info['level'])) {
            return;
        }
        $template = $block->get_root_template();
        $container = $block->get_parent();
        $this->add_template_event($event_type_info, $template, $container, $block, $additional_info);
    }
    /**
     * Should the logger handle a given level?
     *
     * @param int $level Level to check.
     */
    private function should_handle($level): bool
    {
        return $this->threshold_severity <= \WC_Log_Levels::get_level_severity($level);
    }
    /**
     * Add a template event.
     *
     * @param array                  $event_type_info Event type info.
     * @param BlockTemplateInterface $template        Template.
     * @param ContainerInterface     $container       Container.
     * @param BlockInterface         $block           Block.
     * @param array                  $additional_info Additional info.
     */
    private function add_template_event(array $event_type_info, Block_Template_Interface $template, Container_Interface $container, Block_Interface $block, array $additional_info = []): void
    {
        $template_id = $template->get_id();
        if (!isset($this->all_template_events[$template_id])) {
            $this->all_template_events[$template_id] = [];
            $this->templates[$template_id] = $template;
        }
        $template_events =& $this->all_template_events[$template_id];
        $template_events[] = ['level' => $event_type_info['level'], 'event_type' => $event_type_info['event_type'], 'message' => $event_type_info['message'], 'container' => $container, 'block' => $block, 'additional_info' => $additional_info];
    }
    /**
     * Format a message for logging.
     *
     * @param string $message Message to log.
     * @param array  $info    Additional info to log.
     */
    private function format_message(string $message, array $info = []): string
    {
        return sprintf(
            "%s\n%s",
            $message,
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            print_r($this->format_info($info), true)
        );
    }
    /**
     * Format info for logging.
     *
     * @param array $info Info to log.
     */
    private function format_info(array $info): array
    {
        $formatted_info = $info;
        if (isset($info['exception']) && $info['exception'] instanceof \Exception) {
            $formatted_info['exception'] = $this->format_exception($info['exception']);
        }
        if (isset($info['container'])) {
            if ($info['container'] instanceof Block_Container_Interface) {
                $formatted_info['container'] = $this->format_block($info['container']);
            } elseif ($info['container'] instanceof Block_Template_Interface) {
                $formatted_info['container'] = $this->format_template($info['container']);
            } elseif ($info['container'] instanceof Block_Interface) {
                $formatted_info['container'] = $this->format_block($info['container']);
            }
        }
        if (isset($info['block']) && $info['block'] instanceof Block_Interface) {
            $formatted_info['block'] = $this->format_block($info['block']);
        }
        if (isset($info['template']) && $info['template'] instanceof Block_Template_Interface) {
            $formatted_info['template'] = $this->format_template($info['template']);
        }
        return $formatted_info;
    }
    /**
     * Format an exception for logging.
     *
     * @param \Exception $exception Exception to format.
     */
    private function format_exception(\Exception $exception): array
    {
        return [
            'message' => $exception->get_message(),
            'source' => "{$exception->get_file()}: {$exception->get_line()}",
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            'trace' => print_r($this->format_exception_trace($exception->get_trace()), true),
        ];
    }
    /**
     * Format an exception trace for logging.
     *
     * @param array $trace Exception trace to format.
     */
    private function format_exception_trace(array $trace): array
    {
        $formatted_trace = [];
        foreach ($trace as $source) {
            $formatted_trace[] = "{$source['file']}: {$source['line']}";
        }
        return $formatted_trace;
    }
    /**
     * Format a block template for logging.
     *
     * @param BlockTemplateInterface $template Template to format.
     */
    private function format_template(Block_Template_Interface $template): string
    {
        return "{$template->get_id()} (area: {$template->get_area()})";
    }
    /**
     * Format a block for logging.
     *
     * @param BlockInterface $block Block to format.
     */
    private function format_block(Block_Interface $block): string
    {
        return "{$block->get_id()} (name: {$block->get_name()})";
    }
}