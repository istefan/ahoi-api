<?php
/**
 * The main manager for registering all API routes.
 *
 * @link      https://www.ahoi.ro/
 * @since     1.0.0
 * @package   Ahoi_API
 */

namespace Ahoi_API\Api;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The central hub for registering all of the plugin's REST API routes.
 */
class Api_Manager {

    /**
     * The namespace for the API.
     * @var string
     */
    protected $namespace = 'ahoi/v1';

    /**
     * The class constructor.
     * @since 1.0.0
     */
    public function __construct() {
        $this->register_static_routes();
        $this->register_dynamic_routes();

        // Add CORS headers to all of our API responses.
        add_filter('rest_pre_serve_request', [$this, 'add_cors_headers'], 10, 4);
    }

    /**
     * Adds CORS headers to the response for our API namespace.
     * This allows JavaScript applications from other domains to access the API.
     */
    public function add_cors_headers($served, $result, $request, $server) {
        // Only apply CORS for our own API namespace.
        if (strpos($request->get_route(), "/{$this->namespace}/") === false) {
            return $served;
        }

        $origin = $request->get_header('origin');
        if ($origin) {
            $options = get_option('ahoi_api_options');
            $allowed_origins = !empty($options['allowed_origins']) ? explode("\n", $options['allowed_origins']) : [];
            
            // Clean up the array of any empty lines.
            $allowed_origins = array_map('trim', $allowed_origins);
            $allowed_origins = array_filter($allowed_origins);

            // If the request's origin is in our allowed list, add the headers.
            if (in_array($origin, $allowed_origins)) {
                $server->send_header('Access-Control-Allow-Origin', $origin);
                $server->send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE');
                $server->send_header('Access-Control-Allow-Credentials', 'true');
            }
        }

        return $served;
    }

    /**
     * Registers the static API routes (those that don't depend on user-created structures).
     * @since 1.0.0
     */
    protected function register_static_routes() {
        $auth_controller = new Auth_Controller();
        $user_controller = new User_Controller();
        $storage_controller = new Storage_Controller();
        $notification_controller = new Notification_Controller();

        // --- Authentication Routes ---
        register_rest_route($this->namespace, '/token', [
            'methods'  => \WP_REST_Server::CREATABLE, // 'POST'
            'callback' => [$auth_controller, 'generate_token'],
        ]);
        register_rest_route($this->namespace, '/token/validate', [
            'methods'  => \WP_REST_Server::READABLE, // 'GET'
            'callback' => [$auth_controller, 'validate_token'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);
        register_rest_route($this->namespace, '/register', [
            'methods'  => \WP_REST_Server::CREATABLE, // 'POST'
            'callback' => [$auth_controller, 'register_user'],
        ]);

        // --- User & Role Management Routes ---
        register_rest_route($this->namespace, '/users', [
            [
                'methods'  => \WP_REST_Server::READABLE, // GET /users
                'callback' => [$user_controller, 'get_users'],
                'permission_callback' => [$auth_controller, 'validate_token_permission'],
            ],
            [
                'methods'  => \WP_REST_Server::CREATABLE, // POST /users
                'callback' => [$user_controller, 'create_user'],
                'permission_callback' => [$auth_controller, 'validate_token_permission'],
            ]
        ]);
        
        // NEW: Route to get a single user by ID
        register_rest_route($this->namespace, '/users/(?P<id>[\d]+)', [
            'methods'  => \WP_REST_Server::READABLE, // GET /users/{id}
            'callback' => [$user_controller, 'get_user'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);

        // NEW: Route to update a single user by ID
        register_rest_route($this->namespace, '/users/(?P<id>[\d]+)', [
            'methods'  => \WP_REST_Server::EDITABLE, // PUT /users/{id}
            'callback' => [$user_controller, 'update_user'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);

        register_rest_route($this->namespace, '/roles', [
            'methods'  => \WP_REST_Server::READABLE, // GET /roles
            'callback' => [$user_controller, 'get_roles'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);

        // --- Storage Routes ---
        register_rest_route($this->namespace, '/storage/upload', [
            'methods'  => \WP_REST_Server::CREATABLE, // POST
            'callback' => [$storage_controller, 'handle_upload'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);
        register_rest_route($this->namespace, '/storage/(?P<id>[\d]+)', [
            'methods'  => \WP_REST_Server::DELETABLE, // DELETE
            'callback' => [$storage_controller, 'handle_delete'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);

        // --- Notification Routes ---
        register_rest_route($this->namespace, '/notifications/email', [
            'methods'  => \WP_REST_Server::CREATABLE, // POST
            'callback' => [$notification_controller, 'send_email'],
            'permission_callback' => [$auth_controller, 'validate_token_permission'],
        ]);
    }

    /**
     * Registers the dynamic API routes, based on the structures defined in the database.
     * @since 1.0.0
     */
    protected function register_dynamic_routes() {
        global $wpdb;
        $structures_table = $wpdb->prefix . 'ahoi_api_structures';

        // Check if the management table exists before querying it.
        if ($wpdb->get_var("SHOW TABLES LIKE '$structures_table'") != $structures_table) {
            return;
        }

        $structures = $wpdb->get_results("SELECT slug FROM {$structures_table}");
        if (empty($structures)) {
            return;
        }

        $crud_controller = new Dynamic_Crud_Controller();

        foreach ($structures as $structure) {
            $slug = $structure->slug;

            // Register routes for the collection (e.g., /products)
            register_rest_route($this->namespace, '/' . $slug, [
                [
                    'methods'  => \WP_REST_Server::READABLE,
                    'callback' => [$crud_controller, 'get_items'],
                    'permission_callback' => [$crud_controller, 'get_items_permissions_check'],
                ],
                [
                    'methods'  => \WP_REST_Server::CREATABLE,
                    'callback' => [$crud_controller, 'create_item'],
                    'permission_callback' => [$crud_controller, 'create_item_permissions_check'],
                ],
            ]);

            // Register routes for a single item (e.g., /products/123)
            register_rest_route($this->namespace, '/' . $slug . '/(?P<id>[\d]+)', [
                [
                    'methods'  => \WP_REST_Server::READABLE,
                    'callback' => [$crud_controller, 'get_item'],
                    'permission_callback' => [$crud_controller, 'get_item_permissions_check'],
                ],
                [
                    'methods'  => \WP_REST_Server::EDITABLE, // PUT/PATCH
                    'callback' => [$crud_controller, 'update_item'],
                    'permission_callback' => [$crud_controller, 'update_item_permissions_check'],
                ],
                [
                    'methods'  => \WP_REST_Server::DELETABLE, // DELETE
                    'callback' => [$crud_controller, 'delete_item'],
                    'permission_callback' => [$crud_controller, 'delete_item_permissions_check'],
                ],
            ]);
        }
    }
}