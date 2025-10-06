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
     */
    public function get_users(WP_REST_Request $request) {
        if (!current_user_can('list_users')) {
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
     * Creates a new user (secure endpoint for managers).
     */
    public function create_user(WP_REST_Request $request) {
        if (!current_user_can('create_users')) {
            return new WP_Error('rest_forbidden', __('You cannot create users.', 'ahoi-api'), ['status' => 403]);
        }
        $auth_controller = new Auth_Controller();
        return $auth_controller->register_user($request);
    }
    
    // --- NEW FUNCTION: GET A SINGLE USER ---
    /**
     * Retrieves a single user's data by their ID.
     * This is the function that was missing.
     */
    public function get_user(WP_REST_Request $request) {
        if (!current_user_can('edit_users')) {
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

    // --- NEW FUNCTION: UPDATE A USER ---
    /**
     * Updates an existing user's data (email and role).
     */
    public function update_user(WP_REST_Request $request) {
        if (!current_user_can('edit_users')) {
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
     * Retrieves a list of all available roles.
     */
    public function get_roles(WP_REST_Request $request) {
        if (!current_user_can('edit_users')) {
            return new WP_Error('rest_forbidden', __('You cannot view roles.', 'ahoi-api'), ['status' => 403]);
        }
        global $wp_roles;
        return new WP_REST_Response($wp_roles->get_names(), 200);
    }
}