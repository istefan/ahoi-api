<?php
/**
 * Controller for handling CRUD operations on dynamic data structures.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */
namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Dynamic_Crud_Controller.
 *
 * Contains the logic to fetch, create, update, and delete records
 * from the custom, dynamically generated data tables.
 */
class Dynamic_Crud_Controller {

    private $current_structure = null;

    // --- PERMISSION CHECK METHODS ---
    public function get_items_permissions_check( WP_REST_Request $request ) { return $this->check_jwt_permission( $request ); }
    public function get_item_permissions_check( WP_REST_Request $request ) { return $this->check_jwt_permission( $request ); }
    public function update_item_permissions_check( WP_REST_Request $request ) { return $this->check_jwt_permission( $request ); }
    public function delete_item_permissions_check( WP_REST_Request $request ) { return $this->check_jwt_permission( $request ); }
    
    public function create_item_permissions_check( WP_REST_Request $request ) {
        // First, check if the token is valid.
        $permission = $this->check_jwt_permission( $request );
        if ( is_wp_error( $permission ) || ! $permission ) {
            return $permission;
        }
        
        // Then, check for the specific capability.
        $structure = $this->get_structure_details( $request );
        if ( is_wp_error( $structure ) ) return $structure;

        // Dynamically build the capability: 'create_produse', 'create_facturi', etc.
        $capability = 'create_' . $structure['slug'];
        
        if ( ! current_user_can( $capability ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to create this item.', 'ahoi-api' ),
                [ 'status' => 403 ] // Forbidden
            );
        }

        return true;
    }

    // --- CRUD CALLBACK METHODS ---

    /**
     * Retrieves a list of items from a structure, with support for filtering, sorting, and pagination.
     * GET /ahoi/v1/{slug}?_sort=price&_order=desc&_limit=10&_page=1&category=phones
     */
    public function get_items( WP_REST_Request $request ) {
        $structure = $this->get_structure_details( $request );
        if ( is_wp_error( $structure ) ) {
            return $structure;
        }

        global $wpdb;
        $table_name = $this->get_table_name_from_slug( $structure['slug'] );
        $params = $request->get_params();

        // Dynamically and securely build the SQL query.
        $sql = "SELECT * FROM `{$table_name}`";
        $where_clauses = [];
        $query_params = [];
        
        // Filtering: treat any parameter not starting with '_' as a filter.
        foreach ( $params as $key => $value ) {
            if ( strpos( $key, '_' ) !== 0 ) {
                if ( in_array( $key, array_column( $structure['fields'], 'slug' ) ) ) {
                    // SECURITY: Sanitize the key before using it as a column name.
                    $safe_key = sanitize_key($key);
                    $where_clauses[] = "`" . $safe_key . "` = %s";
                    $query_params[] = $value;
                }
            }
        }
        if ( ! empty( $where_clauses ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
        }

        // Sorting
        $sortable_columns = array_merge(['id', 'created_at', 'updated_at', 'owner_id'], array_column($structure['fields'], 'slug'));
        $sort = ! empty( $params['_sort'] ) && in_array( $params['_sort'], $sortable_columns ) ? $params['_sort'] : 'id';
        // SECURITY: Sanitize the sort column.
        $safe_sort = sanitize_key($sort);
        $order = ! empty( $params['_order'] ) && in_array( strtoupper( $params['_order'] ), ['ASC', 'DESC'] ) ? strtoupper( $params['_order'] ) : 'ASC';
        $sql .= " ORDER BY `{$safe_sort}` {$order}";

        // Pagination
        $limit = isset( $params['_limit'] ) ? absint( $params['_limit'] ) : 20;
        $page = isset( $params['_page'] ) ? absint( $params['_page'] ) : 1;
        $offset = ( $page - 1 ) * $limit;
        $sql .= " LIMIT %d OFFSET %d";
        $query_params[] = $limit;
        $query_params[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ) );

        return new WP_REST_Response( $items, 200 );
    }

    /**
     * Retrieves a single item by ID.
     */
    public function get_item( WP_REST_Request $request ) {
        $structure = $this->get_structure_details( $request );
        if ( is_wp_error( $structure ) ) return $structure;
        $id = (int) $request['id'];
        global $wpdb;
        $table_name = $this->get_table_name_from_slug( $structure['slug'] );
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE id = %d", $id ) );
        if ( ! $item ) return new WP_Error( 'ahoi_api_not_found', __( 'Item not found.', 'ahoi-api' ), [ 'status' => 404 ] );
        return new WP_REST_Response( $item, 200 );
    }

    /**
     * Creates a new item.
     */
    public function create_item( WP_REST_Request $request ) {
        $structure = $this->get_structure_details( $request );
        if ( is_wp_error( $structure ) ) return $structure;

        $params = $request->get_json_params();
        $validated_data = $this->validate_and_sanitize_params( $params, $structure['fields'], 'create' );
        if ( is_wp_error( $validated_data ) ) return $validated_data;

        global $wpdb;
        $table_name = $this->get_table_name_from_slug( $structure['slug'] );
        
        $validated_data['owner_id'] = get_current_user_id();
        $validated_data['created_at'] = current_time( 'mysql' );
        $validated_data['updated_at'] = current_time( 'mysql' );
        
        $result = $wpdb->insert( $table_name, $validated_data );
        if ( false === $result ) return new WP_Error( 'ahoi_api_db_error', __( 'Could not insert item.', 'ahoi-api' ), [ 'status' => 500 ] );
        
        $new_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE id = %d", $wpdb->insert_id ) );
        
        if ($new_item) {
            $new_item->structure_slug = $structure['slug']; // Add slug for context
            ahoi_api_trigger_webhook( 'item.created', $new_item );
        }

        return new WP_REST_Response( $new_item, 201 );
    }

    /**
     * Updates an existing item.
     */
    public function update_item( WP_REST_Request $request ) {
        $structure = $this->get_structure_details( $request );
        if ( is_wp_error( $structure ) ) return $structure;
        
        $id = (int) $request['id'];
        $params = $request->get_json_params();
        $validated_data = $this->validate_and_sanitize_params( $params, $structure['fields'], 'update' );
        if ( is_wp_error( $validated_data ) ) return $validated_data;
        
        if ( empty( $validated_data ) ) return new WP_Error( 'ahoi_api_bad_request', __( 'No valid data provided for update.', 'ahoi-api' ), [ 'status' => 400 ] );

        global $wpdb;
        $table_name = $this->get_table_name_from_slug( $structure['slug'] );
        
        $validated_data['updated_at'] = current_time( 'mysql' );
        
        $result = $wpdb->update( $table_name, $validated_data, [ 'id' => $id ] );
        if ( false === $result ) return new WP_Error( 'ahoi_api_db_error', __( 'Could not update item.', 'ahoi-api' ), [ 'status' => 500 ] );
        
        $updated_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE id = %d", $id ) );
        
        if ($updated_item) {
            $updated_item->structure_slug = $structure['slug']; // Add slug for context
            ahoi_api_trigger_webhook( 'item.updated', $updated_item );
        }

        return new WP_REST_Response( $updated_item, 200 );
    }

    /**
     * Deletes an item.
     */
    public function delete_item( WP_REST_Request $request ) {
        $structure = $this->get_structure_details( $request );
        if ( is_wp_error( $structure ) ) return $structure;
        $id = (int) $request['id'];
        global $wpdb;
        $table_name = $this->get_table_name_from_slug( $structure['slug'] );

        $item_to_delete = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE id = %d", $id ) );
        
        $result = $wpdb->delete( $table_name, [ 'id' => $id ] );
        if ( ! $result ) return new WP_Error( 'ahoi_api_not_found', __( 'Item not found or could not be deleted.', 'ahoi-api' ), [ 'status' => 404 ] );
        
        if ( $item_to_delete ) {
            $item_to_delete->structure_slug = $structure['slug']; // Add slug for context
            ahoi_api_trigger_webhook( 'item.deleted', $item_to_delete );
        }

        return new WP_REST_Response( [ 'success' => true, 'message' => 'Item deleted.' ], 200 );
    }


    // --- HELPER METHODS ---

    /**
     * Validates and sanitizes input parameters based on the field definitions.
     */
    private function validate_and_sanitize_params( $params, $fields, $context = 'create' ) {
        $sanitized_data = [];
        $fields_by_slug = array_column( $fields, null, 'slug' );

        if ( 'create' === $context ) {
            foreach ( $fields as $field ) {
                if ( $field->is_required && ! isset( $params[ $field->slug ] ) ) {
                    return new WP_Error( 'ahoi_api_missing_param', sprintf( __( 'Required field "%s" is missing.', 'ahoi-api' ), $field->name ), [ 'status' => 400 ] );
                }
            }
        }
        
        foreach ( $params as $key => $value ) {
            if ( isset( $fields_by_slug[ $key ] ) ) {
                $field_type = $fields_by_slug[ $key ]->type;
                switch ( $field_type ) {
                    case 'TEXT_SHORT':     $sanitized_data[ $key ] = sanitize_text_field( $value ); break;
                    case 'TEXT_LONG':      $sanitized_data[ $key ] = sanitize_textarea_field( $value ); break;
                    case 'NUMBER_INT':     $sanitized_data[ $key ] = intval( $value ); break;
                    case 'NUMBER_DECIMAL': $sanitized_data[ $key ] = floatval( $value ); break;
                    case 'BOOLEAN':        $sanitized_data[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN ); break;
                    case 'DATETIME':
                    case 'DATE':           $sanitized_data[ $key ] = sanitize_text_field( $value ); break;
                    case 'RELATIONSHIP':   $sanitized_data[ $key ] = absint( $value ); break;
                    case 'JSON':
                        if ( is_string( $value ) ) {
                            json_decode( $value );
                            if ( json_last_error() === JSON_ERROR_NONE ) { $sanitized_data[ $key ] = $value; }
                        } elseif ( is_array( $value ) || is_object( $value ) ) {
                            $sanitized_data[ $key ] = wp_json_encode( $value );
                        }
                        break;
                    default:
                        $sanitized_data[ $key ] = sanitize_text_field( $value );
                }
            }
        }
        
        return $sanitized_data;
    }

    private function check_jwt_permission( WP_REST_Request $request ) {
        $auth_controller = new Auth_Controller();
        return $auth_controller->validate_token_permission( $request );
    }

    private function get_table_name_from_slug( $slug ) {
        global $wpdb;
        $safe_slug = preg_replace( '/[^a-zA-Z0-9_]/', '', $slug );
        return $wpdb->prefix . 'ahoi_data_' . $safe_slug;
    }

    /**
     * Retrieves the structure details (including fields) based on the current request.
     */
    private function get_structure_details( WP_REST_Request $request ) {
        if ( $this->current_structure !== null ) return $this->current_structure;
        
        $route = $request->get_route();
        $parts = explode( '/', trim( $route, '/' ) );
        $slug = $parts[2] ?? null;

        if ( ! $slug ) return new WP_Error( 'ahoi_api_invalid_route', __( 'Could not determine structure from route.', 'ahoi-api' ), [ 'status' => 500 ] );

        global $wpdb;
        $structures_table = $wpdb->prefix . 'ahoi_api_structures';
        $fields_table = $wpdb->prefix . 'ahoi_api_fields';
        
        $structure = $wpdb->get_row( $wpdb->prepare( "SELECT id, slug FROM `{$structures_table}` WHERE slug = %s", $slug ), ARRAY_A );
        if ( ! $structure ) return new WP_Error( 'ahoi_api_structure_not_found', __( 'Structure not found.', 'ahoi-api' ), [ 'status' => 404 ] );

        $structure['fields'] = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$fields_table}` WHERE structure_id = %d", $structure['id'] ) );

        $this->current_structure = $structure;
        return $this->current_structure;
    }
}