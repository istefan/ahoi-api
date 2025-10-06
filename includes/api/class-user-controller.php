<?php
/**
 * Controller pentru gestionarea utilizatorilor.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */
namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class User_Controller {

    /**
     * Obține o listă de utilizatori.
     */
    public function get_users( WP_REST_Request $request ) {
        if ( ! current_user_can( 'list_users' ) ) {
            return new WP_Error( 'rest_forbidden', __( 'You cannot view users.', 'ahoi-api' ), [ 'status' => 403 ] );
        }
        $users = get_users( [ 'fields' => [ 'ID', 'user_login', 'user_email', 'display_name', 'roles' ] ] );
        return new WP_REST_Response( $users, 200 );
    }

    /**
     * Creează un utilizator nou (endpoint securizat pentru manageri).
     */
    public function create_user( WP_REST_Request $request ) {
        if ( ! current_user_can( 'create_users' ) ) {
            return new WP_Error( 'rest_forbidden', __( 'You cannot create users.', 'ahoi-api' ), [ 'status' => 403 ] );
        }

        $auth_controller = new Auth_Controller();
        // Reutilizăm logica de înregistrare, dar o protejăm cu o verificare de capabilități.
        return $auth_controller->register_user( $request );
    }
    
    /**
     * Obține o listă cu toate rolurile disponibile.
     */
    public function get_roles( WP_REST_Request $request ) {
        if ( ! current_user_can( 'edit_users' ) ) {
             return new WP_Error( 'rest_forbidden', __( 'You cannot view roles.', 'ahoi-api' ), [ 'status' => 403 ] );
        }
        global $wp_roles;
        return new WP_REST_Response( $wp_roles->get_names(), 200 );
    }
}