<?php
/**
 * Handles the creation of the admin menu and pages.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

namespace Ahoi_API\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Admin_Menu.
 *
 * Handles the registration of the admin menu pages for the plugin.
 */
class Admin_Menu {

    /**
     * The main page hook suffix.
     * @var string
     */
    protected $main_page_hook;
    
    /**
     * The class constructor.
     * Adds the actions for creating the menu and enqueuing assets.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Initialize the logic for the settings page.
        new Settings_Page();
    }

    /**
     * Enqueues CSS and JS files for the plugin's admin pages.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        // BUG FIX: Correctly identify all plugin admin pages.
        // The hook for submenu pages starts with the hook of the parent page.
        if ( strpos($hook, 'ahoi-api') === false ) {
            return;
        }

        wp_enqueue_style(
            'ahoi-api-admin-style', // Unique name
            AHOI_API_URL . '/assets/css/admin-style.css', // Path to the file
            [], // Dependencies
            AHOI_API_VERSION // Version
        );

        wp_enqueue_script(
            'ahoi-api-admin-script', // Unique name
            AHOI_API_URL . '/assets/js/admin-script.js', // Path to the file
            ['jquery'], // Dependencies (jQuery)
            AHOI_API_VERSION, // Version
            true // Load in footer
        );
    }

    /**
     * Registers all menu pages for the plugin.
     *
     * @since 1.0.0
     */
    public function register_menus() {
        // Add the main menu page
        $this->main_page_hook = add_menu_page(
            __( 'Ahoi API Management', 'ahoi-api' ),   // Page title
            'Ahoi API',                                 // Menu title
            'manage_options',                           // Capability required
            'ahoi-api',                                 // Menu slug
            [ $this, 'render_table_builder_page' ],   // Function to display the page content
            'dashicons-rest-api',                       // Menu icon (from Dashicons)
            81                                          // Position in the menu (high number = lower down)
        );

        // Add the "Table Builder" submenu page
        add_submenu_page(
            'ahoi-api',                                 // Parent menu slug
            __( 'Table Builder', 'ahoi-api' ),          // Page title
            __( 'Table Builder', 'ahoi-api' ),          // Menu title
            'manage_options',                           // Capability required
            'ahoi-api',                                 // This submenu's slug (same as parent)
            [ $this, 'render_table_builder_page' ]    // Display function
        );

        // Add the "Settings" submenu page
        add_submenu_page(
            'ahoi-api',                                 // Parent menu slug
            __( 'Settings', 'ahoi-api' ),               // Page title
            __( 'Settings', 'ahoi-api' ),               // Menu title
            'manage_options',                           // Capability required
            'ahoi-api-settings',                        // This submenu's slug
            [ $this, 'render_settings_page' ]           // Display function
        );

        // Add the "Help / Docs" submenu page
        add_submenu_page(
            'ahoi-api',                                 // Parent menu slug
            __( 'Help / Documentation', 'ahoi-api' ),   // Page title
            __( 'Help / Docs', 'ahoi-api' ),            // Menu title
            'manage_options',                           // Capability required
            'ahoi-api-help',                            // This submenu's slug
            [ $this, 'render_help_page' ]               // Display function
        );
    }

    /**
     * Renders the content of the "Table Builder" page.
     *
     * @since 1.0.0
     */
    public function render_table_builder_page() {
        // We load a "view" file which contains the HTML.
        // This is good practice to separate logic from display.
        require_once AHOI_API_PATH . '/includes/admin/views/view-table-builder.php';
    }

    /**
     * Renders the content of the "Settings" page.
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        require_once AHOI_API_PATH . '/includes/admin/views/view-settings.php';
    }

    /**
     * Renders the content of the "Help / Documentation" page.
     *
     * @since 1.0.0
     */
    public function render_help_page() {
        require_once AHOI_API_PATH . '/includes/admin/views/view-help-page.php';
    }
}