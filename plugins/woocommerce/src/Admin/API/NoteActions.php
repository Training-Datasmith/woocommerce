<?php

declare (strict_types=1);
/**
 * REST API Admin Note Action controller
 *
 * Handles requests to the admin note action endpoint.
 */
namespace Automattic\Woo_Commerce\Admin\API;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Note;
use Automattic\Woo_Commerce\Admin\Notes\Notes as NotesFactory;
/**
 * REST API Admin Note Action controller class.
 *
 * @internal
 * @extends WC_REST_CRUD_Controller
 */
class Note_Actions extends Notes
{
    /**
     * Register the routes for admin notes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<note_id>[\d-]+)/action/(?P<action_id>[\d-]+)', ['args' => ['note_id' => ['description' => __('Unique ID for the Note.', 'woocommerce'), 'type' => 'integer'], 'action_id' => ['description' => __('Unique ID for the Note Action.', 'woocommerce'), 'type' => 'integer']], [
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => $this->trigger_note_action(...),
            // @todo - double check these permissions for taking note actions.
            'permission_callback' => $this->get_item_permissions_check(...),
        ], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Trigger a note action.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Request|WP_Error
     */
    public function trigger_note_action($request)
    {
        $note = Notes_Factory::get_note($request->get_param('note_id'));
        if (!$note) {
            return new \WP_Error('woocommerce_note_invalid_id', __('Sorry, there is no resource with that ID.', 'woocommerce'), ['status' => 404]);
        }
        $note->set_is_read(true);
        $note->save();
        $triggered_action = Notes_Factory::get_action_by_id($note, $request->get_param('action_id'));
        if (!$triggered_action) {
            return new \WP_Error('woocommerce_note_action_invalid_id', __('Sorry, there is no resource with that ID.', 'woocommerce'), ['status' => 404]);
        }
        $triggered_note = Notes_Factory::trigger_note_action($note, $triggered_action);
        $data = $triggered_note->get_data();
        $data = $this->prepare_item_for_response($data, $request);
        $data = $this->prepare_response_for_collection($data);
        return rest_ensure_response($data);
    }
}