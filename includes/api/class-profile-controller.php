<?php
// File: includes/api/class-profile-controller.php
namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Profile_Controller {

    /**
     * Get the current user's profile (metadata).
     */
    public function get_my_profile(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $user_meta = get_user_meta($user_id);
        // Clean up the meta array for a cleaner response
        $profile_data = [];
        foreach ($user_meta as $key => $value) {
            // We don't expose private fields (like passwords hashes)
            if (strpos($key, 'wp_') !== 0 && $key !== 'session_tokens') {
                $profile_data[$key] = $value[0];
            }
        }
        return new WP_REST_Response($profile_data, 200);
    }

    /**
     * Update the current user's profile (metadata).
     */
    public function update_my_profile(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        // You should define which fields are updatable for security
        $allowed_fields = ['first_name', 'last_name', 'phone_number', 'company']; // Example

        foreach ($params as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                update_user_meta($user_id, sanitize_key($key), sanitize_text_field($value));
            }
        }

        return new WP_REST_Response(['success' => true, 'message' => 'Profile updated.'], 200);
    }
}