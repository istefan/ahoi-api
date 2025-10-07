<?php
/**
 * Plugin Name:       Ahoi API
 * Plugin URI:        https://www.ahoi.ro/
 * Description:       A headless API solution for WordPress, allowing the creation and management of custom endpoints similar to Supabase.
 * Version:           1.4.0
 * Author:            Stefan Iftimie
 * Author URI:        https://www.ahoi.ro/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ahoi-api
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// SECURITY: Check if Composer autoloader exists. If not, fail gracefully.
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    // Display an admin notice about the missing dependencies.
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__('Ahoi API Error:', 'ahoi-api') . '</strong> ';
        echo esc_html__('Composer dependencies not found. Please run `composer install` in the plugin directory or reinstall the plugin.', 'ahoi-api');
        echo '</p></div>';
    });
    // Stop further execution of the plugin.
    return;
}
// Load Composer autoloader. Essential for external dependencies (e.g., JWT).
require_once __DIR__ . '/vendor/autoload.php';


// ==========================================================================
// INITIALIZE GITHUB UPDATE CHECKER
// ==========================================================================
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/istefan/ahoi-api/', // The URL of your GitHub repository.
    __FILE__,                               // Path to the main plugin file.
    'ahoi-api'                              // A unique slug for the plugin.
);

// (Optional) If your repository is PRIVATE, uncomment the following line and add your Personal Access Token.
// $myUpdateChecker->setAuthentication('YOUR_GITHUB_PERSONAL_ACCESS_TOKEN');

// (Optional) You can set the branch to check for updates (default is 'master' or 'main').
// $myUpdateChecker->setBranch('main');
// ==========================================================================


/**
 * Define the main plugin constants.
 */
define( 'AHOI_API_VERSION', '1.4.0' );
define( 'AHOI_API_FILE', __FILE__ );
define( 'AHOI_API_PATH', dirname( AHOI_API_FILE ) );
define( 'AHOI_API_URL', plugins_url( '', AHOI_API_FILE ) );
define( 'AHOI_API_INCLUDES', AHOI_API_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR );

/**
 * Load the plugin's base files.
 */
require_once AHOI_API_INCLUDES . 'class-ahoi-api.php';
require_once AHOI_API_INCLUDES . 'class-installer.php';
require_once AHOI_API_INCLUDES . 'helpers.php';

/**
 * The main function that returns the Ahoi_API class instance (Singleton).
 *
 * @return \Ahoi_API\Ahoi_API The main plugin instance.
 */
function ahoi_api() {
    return \Ahoi_API\Ahoi_API::instance();
}

// Use the full class name, including the namespace, for hooks.
register_activation_hook( AHOI_API_FILE, [ 'Ahoi_API\Installer', 'activate' ] );
register_deactivation_hook( AHOI_API_FILE, [ 'Ahoi_API\Installer', 'deactivate' ] );


// Hook the initialization to 'plugins_loaded' to ensure WordPress is ready.
// This is the key change that resolves potential fatal errors on activation.
add_action( 'plugins_loaded', 'ahoi_api' );