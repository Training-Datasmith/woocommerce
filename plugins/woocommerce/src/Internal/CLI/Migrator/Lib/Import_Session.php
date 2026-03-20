<?php

declare (strict_types=1);
/**
 * !! Do not apply Woo-specific changes to this class !!
 *
 * This class is a part of the WordPress/php-toolkit project and is currently
 * duplicated between WordPress/php-toolkit and woocommerce/woocommerce:
 * * https://github.com/WordPress/php-toolkit/blob/trunk/components/DataLiberation/Importer/ImportSession.php
 * * https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Lib/ImportSession.php
 *
 * Apply all changes in both projects until Woo consumes php-toolkit as a
 * composer dependency. Generic changes belong to this class. Anything
 * Woo-specific should be implemented as an extension point.
 *
 * MODIFICATION: Made this class standalone by replacing external StreamImporter
 * and AttachmentDownloaderEvent dependencies with internal constants to eliminate
 * external imports and make the class fully self-contained.
 */
namespace Automattic\Woo_Commerce\Internal\CLI\Migrator\Lib;

use function get_all_post_meta_flat;
use function is_wp_error;
use WP_Query;
/**
 * Manages import session data in the WordPress database.
 *
 * Each import session is stored as a post of type 'import_session'.
 * Progress, stage, and other metadata are stored as post meta.
 */
class Import_Session
{
    public const POST_TYPE = 'import_session';
    // Import stage constants - replaces StreamImporter dependencies
    public const STAGE_INITIAL = 'initial';
    public const STAGE_FINISHED = 'finished';
    // Import stages in processing order
    public const STAGES_IN_ORDER = [self::STAGE_INITIAL, 'indexing', 'preparing', 'importing', 'finalizing', self::STAGE_FINISHED];
    // Event type constants - replaces AttachmentDownloaderEvent dependencies
    public const EVENT_SUCCESS = 'success';
    public const EVENT_ALREADY_EXISTS = 'already_exists';
    public const EVENT_FAILURE = 'failure';
    /**
     * @TODO: Make it extendable
     * @TODO: Reuse the same entities list as WP_Stream_Importer
     */
    public const PROGRESS_ENTITIES = ['site_option', 'user', 'category', 'tag', 'term', 'post', 'post_meta', 'comment', 'comment_meta'];
    public const FRONTLOAD_STATUS_AWAITING_DOWNLOAD = 'awaiting_download';
    public const FRONTLOAD_STATUS_IGNORED = 'ignored';
    public const FRONTLOAD_STATUS_ERROR = 'error';
    public const FRONTLOAD_STATUS_SUCCEEDED = 'succeeded';
    private $cached_stage;
    /**
     * Creates a new import session.
     *
     * @param  array  $args  {
     *
     * @type string $data_source The data source (e.g. 'wxr_file', 'wxr_url', 'markdown_zip')
     * @type string $source_url Optional. URL of the source file for remote imports
     * @type int $attachment_id Optional. ID of the uploaded file attachment
     * @type string $file_name Optional. Original name of the uploaded file
     * }
     * @return ImportSession The created ImportSession instance.
     * @throws \Exception If the arguments are invalid.
     */
    public static function create(array $args): self
    {
        // Validate the required arguments for each data source.
        // @TODO: Leave it up to filters to make it extendable.
        switch ($args['data_source']) {
            case 'wxr_file':
                if (empty($args['file_name'])) {
                    throw new \Exception('File name is required for WXR file imports');
                }
                break;
            case 'wxr_url':
                if (empty($args['source_url'])) {
                    throw new \Exception('Source URL is required for remote imports');
                }
                break;
            case 'markdown_zip':
                if (empty($args['file_name'])) {
                    throw new \Exception('File name is required for Markdown ZIP imports');
                }
                break;
            case 'local_directory':
                if (empty($args['file_name'])) {
                    throw new \Exception('Directory path is required for local directory imports');
                }
                break;
        }
        $post_id = wp_insert_post(['post_type' => self::POST_TYPE, 'post_status' => 'publish', 'post_title' => sprintf('Import from %s - %s', $args['data_source'], $args['file_name'] ?? $args['source_url'] ?? 'Unknown source'), 'meta_input' => ['data_source' => $args['data_source'], 'started_at' => time(), 'file_name' => $args['file_name'] ?? null, 'source_url' => $args['source_url'] ?? null, 'attachment_id' => $args['attachment_id'] ?? null]], true);
        if (is_wp_error($post_id)) {
            throw new \Exception('Error creating an import session: ' . $post_id->get_error_message());
        }
        if (!empty($args['attachment_id'])) {
            wp_update_post(['ID' => $post_id, 'post_parent' => $args['attachment_id']]);
        }
        return new self($post_id);
    }
    /**
     * Gets an existing import session by ID.
     *
     * @param  int  $post_id  The import session post ID
     *
     * @return WP_Import_Model|null The import model instance or null if not found
     */
    public static function by_id($post_id): false|\Automattic\Woo_Commerce\Internal\CLI\Migrator\Lib\Import_Session
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return false;
        }
        return new self($post_id);
    }
    /**
     * Gets the most recent active import session.
     *
     * @return WP_Import_Session|null The most recent import or null if none found
     */
    public static function get_active(): false|\Automattic\Woo_Commerce\Internal\CLI\Migrator\Lib\Import_Session
    {
        $posts = get_posts(['post_type' => self::POST_TYPE, 'post_status' => ['publish'], 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => []]);
        if (empty($posts)) {
            return false;
        }
        return new self($posts[0]->ID);
    }
    public function __construct(private $post_id)
    {
    }
    /**
     * Gets the import session ID.
     *
     * @return int The post ID
     */
    public function get_id()
    {
        return $this->post_id;
    }
    public function get_metadata(): array
    {
        $cursor = $this->get_reentrancy_cursor();
        return ['post_id' => $this->post_id, 'cursor' => $cursor ?: null, 'data_source' => get_post_meta($this->post_id, 'data_source', true), 'source_url' => get_post_meta($this->post_id, 'source_url', true), 'attachment_id' => get_post_meta($this->post_id, 'attachment_id', true)];
    }
    public function get_data_source()
    {
        return get_post_meta($this->post_id, 'data_source', true);
    }
    public function get_human_readable_file_reference()
    {
        return match ($this->get_data_source()) {
            'wxr_file', 'markdown_zip' => get_post_meta($this->post_id, 'file_name', true),
            'wxr_url' => get_post_meta($this->post_id, 'source_url', true),
            default => '',
        };
    }
    public function archive(): void
    {
        wp_update_post(['ID' => $this->post_id, 'post_status' => 'archived']);
    }
    /**
     * Gets the current progress information.
     *
     * @return array The progress data
     */
    public function count_imported_entities(): array
    {
        $progress = [];
        foreach (self::PROGRESS_ENTITIES as $entity) {
            $progress[] = ['label' => $entity, 'imported' => (int) get_post_meta($this->post_id, 'imported_' . $entity, true), 'total' => (int) get_post_meta($this->post_id, 'total_' . $entity, true)];
        }
        return $progress;
    }
    public function count_all_imported_entities(): float|int
    {
        $counts = $this->count_imported_entities();
        return array_sum(array_column($counts, 'imported'));
    }
    public function count_all_total_entities(): float|int
    {
        $counts = $this->count_imported_entities();
        return array_sum(array_column($counts, 'total'));
    }
    public function count_remaining_entities(): int|float
    {
        $counts = $this->count_imported_entities();
        return array_sum(array_column($counts, 'total')) - array_sum(array_column($counts, 'imported'));
    }
    /**
     * Cache of imported entity counts to avoid repeated database queries
     */
    private array $cached_imported_counts = [];
    /**
     * Updates the progress information.
     *
     * @param  array  $newly_imported_entities  The new progress data with keys: posts, comments, terms, attachments, users
     */
    public function bump_imported_entities_counts($newly_imported_entities): void
    {
        foreach ($newly_imported_entities as $field => $count) {
            if (!in_array($field, static::PROGRESS_ENTITIES, true)) {
                _doing_it_wrong(__METHOD__, 'Cannot bump imported entities count for unknown entity type: ' . $field, '1.0.0');
                continue;
            }
            // Get current count from cache or database
            if (!isset($this->cached_imported_counts[$field])) {
                $this->cached_imported_counts[$field] = (int) get_post_meta($this->post_id, 'imported_' . $field, true);
            }
            // Add new count to total
            $new_count = $this->cached_imported_counts[$field] + $count;
            // Update database and cache
            update_post_meta($this->post_id, 'imported_' . $field, $new_count);
            $this->cached_imported_counts[$field] = $new_count;
            /*
            @TODO run an atomic query instead:
            $sql = $wpdb->prepare(
                "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
                VALUES (%d, %s, %d)
                ON DUPLICATE KEY UPDATE meta_value = meta_value + %d",
                $this->post_id,
                'imported_' . $field,
                $count,
                $count
            );
            $wpdb->query($sql);
            */
        }
    }
    public function count_awaiting_frontloading_stubs(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts}\n\t\t\t\t WHERE post_type = 'frontloading_stub'\n\t\t\t\t AND post_parent = %d\n\t\t\t\t AND post_status = %s", $this->post_id, self::FRONTLOAD_STATUS_AWAITING_DOWNLOAD));
    }
    public function count_unfinished_frontloading_stubs(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts}\n\t\t\t\t WHERE post_type = 'frontloading_stub'\n\t\t\t\t AND post_parent = %d\n\t\t\t\t AND post_status != %s\n\t\t\t\t AND post_status != %s", $this->post_id, self::FRONTLOAD_STATUS_SUCCEEDED, self::FRONTLOAD_STATUS_IGNORED));
    }
    public function mark_frontloading_errors_as_ignored(): void
    {
        global $wpdb;
        $wpdb->update($wpdb->posts, ['post_status' => self::FRONTLOAD_STATUS_IGNORED], ['post_type' => 'frontloading_stub']);
    }
    public function get_frontloading_stubs(array $options = [])
    {
        $query = new WP_Query(['post_type' => 'frontloading_stub', 'post_status' => 'any', 'post_parent' => $this->post_id, 'posts_per_page' => $options['per_page'] ?? 25, 'paged' => $options['page'] ?? 1, 'orderby' => ['post_status' => [self::FRONTLOAD_STATUS_ERROR => 0, self::FRONTLOAD_STATUS_AWAITING_DOWNLOAD => 1, 'any' => 2], 'ID' => 'ASC']]);
        if (!$query->have_posts()) {
            return [];
        }
        $posts = $query->posts;
        $ids = array_map(fn($post) => $post->ID, $posts);
        update_meta_cache('post', $ids);
        foreach ($posts as $post) {
            $post->meta = get_all_post_meta_flat($post->ID);
        }
        return $posts;
    }
    /**
     * @return mixed[]
     */
    public function get_total_number_of_entities(): array
    {
        $totals = [];
        foreach (static::PROGRESS_ENTITIES as $field) {
            $totals[$field] = (int) get_post_meta($this->post_id, 'total_' . $field, true);
        }
        $totals['download'] = $this->get_total_number_of_assets();
        return $totals;
    }
    public function get_total_number_of_assets(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts}\n\t\t\tWHERE post_type = 'frontloading_stub'\n\t\t\tAND post_parent = %d", $this->post_id));
    }
    public function get_frontloading_stub($url)
    {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p\n\t\t\t\t INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id\n\t\t\t\t WHERE p.post_type = 'frontloading_stub'\n\t\t\t\t AND p.post_parent = %d\n\t\t\t\t AND pm.meta_key = 'current_url'\n\t\t\t\t AND pm.meta_value = %s\n\t\t\t\t LIMIT 1", $this->post_id, $url));
        return get_post($id);
    }
    /**
     * Creates placeholder attachments for the assets to be downloaded in the
     * frontloading stage.
     */
    public function create_frontloading_stubs($urls): void
    {
        global $wpdb;
        foreach ($urls as $url => $_) {
            /**
             * Check if placeholder with this URL already exists
             * There's a race condition here – another insert may happen
             * between the check and the insert.
             *
             * @TODO: Explore solutions. A custom table with a UNIQUE constraint
             * may or may not be an option, depending on the performance impact
             * on 100GB+ VIP databases.
             */
            $exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts}\n\t\t\t\tWHERE post_type = 'frontloading_stub'\n\t\t\t\tAND post_parent = %d\n\t\t\t\tAND guid = %s\n\t\t\t\tLIMIT 1", $this->post_id, $url));
            if ($exists) {
                continue;
            }
            $post_data = ['post_type' => 'frontloading_stub', 'post_parent' => $this->post_id, 'post_title' => basename((string) $url), 'post_status' => self::FRONTLOAD_STATUS_AWAITING_DOWNLOAD, 'guid' => $url, 'meta_input' => ['original_url' => $url, 'current_url' => $url, 'attempts' => 0, 'last_error' => null, 'target_path' => '']];
            $insertion_result = wp_insert_post($post_data);
            if (is_wp_error($insertion_result)) {
                throw new \Exception('Failed to insert frontloading placeholder');
            }
        }
    }
    /**
     * Sets the total number of entities to import for each type.
     *
     * @param  array  $totals  The total number of entities for each type
     */
    private array $cached_totals = [];
    public function bump_total_number_of_entities($newly_indexed_entities): void
    {
        foreach ($newly_indexed_entities as $field => $count) {
            if (!in_array($field, static::PROGRESS_ENTITIES, true)) {
                _doing_it_wrong(__METHOD__, 'Cannot set total number of entities for unknown entity type: ' . $field, '1.0.0');
                continue;
            }
            // Get current total from cache or database
            if (!isset($this->cached_totals[$field])) {
                $this->cached_totals[$field] = (int) get_post_meta($this->post_id, 'total_' . $field, true);
            }
            // Add new count to total
            $new_total = $this->cached_totals[$field] + $count;
            // Update database and cache
            update_post_meta($this->post_id, 'total_' . $field, $new_total);
            $this->cached_totals[$field] = $new_total;
        }
    }
    /**
     * Saves an array of [$url => ['received' => $downloaded_bytes, 'total' => $total_bytes | null]]
     * of the currently fetched files. The list is ephemeral and changes as we stream the data. There
     * will never be more than $concurrency_limit files in the list at any given time.
     */
    public function bump_frontloading_progress($frontloading_progress, $events = []): void
    {
        update_post_meta($this->post_id, 'frontloading_progress', $frontloading_progress);
        foreach ($events as $event) {
            $url = $event->resource_id;
            $placeholder = $this->get_frontloading_stub($url);
            if (!$placeholder) {
                _doing_it_wrong(__METHOD__, 'Frontloading placeholder post not found for URL: ' . $url, '1.0.0');
                continue;
            }
            update_post_meta($placeholder->ID, 'last_error', $event->error);
            $attempts = get_post_meta($placeholder->ID, 'attempts', true);
            $new_attempts = $attempts;
            $new_status = $placeholder->post_status;
            switch ($event->type) {
                case self::EVENT_SUCCESS:
                    $new_status = self::FRONTLOAD_STATUS_SUCCEEDED;
                    $new_attempts = $attempts + 1;
                    break;
                case self::EVENT_ALREADY_EXISTS:
                    $new_status = self::FRONTLOAD_STATUS_SUCCEEDED;
                    break;
                case self::EVENT_FAILURE:
                    $new_status = self::FRONTLOAD_STATUS_ERROR;
                    $new_attempts = $attempts + 1;
                    break;
            }
            if ($new_attempts !== $attempts) {
                update_post_meta($placeholder->ID, 'attempts', $new_attempts);
            }
            if ($new_status !== $placeholder->post_status) {
                wp_update_post(['ID' => $placeholder->ID, 'post_status' => $new_status]);
            }
        }
    }
    public function get_frontloading_progress()
    {
        $meta = get_post_meta($this->post_id, 'frontloading_progress', true);
        return $meta ?: [];
    }
    public function is_stage_completed($stage): bool
    {
        $current_stage = $this->get_stage();
        $stage_index = array_search($stage, self::STAGES_IN_ORDER, true);
        $current_stage_index = array_search($current_stage, self::STAGES_IN_ORDER, true);
        return $current_stage_index > $stage_index;
    }
    /**
     * Gets the current import stage.
     *
     * @return string The current stage
     */
    public function get_stage()
    {
        if (!isset($this->cached_stage)) {
            $meta = get_post_meta($this->post_id, 'current_stage', true);
            $this->cached_stage = $meta ?: self::STAGE_INITIAL;
        }
        return $this->cached_stage;
    }
    /**
     * Updates the current import stage.
     *
     * @param  string  $stage  The new stage
     */
    public function set_stage($stage): void
    {
        if ($stage === $this->get_stage()) {
            return;
        }
        if (self::STAGE_FINISHED === $stage) {
            update_post_meta($this->post_id, 'finished_at', time());
        }
        update_post_meta($this->post_id, 'current_stage', $stage);
        $this->cached_stage = $stage;
    }
    public function get_started_at()
    {
        return get_post_meta($this->post_id, 'started_at', true);
    }
    public function get_finished_at()
    {
        return get_post_meta($this->post_id, 'finished_at', true);
    }
    public function is_finished(): bool
    {
        return !empty(get_post_meta($this->post_id, 'finished_at', true));
    }
    /**
     * Gets the importer cursor for resuming imports.
     *
     * @return string|null The cursor data
     */
    public function get_reentrancy_cursor()
    {
        return get_post_meta($this->post_id, 'importer_cursor', true);
    }
    /**
     * Updates the importer cursor.
     *
     * @param  string  $cursor  The new cursor data
     */
    public function set_reentrancy_cursor($cursor): void
    {
        // WordPress, sadly, removes single slashes from the meta value and
        // requires an addslashes() call to preserve them.
        update_post_meta($this->post_id, 'importer_cursor', addslashes($cursor));
    }
    /**
     * Save the original command arguments for session resumption.
     *
     * @param array $args The original command arguments
     */
    public function set_original_arguments(array $args): void
    {
        update_post_meta($this->post_id, 'original_arguments', $args);
    }
    /**
     * Get the original command arguments for session resumption.
     *
     * @return array|null The original arguments or null if not found
     */
    public function get_original_arguments()
    {
        $args = get_post_meta($this->post_id, 'original_arguments', true);
        return is_array($args) && !empty($args) ? $args : null;
    }
}