<?php
/**
 * Gestionează instalarea și dezinstalarea pluginului.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

namespace Ahoi_API;

// Previne accesul direct.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clasa Installer.
 */
class Installer {

    /**
     * Metoda principală care rulează la activarea pluginului.
     *
     * @since 1.0.0
     */
    public static function activate() {
        self::create_tables();
        
        // NOU: Apelăm metoda pentru crearea rolului de Manager.
        self::create_manager_role();
        
        self::set_version();
        flush_rewrite_rules();
    }

    /**
     * Metoda care rulează la dezactivarea pluginului.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * NOU: Metoda pentru crearea rolului custom de "Manager".
     *
     * @since 1.0.0
     * @access private
     */
    private static function create_manager_role() {
        // Grant basic API access to all standard roles.
        $roles_to_get_basic_access = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
        foreach ($roles_to_get_basic_access as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('use_ahoi_api', true);
            }
        }

        // Define Manager capabilities
        $manager_capabilities = [
            'read' => true,
            'list_users' => true,
            'create_users' => true,
            'edit_users' => true,
            'use_ahoi_api' => true,
            'manage_ahoi_api_all_data' => true,
            'send_api_emails' => true,
            'manage_api_users' => true, // <-- NEW CAPABILITY
        ];

        // Create or update the Manager role
        if (get_role('manager')) {
            remove_role('manager'); // Remove to redefine cleanly
        }
        add_role('manager', __('Manager', 'ahoi-api'), $manager_capabilities);

        // Ensure Admin also has manager capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_ahoi_api_all_data', true);
            $admin_role->add_cap('send_api_emails', true);
            $admin_role->add_cap('manage_api_users', true);
        }
    }

    /**
     * Creează tabelele personalizate necesare pluginului.
     *
     * @since 1.0.0
     * @access private
     */
    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table_structures = $wpdb->prefix . 'ahoi_api_structures';
        $table_fields     = $wpdb->prefix . 'ahoi_api_fields';
        $table_webhooks   = $wpdb->prefix . 'ahoi_api_webhooks';

        $sql_structures = "CREATE TABLE $table_structures (id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description TEXT, created_at DATETIME NOT NULL, PRIMARY KEY  (id), UNIQUE KEY slug (slug)) $charset_collate;";
        $sql_fields = "CREATE TABLE $table_fields (id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, structure_id BIGINT(20) UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, is_required BOOLEAN NOT NULL DEFAULT 0, default_value VARCHAR(255), PRIMARY KEY  (id), KEY structure_id (structure_id), UNIQUE KEY structure_slug (structure_id, slug)) $charset_collate;";
        
        // NOU: Schema SQL pentru tabelul de webhooks
        $sql_webhooks = "CREATE TABLE $table_webhooks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            target_url VARCHAR(255) NOT NULL,
            event_name VARCHAR(100) NOT NULL,
            structure_slug VARCHAR(100) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event_name (event_name)
        ) $charset_collate;";


        dbDelta( $sql_structures );
        dbDelta( $sql_fields );
        dbDelta( $sql_webhooks ); 
    }

    /**
     * Salvează versiunea curentă a pluginului în baza de date.
     *
     * @since 1.0.0
     * @access private
     */
    private static function set_version() {
        update_option( 'ahoi_api_version', AHOI_API_VERSION );
    }
}