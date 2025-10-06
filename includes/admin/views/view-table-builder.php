<?php
/**
 * The view for the "Table Builder" page.
 * Contains the logic and display for managing data tables and their fields.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================================================================
// PRE-PROCESSING AND INITIALIZATION
// ==========================================================================
global $wpdb;
$tables_table     = $wpdb->prefix . 'ahoi_api_structures'; // Table name remains for backward compatibility
$fields_table     = $wpdb->prefix . 'ahoi_api_fields';
$schema_manager   = new Ahoi_API\Database\Schema_Manager();
$messages         = [];
$action           = $_GET['action'] ?? 'list';
$table_id         = isset( $_GET['table_id'] ) ? absint( $_GET['table_id'] ) : 0;

// ==========================================================================
// ROUTE ACTIONS (FORM PROCESSING LOGIC)
// ==========================================================================

// --- Actions for the table LISTING page ---
if ( 'list' === $action ) {
    // Logic for DELETING a table
    if ( isset( $_GET['sub_action'], $_GET['_wpnonce'] ) && 'delete' === $_GET['sub_action'] ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'ahoi_delete_table_' . $table_id ) ) {
            $table_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM $tables_table WHERE id = %d", $table_id ) );
            if ( $table_slug ) {
                $schema_manager->delete_table_for_structure( $table_slug );
                $wpdb->delete( $tables_table, [ 'id' => $table_id ] );
                $wpdb->delete( $fields_table, [ 'structure_id' => $table_id ] );
                $messages[] = [ 'type' => 'success', 'text' => __( 'Table deleted successfully.', 'ahoi-api' ) ];
            }
        }
    }
    // Logic for CREATING a new table
    if ( isset( $_POST['action'] ) && 'ahoi_create_table' === $_POST['action'] ) {
        if ( wp_verify_nonce( $_POST['_wpnonce'], 'ahoi_create_table_nonce' ) ) {
            $name = sanitize_text_field( $_POST['table_name'] );
            $slug = sanitize_title( $_POST['table_slug'] );
            if ( ! empty( $name ) && ! empty( $slug ) ) {
                $wpdb->insert( $tables_table, [ 'name' => $name, 'slug' => $slug, 'created_at' => current_time( 'mysql' ) ] );
                $table_creation_result = $schema_manager->create_table_for_structure( $slug );
                if ( is_wp_error( $table_creation_result ) ) {
                    $wpdb->delete( $tables_table, [ 'slug' => $slug ] );
                    $messages[] = [ 'type' => 'error', 'text' => sanitize_text_field($table_creation_result->get_error_message()) ];
                } else {
                    $messages[] = [ 'type' => 'success', 'text' => __( 'Table created successfully.', 'ahoi-api' ) ];
                }
            } else {
                $messages[] = [ 'type' => 'error', 'text' => __( 'Table Name and Slug are required.', 'ahoi-api' ) ];
            }
        }
    }
}

// --- Actions for the field EDITING page ---
if ( 'edit' === $action ) {
    $field_id = isset( $_GET['field_id'] ) ? absint( $_GET['field_id'] ) : 0;
    // Logic for DELETING a field
    if ( isset( $_GET['sub_action'], $_GET['_wpnonce'] ) && 'delete_field' === $_GET['sub_action'] ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'ahoi_delete_field_' . $field_id ) ) {
            $field   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $fields_table WHERE id = %d", $field_id ) );
            $table_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tables_table WHERE id = %d", $field->structure_id ) );
            if ( $field && $table_obj ) {
                $result = $schema_manager->drop_column_from_table( $table_obj->slug, $field->slug );
                if ( ! is_wp_error( $result ) ) {
                    $wpdb->delete( $fields_table, [ 'id' => $field_id ] );
                    $messages[] = [ 'type' => 'success', 'text' => __( 'Field deleted successfully.', 'ahoi-api' ) ];
                } else {
                    $messages[] = [ 'type' => 'error', 'text' => sanitize_text_field($result->get_error_message()) ];
                }
            }
        }
    }
    // Logic for ADDING a new field
    if ( isset( $_POST['action'] ) && 'ahoi_create_field' === $_POST['action'] ) {
        if ( wp_verify_nonce( $_POST['_wpnonce'], 'ahoi_create_field_nonce' ) ) {
            $reserved_keywords = [
                'select', 'insert', 'update', 'delete', 'where', 'from', 'table', 'database', 'text', 'longtext',
                'int', 'integer', 'varchar', 'char', 'decimal', 'float', 'key', 'primary', 'index', 'foreign',
                'order', 'group', 'by', 'as', 'on', 'date', 'datetime', 'timestamp', 'boolean', 'true', 'false', 'null'
            ];
            
            // CORRECTION: Sanitize slug and replace hyphens with underscores for SQL compatibility.
            $raw_slug = sanitize_title( $_POST['field_slug'] );
            $field_slug = str_replace('-', '_', $raw_slug);

            if ( in_array( strtolower( $field_slug ), $reserved_keywords, true ) ) {
                $messages[] = [ 'type' => 'error', 'text' => sprintf( __( 'The slug "%s" is a reserved keyword. Please choose another one.', 'ahoi-api' ), $field_slug ) ];
            } else {
                $field = [
                    'name'         => sanitize_text_field( $_POST['field_name'] ),
                    'slug'         => $field_slug, // Use the corrected slug
                    'type'         => sanitize_text_field( $_POST['field_type'] ),
                    'is_required'  => isset( $_POST['field_is_required'] ) ? 1 : 0,
                    'structure_id' => $table_id,
                ];
                if ( ! empty( $field['name'] ) && ! empty( $field['slug'] ) ) {
                    $table_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tables_table WHERE id = %d", $table_id ) );
                    $result    = $schema_manager->add_column_to_table( $table_obj->slug, $field );
                    if ( ! is_wp_error( $result ) ) {
                        $wpdb->insert( $fields_table, $field );
                        $messages[] = [ 'type' => 'success', 'text' => __( 'Field created successfully.', 'ahoi-api' ) ];
                    } else {
                        $messages[] = [ 'type' => 'error', 'text' => sanitize_text_field($result->get_error_message()) ];
                    }
                } else {
                    $messages[] = [ 'type' => 'error', 'text' => __( 'Field Name and Slug are required.', 'ahoi-api' ) ];
                }
            }
        }
    }
}

// ==========================================================================
// HTML DISPLAY
// ==========================================================================
?>
<div class="wrap ahoi-api-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php foreach ( $messages as $message ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> is-dismissible"><p><?php echo esc_html( $message['text'] ); ?></p></div>
    <?php endforeach; ?>

    <?php if ( 'edit' === $action && $table_id > 0 ) : ?>
        <?php // =================== DISPLAY FIELD EDITING PAGE ===================
            $table_obj       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tables_table WHERE id = %d", $table_id ) );
            $existing_fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $fields_table WHERE structure_id = %d ORDER BY name ASC", $table_id ) );
            $field_types     = [
                'TEXT_SHORT'     => __( 'Text (short, max 255 chars)', 'ahoi-api' ), 'TEXT_LONG' => __( 'Text (long)', 'ahoi-api' ), 'NUMBER_INT' => __( 'Number (integer)', 'ahoi-api' ),
                'NUMBER_DECIMAL' => __( 'Number (decimal)', 'ahoi-api' ), 'BOOLEAN' => __( 'Boolean (true/false)', 'ahoi-api' ), 'DATETIME' => __( 'Date and Time', 'ahoi-api' ),
                'DATE' => __( 'Date', 'ahoi-api' ), 'RELATIONSHIP' => __( 'Relationship (stores an ID)', 'ahoi-api' ), 'JSON' => __( 'JSON (stores structured data)', 'ahoi-api' ),
            ];
        ?>
        <h2><?php printf( esc_html__( 'Editing Fields for "%s" Table', 'ahoi-api' ), $table_obj->name ); ?></h2>
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=ahoi-api' ) ); ?>">&larr; <?php esc_html_e( 'Back to all tables', 'ahoi-api' ); ?></a></p>

        <div class="ahoi-columns-2">
            <div class="postbox">
                <h2 class="hndle"><span><?php esc_html_e( 'Add New Field', 'ahoi-api' ); ?></span></h2>
                <div class="inside">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ahoi-api&action=edit&table_id=' . $table_id ) ); ?>">
                        <?php wp_nonce_field( 'ahoi_create_field_nonce', '_wpnonce' ); ?>
                        <input type="hidden" name="action" value="ahoi_create_field">
                        <div class="form-field"><label for="field_name"><?php esc_html_e( 'Field Name', 'ahoi-api' ); ?></label><input type="text" name="field_name" id="field_name" required></div>
                        <div class="form-field"><label for="field_slug"><?php esc_html_e( 'Field Slug', 'ahoi-api' ); ?></label><input type="text" name="field_slug" id="field_slug" required></div>
                        <div class="form-field"><label for="field_type"><?php esc_html_e( 'Field Type', 'ahoi-api' ); ?></label><select name="field_type" id="field_type"><?php foreach ( $field_types as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
                        <div class="form-field"><label><input type="checkbox" name="field_is_required" value="1"> <?php esc_html_e( 'Is this field required?', 'ahoi-api' ); ?></label></div>
                        <?php submit_button( __( 'Add New Field', 'ahoi-api' ) ); ?>
                    </form>
                </div>
            </div>
             <div class="postbox">
                <h2 class="hndle"><span><?php esc_html_e( 'Existing Fields', 'ahoi-api' ); ?></span></h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th><?php esc_html_e( 'Name', 'ahoi-api' ); ?></th><th><?php esc_html_e( 'Slug', 'ahoi-api' ); ?></th><th><?php esc_html_e( 'Type', 'ahoi-api' ); ?></th><th><?php esc_html_e( 'Required', 'ahoi-api' ); ?></th></tr></thead>
                        <tbody>
                            <?php if ( empty( $existing_fields ) ) : ?>
                                <tr><td colspan="4"><?php esc_html_e( 'No custom fields found for this table.', 'ahoi-api' ); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ( $existing_fields as $field ) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html( $field->name ); ?></strong>
                                            <div class="row-actions"><span class="delete"><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ahoi-api&action=edit&table_id=' . $table_id . '&sub_action=delete_field&field_id=' . $field->id ), 'ahoi_delete_field_' . $field->id ) ); ?>" class="text-danger" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this field? This action cannot be undone.', 'ahoi-api' ); ?>');"><?php esc_html_e( 'Delete', 'ahoi-api' ); ?></a></span></div>
                                        </td>
                                        <td><code><?php echo esc_html( $field->slug ); ?></code></td>
                                        <td><?php echo esc_html( $field_types[ $field->type ] ?? $field->type ); ?></td>
                                        <td><?php echo $field->is_required ? __( 'Yes', 'ahoi-api' ) : __( 'No', 'ahoi-api' ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else : ?>
        <?php // =================== DISPLAY TABLE LIST (DEFAULT) ===================
            $existing_tables = $wpdb->get_results( "SELECT * FROM {$tables_table} ORDER BY name ASC" );
        ?>
        <p><?php esc_html_e( 'Manage your data tables. Each table you create will automatically generate REST API endpoints.', 'ahoi-api' ); ?></p>
        <div class="ahoi-columns-2">
            <div class="postbox">
                <h2 class="hndle"><span><?php esc_html_e( 'Add New Table', 'ahoi-api' ); ?></span></h2>
                <div class="inside">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ahoi-api' ) ); ?>">
                        <?php wp_nonce_field( 'ahoi_create_table_nonce', '_wpnonce' ); ?>
                        <input type="hidden" name="action" value="ahoi_create_table">
                        <div class="form-field"><label for="table_name"><?php esc_html_e( 'Table Name', 'ahoi-api' ); ?></label><input type="text" name="table_name" id="table_name" required><p class="description"><?php esc_html_e( 'The name is how it appears in the admin area (e.g., "Movies", "Products").', 'ahoi-api' ); ?></p></div>
                        <div class="form-field"><label for="table_slug"><?php esc_html_e( 'Table Slug', 'ahoi-api' ); ?></label><input type="text" name="table_slug" id="table_slug" required><p class="description"><?php esc_html_e( 'The slug is the URL-friendly version used in the API endpoint (e.g., "movies", "products").', 'ahoi-api' ); ?></p></div>
                        <?php submit_button( __( 'Add New Table', 'ahoi-api' ) ); ?>
                    </form>
                </div>
            </div>
            <div class="postbox">
                 <h2 class="hndle"><span><?php esc_html_e( 'Existing Tables', 'ahoi-api' ); ?></span></h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th><?php esc_html_e( 'Name', 'ahoi-api' ); ?></th><th><?php esc_html_e( 'Slug', 'ahoi-api' ); ?></th><th><?php esc_html_e( 'API Endpoint', 'ahoi-api' ); ?></th></tr></thead>
                        <tbody>
                            <?php if ( empty( $existing_tables ) ) : ?>
                                <tr><td colspan="3"><?php esc_html_e( 'No tables found.', 'ahoi-api' ); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ( $existing_tables as $table ) : ?>
                                    <tr>
                                        <td><strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=ahoi-api&action=edit&table_id=' . $table->id ) ); ?>"><?php echo esc_html( $table->name ); ?></a></strong>
                                            <div class="row-actions">
                                                <span class="edit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=ahoi-api&action=edit&table_id=' . $table->id ) ); ?>"><?php esc_html_e( 'Edit Fields', 'ahoi-api' ); ?></a> | </span>
                                                <span class="delete"><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ahoi-api&sub_action=delete&table_id=' . $table->id ), 'ahoi_delete_table_' . $table->id ) ); ?>" class="text-danger" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this table and all its data? This action cannot be undone.', 'ahoi-api' ); ?>');"><?php esc_html_e( 'Delete', 'ahoi-api' ); ?></a></span>
                                            </div>
                                        </td>
                                        <td><code><?php echo esc_html( $table->slug ); ?></code></td>
                                        <td><code>/wp-json/ahoi/v1/<?php echo esc_html( $table->slug ); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>