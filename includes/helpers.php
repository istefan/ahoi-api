<?php
/**
 * Fișier pentru funcții ajutătoare globale.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

if ( ! function_exists( 'ahoi_api_log' ) ) {
    /**
     * Funcție simplă de logging pentru debug.
     * Va scrie în fișierul debug.log din wp-content dacă WP_DEBUG_LOG este activat.
     */
    function ahoi_api_log( $message ) {
        if ( WP_DEBUG === true ) {
            if ( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }

}


/**
 * Declanșează un eveniment webhook.
 * Găsește toate URL-urile care ascultă acest eveniment și programează trimiterea în fundal.
 *
 * @param string $event Numele evenimentului (ex: 'item.created').
 * @param array|object $data Datele care vor fi trimise ca payload.
 */
function ahoi_api_trigger_webhook( $event, $data ) {
    global $wpdb;
    $webhooks_table = $wpdb->prefix . 'ahoi_api_webhooks';

    $active_webhooks = $wpdb->get_results( $wpdb->prepare(
        "SELECT target_url FROM {$webhooks_table} WHERE event_name = %s AND status = 'active'",
        $event
    ) );

    if ( empty( $active_webhooks ) ) {
        return;
    }

    // Extragem slug-ul structurii din date, dacă este posibil
    $structure_slug = $data->slug ?? 'unknown';

    // Construim payload-ul final
    $payload = [
        'event'     => $event,
        'structure' => $structure_slug,
        'data'      => $data,
        'timestamp' => current_time('timestamp', true)
    ];

    foreach ( $active_webhooks as $webhook ) {
        // Programăm un eveniment care să ruleze IMEDIAT, o singură dată.
        // WP-Cron nu este perfect instantaneu, dar este cea mai bună opțiune nativă.
        wp_schedule_single_event( time(), 'ahoi_api_send_webhook_event', [
            'target_url' => $webhook->target_url,
            'payload'    => $payload,
        ] );
    }
}

/**
 * Funcția care efectiv trimite cererea POST pentru un webhook.
 * Aceasta este apelată de WP-Cron în fundal.
 *
 * @param string $target_url URL-ul către care se trimite.
 * @param array $payload Datele de trimis.
 */
function ahoi_api_send_webhook_request( $target_url, $payload ) {
    $args = [
        'body'        => wp_json_encode( $payload ),
        'headers'     => [ 'Content-Type' => 'application/json; charset=utf-8' ],
        'timeout'     => 15, // Timeout de 15 secunde
        'redirection' => 5,
        'blocking'    => true, // Rulează și așteaptă răspuns
        'data_format' => 'body',
    ];

    wp_remote_post( $target_url, $args );
}
// Conectăm funcția de trimitere la acțiunea programată de WP-Cron.
add_action( 'ahoi_api_send_webhook_event', 'ahoi_api_send_webhook_request', 10, 2 );