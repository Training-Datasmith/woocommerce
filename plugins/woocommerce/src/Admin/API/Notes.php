<?php

declare (strict_types=1);
/**
 * REST API Admin Notes controller
 *
 * Handles requests to the admin notes endpoint.
 */
namespace Automattic\Woo_Commerce\Admin\API;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Notes as NotesRepository;
/**
 * REST API Admin Notes controller class.
 *
 * @internal
 * @extends WC_REST_CRUD_Controller
 */
class Notes extends \WC_REST_CRUD_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-analytics';
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'admin/notes';
    /**
     * Register the routes for admin notes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_items(...), 'permission_callback' => $this->get_items_permissions_check(...), 'args' => $this->get_collection_params()], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d-]+)', ['args' => ['id' => ['description' => __('Unique ID for the resource.', 'woocommerce'), 'type' => 'integer']], ['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_item(...), 'permission_callback' => $this->get_item_permissions_check(...)], ['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->update_item(...), 'permission_callback' => $this->update_items_permissions_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete/(?P<id>[\d-]+)', [['methods' => \WP_REST_Server::DELETABLE, 'callback' => $this->delete_item(...), 'permission_callback' => $this->update_items_permissions_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete/all', [['methods' => \WP_REST_Server::DELETABLE, 'callback' => $this->delete_all_items(...), 'permission_callback' => $this->update_items_permissions_check(...), 'args' => ['status' => ['description' => __('Status of note.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_slug_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['enum' => Note::get_allowed_statuses(), 'type' => 'string']]]], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/update', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->batch_update_items(...), 'permission_callback' => $this->update_items_permissions_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/experimental-activate-promo/(?P<promo_note_name>[\w-]+)', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->activate_promo_note(...), 'permission_callback' => $this->update_items_permissions_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Get a single note.
     *
     * @param WP_REST_Request $request Request data.
     * @return WP_REST_Response|WP_Error
     */
    public function get_item($request)
    {
        $note = Notes_Repository::get_note($request->get_param('id'));
        if (!$note) {
            return new \WP_Error('woocommerce_note_invalid_id', __('Sorry, there is no resource with that ID.', 'woocommerce'), ['status' => 404]);
        }
        if (is_wp_error($note)) {
            return $note;
        }
        $data = $this->prepare_note_data_for_response($note, $request);
        return rest_ensure_response($data);
    }
    /**
     * Get all notes.
     *
     * @param WP_REST_Request $request Request data.
     * @return WP_REST_Response
     */
    public function get_items($request)
    {
        $query_args = $this->prepare_objects_query($request);
        $notes = Notes_Repository::get_notes('edit', $query_args);
        $data = [];
        foreach ((array) $notes as $note_obj) {
            $note = $this->prepare_item_for_response($note_obj, $request);
            $note = $this->prepare_response_for_collection($note);
            $data[] = $note;
        }
        $response = rest_ensure_response($data);
        $response->header('X-WP-Total', count($data));
        return $response;
    }
    /**
     * Prepare objects query.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return array
     */
    protected function prepare_objects_query($request)
    {
        $args = [];
        $args['order'] = $request['order'];
        $args['orderby'] = $request['orderby'];
        $args['per_page'] = $request['per_page'];
        $args['page'] = $request['page'];
        $args['type'] = $request['type'] ?? [];
        $args['status'] = $request['status'] ?? [];
        $args['source'] = $request['source'] ?? [];
        $args['is_deleted'] = 0;
        if (isset($request['is_read'])) {
            $args['is_read'] = filter_var($request['is_read'], FILTER_VALIDATE_BOOLEAN);
        }
        if ('date' === $args['orderby']) {
            $args['orderby'] = 'date_created';
        }
        /**
         * Filter the query arguments for a request.
         *
         * Enables adding extra arguments or setting defaults for a post
         * collection request.
         *
         * @param array           $args    Key value array of query var to query value.
         * @param WP_REST_Request $request The request used.
         * @since 3.9.0
         */
        $args = apply_filters('woocommerce_rest_notes_object_query', $args, $request);
        return $args;
    }
    /**
     * Check whether a given request has permission to read a single note.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request): \WP_Error|true
    {
        if (!wc_rest_check_manager_permissions('system_status', 'read')) {
            return new \WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Check whether a given request has permission to read notes.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request): \WP_Error|true
    {
        if (!wc_rest_check_manager_permissions('system_status', 'read')) {
            return new \WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Update a single note.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Request|WP_Error
     */
    public function update_item($request)
    {
        $note = Notes_Repository::get_note($request->get_param('id'));
        if (!$note) {
            return new \WP_Error('woocommerce_note_invalid_id', __('Sorry, there is no resource with that ID.', 'woocommerce'), ['status' => 404]);
        }
        Notes_Repository::update_note($note, $this->get_requested_updates($request));
        return $this->get_item($request);
    }
    /**
     * Delete a single note.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Request|WP_Error
     */
    public function delete_item($request)
    {
        $note = Notes_Repository::get_note($request->get_param('id'));
        if (!$note) {
            return new \WP_Error('woocommerce_note_invalid_id', __('Sorry, there is no note with that ID.', 'woocommerce'), ['status' => 404]);
        }
        Notes_Repository::delete_note($note);
        $data = $this->prepare_note_data_for_response($note, $request);
        return rest_ensure_response($data);
    }
    /**
     * Delete all notes.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Request|WP_Error
     */
    public function delete_all_items($request)
    {
        $args = [];
        if (isset($request['status'])) {
            $args['status'] = $request['status'];
        }
        $notes = Notes_Repository::delete_all_notes($args);
        $data = [];
        foreach ((array) $notes as $note_obj) {
            $data[] = $this->prepare_note_data_for_response($note_obj, $request);
        }
        $response = rest_ensure_response($data);
        $response->header('X-WP-Total', Notes_Repository::get_notes_count(['info', 'warning'], []));
        return $response;
    }
    /**
     * Prepare note data.
     *
     * @param Note            $note     Note data.
     * @param WP_REST_Request $request  Request object.
     *
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_note_data_for_response($note, $request)
    {
        $note = $note->get_data();
        $note = $this->prepare_item_for_response($note, $request);
        return $this->prepare_response_for_collection($note);
    }
    /**
     * Prepare an array with the requested updates.
     *
     * @param WP_REST_Request $request  Request object.
     * @return array A list of the requested updates values.
     */
    protected function get_requested_updates($request)
    {
        $requested_updates = [];
        if (!is_null($request->get_param('status'))) {
            $requested_updates['status'] = $request->get_param('status');
        }
        if (!is_null($request->get_param('date_reminder'))) {
            $requested_updates['date_reminder'] = $request->get_param('date_reminder');
        }
        if (!is_null($request->get_param('is_deleted'))) {
            $requested_updates['is_deleted'] = $request->get_param('is_deleted');
        }
        if (!is_null($request->get_param('is_read'))) {
            $requested_updates['is_read'] = $request->get_param('is_read');
        }
        return $requested_updates;
    }
    /**
     * Batch update a set of notes.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Request|WP_Error
     */
    public function batch_update_items($request)
    {
        $data = [];
        $note_ids = $request->get_param('noteIds');
        if (!isset($note_ids) || !is_array($note_ids)) {
            return new \WP_Error('woocommerce_note_invalid_ids', __('Please provide an array of IDs through the noteIds param.', 'woocommerce'), ['status' => 422]);
        }
        foreach ($note_ids as $note_id) {
            $note = Notes_Repository::get_note((int) $note_id);
            if ($note) {
                Notes_Repository::update_note($note, $this->get_requested_updates($request));
                $data[] = $this->prepare_note_data_for_response($note, $request);
            }
        }
        $response = rest_ensure_response($data);
        $response->header('X-WP-Total', Notes_Repository::get_notes_count(['info', 'warning'], []));
        return $response;
    }
    /**
     * Activate a promo note, create if not exist.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Request|WP_Error
     */
    public function activate_promo_note($request)
    {
        /**
         * Filter allowed promo notes for experimental-activate-promo.
         *
         * @param array     $promo_notes    Array of allowed promo notes.
         * @since 7.8.0
         */
        $allowed_promo_notes = apply_filters('woocommerce_admin_allowed_promo_notes', []);
        $promo_note_name = $request->get_param('promo_note_name');
        if (!in_array($promo_note_name, $allowed_promo_notes, true)) {
            return new \WP_Error('woocommerce_note_invalid_promo_note_name', __('Please provide a valid promo note name.', 'woocommerce'), ['status' => 422]);
        }
        $data_store = Notes_Repository::load_data_store();
        $note_ids = $data_store->get_notes_with_name($promo_note_name);
        if (empty($note_ids)) {
            // Promo note doesn't exist, this could happen in cases where
            // user might have disabled RemoteInboxNotications via disabling
            // marketing suggestions. Thus we'd have to manually add the note.
            $note = new Note();
            $note->set_name($promo_note_name);
            $note->set_status(Note::E_WC_ADMIN_NOTE_ACTIONED);
            $data_store->create($note);
        } else {
            $note = Notes_Repository::get_note($note_ids[0]);
            Notes_Repository::update_note($note, ['status' => Note::E_WC_ADMIN_NOTE_ACTIONED]);
        }
        return rest_ensure_response(['success' => true]);
    }
    /**
     * Makes sure the current user has access to WRITE the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function update_items_permissions_check($request)
    {
        if (!wc_rest_check_manager_permissions('settings', 'edit')) {
            return new \WP_Error('woocommerce_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Prepare a path or query for serialization to the client.
     *
     * @param string $query The query, path, or URL to transform.
     * @return string A fully formed URL.
     */
    public function prepare_query_for_response($query)
    {
        if (empty($query)) {
            return $query;
        }
        if (str_starts_with($query, 'https://')) {
            return $query;
        }
        if (str_starts_with($query, 'http://')) {
            return $query;
        }
        if (str_starts_with($query, '?')) {
            return admin_url('admin.php' . $query);
        }
        return admin_url($query);
    }
    /**
     * Maybe add a nonce to a URL.
     *
     * @link https://codex.wordpress.org/WordPress_Nonces
     *
     * @param string $url The URL needing a nonce.
     * @param string $action The nonce action.
     * @param string $name The nonce name.
     * @return string A fully formed URL.
     */
    private function maybe_add_nonce_to_url(string $url, string $action = '', string $name = ''): string
    {
        if (empty($action)) {
            return $url;
        }
        if (empty($name)) {
            // Default parameter name.
            $name = '_wpnonce';
        }
        return add_query_arg($name, wp_create_nonce($action), $url);
    }
    /**
     * Prepare a note object for serialization.
     *
     * @param array           $data Note data.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($data, $request)
    {
        $context = !empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data['date_created_gmt'] = wc_rest_prepare_date_response($data['date_created']);
        $data['date_created'] = wc_rest_prepare_date_response($data['date_created'], false);
        $data['date_reminder_gmt'] = wc_rest_prepare_date_response($data['date_reminder']);
        $data['date_reminder'] = wc_rest_prepare_date_response($data['date_reminder'], false);
        $data['title'] = stripslashes((string) $data['title']);
        $data['content'] = stripslashes((string) $data['content']);
        $data['is_snoozable'] = (bool) $data['is_snoozable'];
        $data['is_deleted'] = (bool) $data['is_deleted'];
        $data['is_read'] = (bool) $data['is_read'];
        foreach ((array) $data['actions'] as $key => $value) {
            $data['actions'][$key]->label = stripslashes((string) $data['actions'][$key]->label);
            $data['actions'][$key]->url = $this->maybe_add_nonce_to_url($this->prepare_query_for_response($data['actions'][$key]->query), (string) $data['actions'][$key]->nonce_action, (string) $data['actions'][$key]->nonce_name);
            $data['actions'][$key]->status = stripslashes((string) $data['actions'][$key]->status);
        }
        $data = $this->filter_response_by_context($data, $context);
        // Wrap the data in a response object.
        $response = rest_ensure_response($data);
        $response->add_links(['self' => ['href' => rest_url(sprintf('/%s/%s/%d', $this->namespace, $this->rest_base, $data['id']))], 'collection' => ['href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base))]]);
        /**
         * Filter a note returned from the API.
         *
         * Allows modification of the note data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param array            $data The original note.
         * @param WP_REST_Request  $request  Request used to generate the response.
         * @since 3.9.0
         */
        return apply_filters('woocommerce_rest_prepare_note', $response, $data, $request);
    }
    /**
     * Track opened emails.
     *
     * @deprecated 10.6.0 This method is no longer functional as the email tracking feature was removed in WooCommerce 9.9.
     *
     * @param WP_REST_Request $request Request object.
     */
    public function track_opened_email($request): void
    {
        wc_deprecated_function(__METHOD__, '10.6.0');
    }
    /**
     * Get the query params for collections.
     */
    public function get_collection_params(): array
    {
        $params = [];
        $params['context'] = $this->get_context_param(['default' => 'view']);
        $params['order'] = ['description' => __('Order sort attribute ascending or descending.', 'woocommerce'), 'type' => 'string', 'default' => 'desc', 'enum' => ['asc', 'desc'], 'validate_callback' => 'rest_validate_request_arg'];
        $params['orderby'] = ['description' => __('Sort collection by object attribute.', 'woocommerce'), 'type' => 'string', 'default' => 'date', 'enum' => ['note_id', 'date', 'type', 'title', 'status'], 'validate_callback' => 'rest_validate_request_arg'];
        $params['page'] = ['description' => __('Current page of the collection.', 'woocommerce'), 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg', 'minimum' => 1];
        $params['per_page'] = ['description' => __('Maximum number of items to be returned in result set.', 'woocommerce'), 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 100, 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg'];
        $params['type'] = ['description' => __('Type of note.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_slug_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['enum' => Note::get_allowed_types(), 'type' => 'string']];
        $params['status'] = ['description' => __('Status of note.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_slug_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['enum' => Note::get_allowed_statuses(), 'type' => 'string']];
        $params['source'] = ['description' => __('Source of note.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['type' => 'string']];
        return $params;
    }
    /**
     * Get the note's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'note', 'type' => 'object', 'properties' => ['id' => ['description' => __('ID of the note record.', 'woocommerce'), 'type' => 'integer', 'context' => ['view'], 'readonly' => true], 'name' => ['description' => __('Name of the note.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'type' => ['description' => __('The type of the note (e.g. error, warning, etc.).', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'locale' => ['description' => __('Locale used for the note title and content.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'title' => ['description' => __('Title of the note.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'content' => ['description' => __('Content of the note.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'content_data' => ['description' => __('Content data for the note. JSON string. Available for re-localization.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'status' => ['description' => __('The status of the note (e.g. unactioned, actioned).', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit']], 'source' => ['description' => __('Source of the note.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'date_created' => ['description' => __('Date the note was created.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'date_created_gmt' => ['description' => __('Date the note was created (GMT).', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'date_reminder' => ['description' => __('Date after which the user should be reminded of the note, if any.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'date_reminder_gmt' => ['description' => __('Date after which the user should be reminded of the note, if any (GMT).', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'is_snoozable' => ['description' => __('Whether or not a user can request to be reminded about the note.', 'woocommerce'), 'type' => 'boolean', 'context' => ['view', 'edit'], 'readonly' => true], 'actions' => ['description' => __('An array of actions, if any, for the note.', 'woocommerce'), 'type' => 'array', 'context' => ['view', 'edit'], 'readonly' => true], 'layout' => ['description' => __('The layout of the note (e.g. banner, thumbnail, plain).', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'image' => ['description' => __('The image of the note, if any.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'is_deleted' => ['description' => __('Registers whether the note is deleted or not', 'woocommerce'), 'type' => 'boolean', 'context' => ['view', 'edit'], 'readonly' => true], 'is_read' => ['description' => __('Registers whether the note is read or not', 'woocommerce'), 'type' => 'boolean', 'context' => ['view', 'edit'], 'readonly' => true]]];
        return $this->add_additional_fields_schema($schema);
    }
}