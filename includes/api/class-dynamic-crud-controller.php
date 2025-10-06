<?php
/**
 * Controller for handling CRUD operations on dynamic data structures.
 *
 * @link      https://www.ahoi.ro/
 * @since     1.0.0
 * @package   Ahoi_API
 */

namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contains the logic to fetch, create, update, and delete records
 * from the custom, dynamically generated data tables.
 *
 * SECURITY MODEL:
 * 1. A valid JWT is required for all actions. This authenticates the user.
 * 2. Data access is restricted based on ownership. Users can only view,
 *    update, or delete items they have created (via the `owner_id` column).
 */
class Dynamic_Crud_Controller {

    private $current_structure = null;

    // --- PERMISSION CHECK METHODS ---
    public function get_items_permissions_check(WP_REST_Request $request) { return $this->check_jwt_permission($request); }
    public function get_item_permissions_check(WP_REST_Request $request) { return $this->check_jwt_permission($request); }
    public function create_item_permissions_check(WP_REST_Request $request) { return $this->check_jwt_permission($request); }
    public function update_item_permissions_check(WP_REST_Request $request) { return $this->check_jwt_permission($request); }
    public function delete_item_permissions_check(WP_REST_Request $request) { return $this->check_jwt_permission($request); }


    // --- CRUD CALLBACK METHODS ---

    /**
     * Retrieves a list of items owned by the current user.
     */
    public function get_items(WP_REST_Request $request) {
        $structure = $this->get_structure_details($request);
        if (is_wp_error($structure)) return $structure;

        global $wpdb;
        $table_name = $this->get_table_name_from_slug($structure['slug']);
        $current_user_id = get_current_user_id();

        // SQL query now includes a WHERE clause to fetch only the user's own items.
        $sql = $wpdb->prepare(
            "SELECT * FROM `{$table_name}` WHERE owner_id = %d ORDER BY id DESC",
            $current_user_id
        );

        $items = $wpdb->get_results($sql);
        return new WP_REST_Response($items, 200);
    }

    /**
     * Retrieves a single item by ID, but only if it is owned by the current user.
     */
    public function get_item(WP_REST_Request $request) {
        $structure = $this->get_structure_details($request);
        if (is_wp_error($structure)) return $structure;
        
        $id = (int) $request['id'];
        global $wpdb;
        $table_name = $this->get_table_name_from_slug($structure['slug']);
        $current_user_id = get_current_user_id();
        
        // The query now checks both the item ID and the owner ID.
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$table_name}` WHERE id = %d AND owner_id = %d",
            $id,
            $current_user_id
        ));
        
        if (!$item) {
            return new WP_Error('ahoi_api_not_found', __('Item not found or you do not have permission to view it.', 'ahoi-api'), ['status' => 404]);
        }
        
        return new WP_REST_Response($item, 200);
    }

    /**
     * Creates a new item, automatically assigning ownership to the current user.
     */
    public function create_item(WP_REST_Request $request) {
        $structure = $this->get_structure_details($request);
        if (is_wp_error($structure)) return $structure;

        $params = $request->get_json_params();
        $validated_data = $this->validate_and_sanitize_params($params, $structure['fields'], 'create');
        if (is_wp_error($validated_data)) return $validated_data;

        global $wpdb;
        $table_name = $this->get_table_name_from_slug($structure['slug']);
        $validated_data['owner_id'] = get_current_user_id(); // Assign ownership
        $validated_data['created_at'] = current_time('mysql', 1);
        $validated_data['updated_at'] = current_time('mysql', 1);

        $result = $wpdb->insert($table_name, $validated_data);
        if (false === $result) return new WP_Error('ahoi_api_db_error', __('Could not insert item.', 'ahoi-api'), ['status' => 500]);
        
        $new_item_id = $wpdb->insert_id;
        $new_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table_name}` WHERE id = %d", $new_item_id));
        return new WP_REST_Response($new_item, 201);
    }

    /**
     * Updates an existing item, but only if it is owned by the current user.
     */
    public function update_item(WP_REST_Request $request) {
        $structure = $this->get_structure_details($request);
        if (is_wp_error($structure)) return $structure;

        $id = (int) $request['id'];
        global $wpdb;
        $table_name = $this->get_table_name_from_slug($structure['slug']);
        $current_user_id = get_current_user_id();

        // First, verify ownership.
        $owner_id = $wpdb->get_var($wpdb->prepare("SELECT owner_id FROM `{$table_name}` WHERE id = %d", $id));
        if ($owner_id != $current_user_id) {
            return new WP_Error('rest_forbidden', __('You do not have permission to modify this item.', 'ahoi-api'), ['status' => 403]);
        }

        $params = $request->get_json_params();
        $validated_data = $this->validate_and_sanitize_params($params, $structure['fields'], 'update');
        if (is_wp_error($validated_data)) return $validated_data;
        if (empty($validated_data)) return new WP_Error('ahoi_api_bad_request', __('No valid data provided for update.', 'ahoi-api'), ['status' => 400]);

        $validated_data['updated_at'] = current_time('mysql', 1);
        $wpdb->update($table_name, $validated_data, ['id' => $id]);
        
        $updated_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table_name}` WHERE id = %d", $id));
        return new WP_REST_Response($updated_item, 200);
    }

    /**
     * Deletes an item, but only if it is owned by the current user.
     */
    public function delete_item(WP_REST_Request $request) {
        $structure = $this->get_structure_details($request);
        if (is_wp_error($structure)) return $structure;

        $id = (int) $request['id'];
        global $wpdb;
        $table_name = $this->get_table_name_from_slug($structure['slug']);
        $current_user_id = get_current_user_id();
        
        // First, verify ownership.
        $owner_id = $wpdb->get_var($wpdb->prepare("SELECT owner_id FROM `{$table_name}` WHERE id = %d", $id));
        if ($owner_id != $current_user_id) {
            return new WP_Error('rest_forbidden', __('You do not have permission to delete this item.', 'ahoi-api'), ['status' => 403]);
        }

        $result = $wpdb->delete($table_name, ['id' => $id]);
        if (!$result) return new WP_Error('ahoi_api_not_found', __('Item not found or could not be deleted.', 'ahoi-api'), ['status' => 404]);
        
        return new WP_REST_Response(['success' => true, 'message' => 'Item deleted.'], 200);
    }


    // --- HELPER METHODS ---

    private function validate_and_sanitize_params($params, $fields, $context = 'create') {
        $sanitized_data = [];
        $fields_by_slug = array_column($fields, null, 'slug');

        if ($context === 'create') {
            foreach ($fields as $field) {
                if ($field->is_required && !isset($params[$field->slug])) {
                    $message = sprintf(__('Required field "%s" is missing.', 'ahoi-api'), $field->name);
                    return new WP_Error('ahoi_api_missing_param', $message, ['status' => 400]);
                }
            }
        }

        foreach ($params as $key => $value) {
            if (isset($fields_by_slug[$key])) {
                $field_type = $fields_by_slug[$key]->type;
                switch ($field_type) {
                    case 'TEXT_SHORT': $sanitized_data[$key] = sanitize_text_field($value); break;
                    case 'TEXT_LONG': $sanitized_data[$key] = sanitize_textarea_field($value); break;
                    case 'NUMBER_INT': $sanitized_data[$key] = intval($value); break;
                    case 'NUMBER_DECIMAL': $sanitized_data[$key] = floatval($value); break;
                    case 'BOOLEAN': $sanitized_data[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN); break;
                    case 'DATETIME':
                    case 'DATE': $sanitized_data[$key] = sanitize_text_field($value); break;
                    case 'RELATIONSHIP': $sanitized_data[$key] = absint($value); break;
                    case 'JSON': $sanitized_data[$key] = is_string($value) ? wp_check_json($value) : wp_json_encode($value); break;
                    default: $sanitized_data[$key] = sanitize_text_field($value);
                }
            }
        }
        return $sanitized_data;
    }

    private function check_jwt_permission(WP_REST_Request $request) {
        $auth_controller = new Auth_Controller();
        return $auth_controller->validate_token_permission($request);
    }
    
    private function get_table_name_from_slug($slug) {
        global $wpdb;
        $safe_slug = preg_replace('/[^a-zA-Z0-9_]/', '', $slug);
        return $wpdb->prefix . 'ahoi_data_' . $safe_slug;
    }

    private function get_structure_details(WP_REST_Request $request) {
        if ($this->current_structure !== null) return $this->current_structure;

        $route = $request->get_route();
        $parts = explode('/', trim($route, '/'));
        $slug = $parts[2] ?? null;

        if (!$slug) return new WP_Error('ahoi_api_invalid_route', __('Could not determine structure from route.', 'ahoi-api'), ['status' => 500]);
        
        global $wpdb;
        $structures_table = $wpdb->prefix . 'ahoi_api_structures';
        $fields_table = $wpdb->prefix . 'ahoi_api_fields';
        
        $structure = $wpdb->get_row($wpdb->prepare("SELECT id, slug FROM `{$structures_table}` WHERE slug = %s", $slug), ARRAY_A);
        if (!$structure) return new WP_Error('ahoi_api_structure_not_found', __('Structure not found.', 'ahoi-api'), ['status' => 404]);

        $structure['fields'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$fields_table}` WHERE structure_id = %d", $structure['id']));

        $this->current_structure = $structure;
        return $this->current_structure;
    }
}