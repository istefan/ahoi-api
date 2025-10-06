<?php
/**
 * Fișierul clasei principale a pluginului.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

// Folosim namespace pentru a evita conflicte și pentru a beneficia de autoloading (PSR-4).
namespace Ahoi_API;

// Previne accesul direct.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clasa principală Ahoi_API.
 *
 * Aceasta este clasa principală care inițializează și rulează toată logica pluginului.
 * Acționează ca un orchestrator central.
 *
 * @since      1.0.0
 * @author     Stefan Iftimie <email@exemplu.com>
 */
final class Ahoi_API {

    /**
     * Singura instanță a clasei (Singleton).
     *
     * @since    1.0.0
     * @access   private
     * @var      Ahoi_API
     */
    private static $instance = null;

    /**
     * Versiunea pluginului.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $version = '1.0.0';

    /**
     * Metoda principală care asigură că există o singură instanță a clasei.
     *
     * @since     1.0.0
     * @static
     * @return    Ahoi_API - Returnează instanța clasei.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructorul clasei.
     * Este privat pentru a preveni crearea de noi instanțe.
     *
     * @since    1.0.0
     */
    private function __construct() {
        $this->version = AHOI_API_VERSION;
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Definește constante suplimentare dacă este necesar.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_constants() {
        // Aici poți adăuga alte constante specifice logicii interne.
    }

    /**
     * Încarcă fișierele necesare pentru funcționarea pluginului.
     * Datorită autoloader-ului Composer, nu trebuie să includem fiecare clasă,
     * ci doar fișierele care instanțiază clasele respective.
     *
     * @since    1.0.0
     * @access   private
     */
    private function includes() {
        // Nu este necesar să includem fișierele de clasă datorită autoloading-ului PSR-4.
        // Composer se ocupă de asta. Vom instanția clasele direct.
    }

    /**
     * Adaugă toate acțiunile (actions) și filtrele (filters) necesare.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'rest_api_init', [ $this, 'init_api' ] );

        // Inițializează componenta de administrare doar dacă suntem în panoul de control.
        if ( is_admin() ) {
            $this->init_admin();
        }
    }

    /**
     * Încarcă componenta de administrare a pluginului.
     *
     * @since 1.0.0
     */
    public function init_admin() {
        // Clasa Admin\Admin_Menu va fi încărcată automat de Composer.
        // Ea se va ocupa de crearea paginilor din meniu.
        new Admin\Admin_Menu();
    }

    /**
     * Încarcă componenta API a pluginului.
     *
     * @since 1.0.0
     */
    public function init_api() {
        // Clasa Api\Api_Manager va fi încărcată automat de Composer.
        // Ea se va ocupa de înregistrarea tuturor endpoint-urilor.
        new Api\Api_Manager();
    }
    
    /**
     * Încarcă fișierul de traducere (textdomain) al pluginului.
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ahoi-api',
            false,
            dirname( plugin_basename( AHOI_API_FILE ) ) . '/languages/'
        );
    }
}