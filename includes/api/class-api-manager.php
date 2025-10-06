<?php
/**
 * Managerul principal pentru înregistrarea rutelor API.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

namespace Ahoi_API\Api;

// Previne accesul direct.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clasa Api_Manager.
 *
 * Punctul central pentru înregistrarea tuturor rutelor REST API ale pluginului.
 */
class Api_Manager {

    /**
     * Namespace-ul pentru API.
     *
     * @var string
     */
    protected $namespace = 'ahoi/v1';

    /**
     * Constructorul clasei.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->register_static_routes();
        $this->register_dynamic_routes();
        
        // ==================================================================
        // NEW CODE: Add CORS headers to all our API responses.
        // ==================================================================
        add_filter( 'rest_pre_serve_request', [ $this, 'add_cors_headers' ], 10, 4 );
    }
    
    /**
     * ==================================================================
     * NEW METHOD
     * Adds CORS headers to the response for our API namespace.
     * This allows JavaScript applications from other domains to access the API.
     * ==================================================================
     *
     * @param bool                  $served  Whether the request has already been served.
     * @param \WP_REST_Response     $result  Result to send to the client.
     * @param \WP_REST_Request      $request Request used to generate the response.
     * @param \WP_REST_Server       $server  Server instance.
     * @return bool
     */
    public function add_cors_headers( $served, $result, $request, $server ) {
        // Only apply CORS for our own API namespace.
        if ( strpos( $request->get_route(), "/{$this->namespace}/" ) === false ) {
            return $served;
        }

        // Get the "Origin" header from the incoming request.
        $origin = $request->get_header( 'origin' );

        if ( $origin ) {
            // Get the list of allowed domains from the settings we saved.
            $options = get_option( 'ahoi_api_options' );
            $allowed_origins = ! empty( $options['allowed_origins'] ) ? explode( "\n", $options['allowed_origins'] ) : [];
            
            // Clean up the array of any empty lines.
            $allowed_origins = array_map( 'trim', $allowed_origins );
            $allowed_origins = array_filter( $allowed_origins );
            
            // If the request's origin is in our allowed list, add the header.
            if ( in_array( $origin, $allowed_origins ) ) {
                $server->send_header( 'Access-Control-Allow-Origin', $origin );
                $server->send_header( 'Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE' );
                $server->send_header( 'Access-Control-Allow-Credentials', 'true' );
            }
        }
        
        return $served;
    }

    /**
     * Înregistrează rutele API statice (care nu depind de structurile create de utilizator).
     *
     * @since 1.0.0
     */
    protected function register_static_routes() {
        // ... (rest of the function is unchanged)
        $auth_controller = new Auth_Controller();

        // Ruta pentru obținerea unui token JWT: POST /wp-json/ahoi/v1/token
        register_rest_route( $this->namespace, '/token', [
            'methods'  => \WP_REST_Server::CREATABLE, // 'POST'
            'callback' => [ $auth_controller, 'generate_token' ],
            'permission_callback' => '__return_true', // Oricine poate încerca să se logheze.
        ]);

        // Ruta pentru validarea unui token: GET /wp-json/ahoi/v1/token/validate
        register_rest_route( $this->namespace, '/token/validate', [
            'methods'  => \WP_REST_Server::READABLE, // 'GET'
            'callback' => [ $auth_controller, 'validate_token' ],
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ], // Necesită un token valid.
        ]);


        // Ruta pentru înregistrarea unui utilizator nou: POST /wp-json/ahoi/v1/register
        register_rest_route( $this->namespace, '/register', [
            'methods'  => \WP_REST_Server::CREATABLE, // 'POST'
            'callback' => [ $auth_controller, 'register_user' ],
            'permission_callback' => '__return_true', // Oricine poate încerca să se înregistreze.
        ]);

        // NOU: Rute pentru managementul utilizatorilor
        $user_controller = new User_Controller();

        // GET /wp-json/ahoi/v1/users - Obține lista de utilizatori
        register_rest_route( $this->namespace, '/users', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [ $user_controller, 'get_users' ],
            // Doar cine e autentificat și are permisiuni poate apela asta
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ],
        ]);
        
        // POST /wp-json/ahoi/v1/users - Creează un utilizator nou (cale securizată)
        register_rest_route( $this->namespace, '/users', [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => [ $user_controller, 'create_user' ],
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ],
        ]);

        // GET /wp-json/ahoi/v1/roles - Obține lista de roluri
        register_rest_route( $this->namespace, '/roles', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [ $user_controller, 'get_roles' ],
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ],
        ]);
        
        // NOU: Instanțiem noul controller de stocare
        $storage_controller = new Storage_Controller();

        // NOU: Înregistrăm endpoint-ul pentru upload de fișiere
        // POST /wp-json/ahoi/v1/storage/upload
        register_rest_route( $this->namespace, '/storage/upload', [
            'methods'  => \WP_REST_Server::CREATABLE, // 'POST'
            'callback' => [ $storage_controller, 'handle_upload' ],
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ],
        ]);

        // NOU: Înregistrăm endpoint-ul pentru ștergerea fișierelor
        // DELETE /wp-json/ahoi/v1/storage/{id}
        register_rest_route( $this->namespace, '/storage/(?P<id>[\d]+)', [
            'methods'  => \WP_REST_Server::DELETABLE, // 'DELETE'
            'callback' => [ $storage_controller, 'handle_delete' ],
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ],
        ]);

        // NOU: Instanțiem controller-ul de notificări
        $notification_controller = new Notification_Controller();

        // NOU: Înregistrăm endpoint-ul pentru trimiterea de email-uri
        // POST /wp-json/ahoi/v1/notifications/email
        register_rest_route( $this->namespace, '/notifications/email', [
            'methods'  => \WP_REST_Server::CREATABLE, // 'POST'
            'callback' => [ $notification_controller, 'send_email' ],
            'permission_callback' => [ $auth_controller, 'validate_token_permission' ],
        ]);

        // In register_static_routes() in class-api-manager.php
        $profile_controller = new Profile_Controller();

        // GET /wp-json/ahoi/v1/users/me (Get my own profile)
        register_rest_route($this->namespace, '/users/me', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [$profile_controller, 'get_my_profile'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);

        // PUT /wp-json/ahoi/v1/users/me (Update my own profile)
        register_rest_route($this->namespace, '/users/me', [
            'methods'  => \WP_REST_Server::EDITABLE,
            'callback' => [$profile_controller, 'update_my_profile'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);

    }

    /**
     * Înregistrează rutele API dinamice, bazate pe structurile definite în baza de date.
     *
     * @since 1.0.0
     */
    protected function register_dynamic_routes() {
        // ... (rest of the function is unchanged)
        global $wpdb;

        $structures_table = $wpdb->prefix . 'ahoi_api_structures';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$structures_table'" ) !== $structures_table ) {
            return;
        }
        $structures = $wpdb->get_results( "SELECT slug FROM $structures_table" );
        if ( empty( $structures ) ) {
            return;
        }
        $crud_controller = new Dynamic_Crud_Controller();
        foreach ( $structures as $structure ) {
            $slug = $structure->slug;
            register_rest_route( $this->namespace, '/' . $slug, [
                [ 'methods'  => \WP_REST_Server::READABLE, 'callback' => [ $crud_controller, 'get_items' ], 'permission_callback' => [ $crud_controller, 'get_items_permissions_check' ], ],
                [ 'methods'  => \WP_REST_Server::CREATABLE, 'callback' => [ $crud_controller, 'create_item' ], 'permission_callback' => [ $crud_controller, 'create_item_permissions_check' ], ],
            ]);
            register_rest_route( $this->namespace, '/' . $slug . '/(?P<id>[\d]+)', [
                [ 'methods'  => \WP_REST_Server::READABLE, 'callback' => [ $crud_controller, 'get_item' ], 'permission_callback' => [ $crud_controller, 'get_item_permissions_check' ], ],
                [ 'methods'  => \WP_REST_Server::EDITABLE, 'callback' => [ $crud_controller, 'update_item' ], 'permission_callback' => [ $crud_controller, 'update_item_permissions_check' ], ],
                [ 'methods'  => \WP_REST_Server::DELETABLE, 'callback' => [ $crud_controller, 'delete_item' ], 'permission_callback' => [ $crud_controller, 'delete_item_permissions_check' ], ],
            ]);
        }
    }
}