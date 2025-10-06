<?php
/**
 * Gestionează pagina de setări și logica pentru salvarea opțiunilor.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */
namespace Ahoi_API\Admin;

// Previne accesul direct.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clasa Settings_Page.
 */
class Settings_Page {

    /**
     * ID-ul grupului de opțiuni.
     * @var string
     */
    private $option_group = 'ahoi_api_settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Înregistrează secțiunile și câmpurile folosind WordPress Settings API.
     */
    public function register_settings() {
        // Înregistrează grupul de setări.
        register_setting(
            $this->option_group,       // Numele grupului
            'ahoi_api_options',        // Numele opțiunii din tabelul wp_options
            [ $this, 'sanitize' ]      // Funcția de sanitizare la salvare
        );

        // Adaugă secțiunea CORS
        add_settings_section(
            'ahoi_api_cors_section',                // ID-ul secțiunii
            __( 'CORS Settings', 'ahoi-api' ),      // Titlul
            [ $this, 'print_cors_section_info' ],   // Callback pentru a afișa text descriptiv
            $this->option_group                     // Pagina pe care apare
        );

        // Adaugă câmpul pentru domeniile permise
        add_settings_field(
            'allowed_origins',                                  // ID-ul câmpului
            __( 'Allowed Origins', 'ahoi-api' ),                // Eticheta
            [ $this, 'render_allowed_origins_field' ],          // Callback pentru a afișa HTML-ul câmpului
            $this->option_group,                                // Pagina
            'ahoi_api_cors_section'                             // Secțiunea
        );
    }

    /**
     * Sanitizează datele înainte de a le salva în baza de date.
     *
     * @param array $input Datele primite de la formular.
     * @return array Datele sanitizate.
     */
    public function sanitize( $input ) {
        $new_input = [];
        if ( isset( $input['allowed_origins'] ) ) {
            // Curățăm fiecare linie, eliminăm liniile goale și asigurăm că sunt doar domenii valide.
            $origins = explode( "\n", $input['allowed_origins'] );
            $sanitized_origins = [];
            foreach ( $origins as $origin ) {
                $trimmed_origin = trim( $origin );
                if ( ! empty( $trimmed_origin ) ) {
                    // O sanitizare simplă, se poate îmbunătăți cu validare de URL.
                    $sanitized_origins[] = esc_url_raw( $trimmed_origin );
                }
            }
            $new_input['allowed_origins'] = implode( "\n", array_unique( $sanitized_origins ) );
        }
        return $new_input;
    }

    /**
     * Afișează textul descriptiv pentru secțiunea CORS.
     */
    public function print_cors_section_info() {
        esc_html_e( 'Enter the domains that are allowed to make requests to your API. Enter one domain per line.', 'ahoi-api' );
    }

    /**
     * Afișează câmpul textarea pentru domeniile permise.
     */
    public function render_allowed_origins_field() {
        $options = get_option( 'ahoi_api_options' );
        $value = $options['allowed_origins'] ?? '';
        printf(
            '<textarea id="allowed_origins" name="ahoi_api_options[allowed_origins]" rows="5" cols="50">%s</textarea>',
            esc_textarea( $value )
        );
        echo '<p class="description">' . esc_html__( 'Example: https://www.my-app.com', 'ahoi-api' ) . '</p>';
    }
}