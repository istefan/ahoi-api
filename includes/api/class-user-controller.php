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
     * Retrieves a list of users.
     * Non-admins will not see administrator accounts in the list.
     */
    public function get_users(WP_REST_Request $request) {
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You cannot view users.', 'ahoi-api'), ['status' => 403]);
        }
        
        $all_users = get_users();
        $response_data = [];

        foreach ($all_users as $user) {
            // SECURITY: If the current user is NOT an admin, do not show other admins in the list.
            if (!current_user_can('administrator') && in_array('administrator', $user->roles)) {
                continue; // Skip this administrator and go to the next user
            }

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
     * Prevents non-admins from creating admin accounts.
     */
    public function create_user(WP_REST_Request $request) {
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You cannot create users.', 'ahoi-api'), ['status' => 403]);
        }

        // SECURITY: Prevent a non-admin from creating an admin user.
        $params = $request->get_params();
        if (isset($params['role']) && $params['role'] === 'administrator' && !current_user_can('administrator')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to create administrator accounts.', 'ahoi-api'), ['status' => 403]);
        }
        
        $auth_controller = new Auth_Controller();
        return $auth_controller->register_user($request);
    }

    /**
     * Retrieves a single user's data by their ID.
     * Prevents non-admins from viewing admin profiles.
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

        // SECURITY: Prevent a non-admin from viewing an admin's profile.
        if (in_array('administrator', $user->roles) && !current_user_can('administrator')) {
             return new WP_Error('rest_forbidden', __('You do not have permission to view administrator accounts.', 'ahoi-api'), ['status' => 403]);
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
     * Prevents non-admins from editing admins.
     */
    public function update_user(WP_REST_Request $request) {
        if (!current_user_can('manage_api_users')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to update this user.', 'ahoi-api'), ['status' => 403]);
        }
        
        $user_id_to_edit = (int) $request['id'];
        $user_to_edit = get_userdata($user_id_to_edit);

        if (!$user_to_edit) {
            return new WP_Error('rest_user_not_found', __('User not found.', 'ahoi-api'), ['status' => 404]);
        }

        // SECURITY: Prevent a non-admin from editing an admin.
        if (in_array('administrator', $user_to_edit->roles) && !current_user_can('administrator')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to edit administrator accounts.', 'ahoi-api'), ['status' => 403]);
        }
        
        $params = $request->get_json_params();

        // SECURITY: Prevent a non-admin from promoting a user to admin.
        if (isset($params['role']) && $params['role'] === 'administrator' && !current_user_can('administrator')) {
            return new WP_Error('rest_forbidden', __('You do not have permission to assign the administrator role.', 'ahoi-api'), ['status' => 403]);
        }

        // Update core user data
        $user_data = ['ID' => $user_id_to_edit];
        if (isset($params['email'])) {
            $user_data['user_email'] = sanitize_email($params['email']);
        }
        wp_update_user($user_data);

        // Update user role
        if (isset($params['role'])) {
            $user_to_edit->set_role(sanitize_text_field($params['role']));
        }
        
        return new WP_REST_Response(['success' => true, 'message' => 'User updated successfully.'], 200);
    }

    /**
     * Retrieves a list of available roles.
     * Managers will not see default WordPress roles.
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
     * Only administrators can delete users.
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