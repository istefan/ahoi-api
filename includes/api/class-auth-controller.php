<?php
/**
 * Controller for handling JWT authentication.
 *
 * @link      https://www.ahoi.ro/
 * @since     1.0.0
 * @package   Ahoi_API
 */

namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Auth_Controller.
 *
 * Manages endpoints for generating and validating JWT tokens.
 */
class Auth_Controller {

    /**
     * Registers a new user.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function register_user(WP_REST_Request $request) {
        $username = sanitize_user($request->get_param('username'));
        $email    = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $role     = sanitize_text_field($request->get_param('role')); // Get the role from the request

        if (empty($username) || empty($email) || empty($password)) {
            return new WP_Error('ahoi_api_missing_fields', __('Username, email, and password are required.', 'ahoi-api'), ['status' => 400]);
        }
        if (!is_email($email)) {
            return new WP_Error('ahoi_api_invalid_email', __('Invalid email address.', 'ahoi-api'), ['status' => 400]);
        }
        if (username_exists($username)) {
            return new WP_Error('ahoi_api_username_exists', __('Username already exists.', 'ahoi-api'), ['status' => 409]);
        }
        if (email_exists($email)) {
            return new WP_Error('ahoi_api_email_exists', __('Email address already in use.', 'ahoi-api'), ['status' => 409]);
        }

        // Use the secure WordPress function to create the user.
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Set the user's role. If a role is specified and valid, use it. Otherwise, use the default.
        $user = new \WP_User($user_id);
        if (!empty($role) && get_role($role)) {
            $user->set_role($role);
        } else {
            $user->set_role(get_option('default_role', 'subscriber'));
        }
        
        // --- TRIGGER WEBHOOK ON SUCCESSFUL USER CREATION ---
        // Get the full user data object to send in the payload.
        $new_user_data = get_userdata($user_id);
        if ($new_user_data) {
            // The helper function already exists in helpers.php
            ahoi_api_trigger_webhook('user.created', $new_user_data->data);
        }
        // --- END OF NEW CODE ---

        return new WP_REST_Response([
            'success' => true,
            'message' => __('User registered successfully.', 'ahoi-api'),
            'user_id' => $user_id,
        ], 201);
    }

    /**
     * Generates a JWT based on user credentials.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error Response with the token or an error.
     */
    public function generate_token(WP_REST_Request $request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');

        if (empty($username) || empty($password)) {
            return new WP_Error(
                'ahoi_api_bad_request',
                __('Username and password are required.', 'ahoi-api'),
                ['status' => 400]
            );
        }

        // Authenticate the user using the WordPress system.
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error(
                'ahoi_api_invalid_credentials',
                __('Invalid username or password.', 'ahoi-api'),
                ['status' => 403]
            );
        }

        // Valid credentials, generate the token.
        $issued_at       = time();
        $expiration_time = $issued_at + (DAY_IN_SECONDS * 7); // Token is valid for 7 days

        $payload = [
            'iss'  => get_bloginfo('url'),
            'iat'  => $issued_at,
            'nbf'  => $issued_at,
            'exp'  => $expiration_time,
            'data' => [
                'user' => [
                    'id'    => $user->ID,
                    'roles' => $user->roles,
                ],
            ],
        ];

        // Sign the token using the secret key from wp-config.php for security.
        $jwt = JWT::encode($payload, SECURE_AUTH_KEY, 'HS256');

        return new WP_REST_Response([
            'success' => true,
            'token'   => $jwt,
            'user'    => [
                'id'           => $user->ID,
                'email'        => $user->user_email,
                'display_name' => $user->display_name,
                'roles'        => $user->roles,
            ],
        ], 200);
    }

    /**
     * Validates the token sent in the Authorization header.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function validate_token(WP_REST_Request $request) {
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Token is valid.', 'ahoi-api'),
            'user_id' => get_current_user_id()
        ], 200);
    }

    /**
     * Permission callback for validating the token.
     * This is the key function that protects endpoints.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error True if the token is valid, otherwise WP_Error.
     */
    public function validate_token_permission(WP_REST_Request $request) {
        $auth_header = $request->get_header('Authorization');

        if (!$auth_header) {
            return new WP_Error('ahoi_api_no_auth_header', __('Authorization header not found.', 'ahoi-api'), ['status' => 401]);
        }

        // Extract the token from the header (e.g., "Bearer <token>").
        list($token) = sscanf($auth_header, 'Bearer %s');
        
        if (!$token) {
            return new WP_Error('ahoi_api_bad_auth_header', __('Malformed Authorization header.', 'ahoi-api'), ['status' => 401]);
        }

        try {
            // Decode and validate the token.
            // This function throws an exception if the token is invalid (expired, wrong signature, etc.).
            $decoded = JWT::decode($token, new Key(SECURE_AUTH_KEY, 'HS256'));

            // Check if the user ID exists in the token.
            if (!isset($decoded->data->user->id)) {
                return false;
            }

            $user_id = $decoded->data->user->id;

            // Set the current user in WordPress based on the token's user ID.
            // This step is VITAL for functions like `current_user_can()` to work correctly.
            wp_set_current_user($user_id);
            
            return true;

        } catch (\Exception $e) {
            // The token is invalid.
            return new WP_Error('ahoi_api_invalid_token', $e->getMessage(), ['status' => 403]);
        }

        return false;
    }
}