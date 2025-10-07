<?php
/**
 * Controller for managing users.
 *
 * @link      https://www.ahoi.ro/
 * @since     1.0.0
 * @package   Ahoi_API
 */

namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class User_Controller {

    /**
     * Retrieves a list of all users.
     * Requires the custom 'manage_api_users' capability.
     */
    public function get_users(WP_REST_Request $request) {
        // MODIFIED: Use our custom capability
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You cannot view users.', 'ahoi-api'), ['status' => 403]);
        }
        
        $all_users = get_users();
        $response_data = [];
        foreach ($all_users as $user) {
            $response_data[] = [
                'ID'           => $user->ID,
                'user_login'   => $user->user_login,
                'display_name' => $user->display_name,
                'user_email'   => $user->user_email,
                'roles'        => $user->roles,
            ];
        }
        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Creates a new user.
     * This endpoint is a proxy for the register_user method but secured with 'manage_api_users'.
     */
    public function create_user(WP_REST_Request $request) {
        // MODIFIED: Use our custom capability
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You cannot create users.', 'ahoi-api'), ['status' => 403]);
        }
        
        $auth_controller = new Auth_Controller();
        return $auth_controller->register_user($request);
    }

    /**
     * Retrieves a single user's data by their ID.
     */
    public function get_user(WP_REST_Request $request) {
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to view this user.', 'ahoi-api'), ['status' => 403]);
        }
        
        $user_id = (int) $request['id'];
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('rest_user_not_found', __('User not found.', 'ahoi-api'), ['status' => 404]);
        }
        
        $response_data = [
            'ID'           => $user->ID,
            'user_login'   => $user->user_login,
            'display_name' => $user->display_name,
            'user_email'   => $user->user_email,
            'roles'        => $user->roles,
        ];
        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Updates an existing user's data (email and role).
     */
    public function update_user(WP_REST_Request $request) {
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to update this user.', 'ahoi-api'), ['status' => 403]);
        }
        
        $user_id = (int) $request['id'];
        $params = $request->get_json_params();

        // Update core user data
        $user_data = ['ID' => $user_id];
        if (isset($params['email'])) {
            $user_data['user_email'] = sanitize_email($params['email']);
        }
        wp_update_user($user_data);

        // Update user role
        if (isset($params['role'])) {
            $user = new \WP_User($user_id);
            $user->set_role(sanitize_text_field($params['role']));
        }
        
        return new WP_REST_Response(['success' => true, 'message' => 'User updated successfully.'], 200);
    }

    /**
     * Retrieves a list of available roles.
     * MODIFIED: Managers will not see default WordPress roles.
     */
    public function get_roles(WP_REST_Request $request) {
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You cannot view roles.', 'ahoi-api'), ['status' => 403]);
        }
        
        global $wp_roles;
        $all_roles = $wp_roles->get_names();

        // If the current user is NOT an administrator, filter out the default roles
        if (!current_user_can('administrator')) {
            $default_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
            foreach ($default_roles as $role_key) {
                unset($all_roles[$role_key]);
            }
        }
        
        return new WP_REST_Response($all_roles, 200);
    }
    
    /**
     * Deletes a user.
     * MODIFIED: Only administrators can delete users.
     */
    public function delete_user(WP_REST_Request $request) {
        // Stricter check: Only allow administrators to delete.
        if (!current_user_can('administrator')) {
            return new WP_Error('rest_forbidden', __('Only administrators can delete users.', 'ahoi-api'), ['status' => 403]);
        }

        $user_id = (int) $request['id'];
        if (get_current_user_id() === $user_id) {
            return new WP_Error('rest_cannot_delete_self', __('You cannot delete your own account.', 'ahoi-api'), ['status' => 403]);
        }

        require_once(ABSPATH.'wp-admin/includes/user.php');
        if (wp_delete_user($user_id)) {
            return new WP_REST_Response(['success' => true, 'message' => 'User deleted successfully.'], 200);
        } else {
            return new WP_Error('rest_user_delete_failed', __('Failed to delete user.', 'ahoi-api'), ['status' => 500]);
        }
    }
}