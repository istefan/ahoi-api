<?php
/**
 * Controller pentru gestionarea autentificării prin JWT.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Previne accesul direct.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clasa Auth_Controller.
 *
 * Gestionează endpoint-urile pentru generarea și validarea token-urilor JWT.
 */
class Auth_Controller {

    /**
     * Înregistrează un utilizator nou.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function register_user( WP_REST_Request $request ) {
        $username = sanitize_user( $request->get_param( 'username' ) );
        $email    = sanitize_email( $request->get_param( 'email' ) );
        $password = $request->get_param( 'password' );

        if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'ahoi_api_missing_fields', __( 'Username, email, and password are required.', 'ahoi-api' ), [ 'status' => 400 ] );
        }
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'ahoi_api_invalid_email', __( 'Invalid email address.', 'ahoi-api' ), [ 'status' => 400 ] );
        }
        if ( username_exists( $username ) ) {
            return new WP_Error( 'ahoi_api_username_exists', __( 'Username already exists.', 'ahoi-api' ), [ 'status' => 409 ] );
        }
        if ( email_exists( $email ) ) {
            return new WP_Error( 'ahoi_api_email_exists', __( 'Email address already in use.', 'ahoi-api' ), [ 'status' => 409 ] );
        }

        // Folosim funcția securizată WordPress pentru a crea utilizatorul.
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        // Setează rolul default (de ex. "Subscriber" sau un rol custom "Client")
        $user = new \WP_User( $user_id );
        $user->set_role( get_option('default_role', 'subscriber') );

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'User registered successfully.', 'ahoi-api' ),
            'user_id' => $user_id,
        ], 201 );
    }

    /**
     * Generează un token JWT pe baza credențialelor de utilizator.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Obiectul cererii.
     * @return WP_REST_Response|WP_Error Răspunsul cu token-ul sau o eroare.
     */
    public function generate_token( WP_REST_Request $request ) {
        $username = $request->get_param( 'username' );
        $password = $request->get_param( 'password' );

        if ( empty( $username ) || empty( $password ) ) {
            return new WP_Error(
                'ahoi_api_bad_request',
                __( 'Username and password are required.', 'ahoi-api' ),
                [ 'status' => 400 ]
            );
        }

        // Autentifică utilizatorul folosind sistemul WordPress.
        $user = wp_authenticate( $username, $password );

        if ( is_wp_error( $user ) ) {
            return new WP_Error(
                'ahoi_api_invalid_credentials',
                __( 'Invalid username or password.', 'ahoi-api' ),
                [ 'status' => 403 ]
            );
        }

        // Credențiale valide, generăm token-ul.
        $issued_at  = time();
        $expiration_time = $issued_at + ( DAY_IN_SECONDS * 7 ); // Token valabil 7 zile

        $payload = [
            'iss'  => get_bloginfo( 'url' ), // Emitentul token-ului
            'iat'  => $issued_at,            // Timpul la care a fost emis
            'nbf'  => $issued_at,            // Timpul de la care este valid (imediat)
            'exp'  => $expiration_time,      // Timpul de expirare
            'data' => [
                'user' => [
                    'id'    => $user->ID,
                    'roles' => $user->roles,
                ],
            ],
        ];

        // Semnează token-ul folosind cheia secretă din wp-config.php pentru securitate.
        $jwt = JWT::encode( $payload, SECURE_AUTH_KEY, 'HS256' );

        return new WP_REST_Response( [
            'success' => true,
            'token'   => $jwt,
            'user'    => [
                'id'           => $user->ID,
                'email'        => $user->user_email,
                'display_name' => $user->display_name,
            ],
        ], 200 );
    }

    /**
     * Validează token-ul trimis în header-ul Authorization.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Obiectul cererii.
     * @return WP_REST_Response
     */
    public function validate_token( WP_REST_Request $request ) {
        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Token is valid.', 'ahoi-api' ),
            'user_id' => get_current_user_id()
        ], 200 );
    }

    /**
     * Callback de permisiuni pentru validarea token-ului.
     * Aceasta este funcția cheie care va proteja endpoint-urile.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Obiectul cererii.
     * @return bool|WP_Error True dacă token-ul este valid, altfel WP_Error.
     */
    public function validate_token_permission( WP_REST_Request $request ) {
        $auth_header = $request->get_header( 'Authorization' );

        if ( ! $auth_header ) {
            return new WP_Error( 'ahoi_api_no_auth_header', __( 'Authorization header not found.', 'ahoi-api' ), [ 'status' => 401 ] );
        }

        // Extrage token-ul din header (ex: "Bearer <token>").
        list( $token ) = sscanf( $auth_header, 'Bearer %s' );

        if ( ! $token ) {
            return new WP_Error( 'ahoi_api_bad_auth_header', __( 'Malformed Authorization header.', 'ahoi-api' ), [ 'status' => 401 ] );
        }
        
        try {
            // Decodează și validează token-ul.
            // Funcția aruncă o excepție dacă token-ul este invalid (expirat, semnătură greșită etc.).
            $decoded = JWT::decode( $token, new Key( SECURE_AUTH_KEY, 'HS256' ) );
            
            // Verifică dacă ID-ul utilizatorului există în token.
            if ( ! isset( $decoded->data->user->id ) ) {
                return false;
            }
            
            $user_id = $decoded->data->user->id;
            
            // Setează utilizatorul curent în WordPress pe baza ID-ului din token.
            // Acest pas este VITAL pentru ca funcții precum `current_user_can()` să funcționeze corect.
            wp_set_current_user( $user_id );
            
            return true;

        } catch ( \Exception $e ) {
            // Token-ul este invalid.
            return new WP_Error( 'ahoi_api_invalid_token', $e->getMessage(), [ 'status' => 403 ] );
        }
        
        return false;
    }
}