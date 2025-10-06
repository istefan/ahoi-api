<?php
/**
 * Controller pentru gestionarea notificărilor (ex: email).
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */
namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Notification_Controller {

    /**
     * Trimite un email folosind funcția nativă wp_mail().
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function send_email( WP_REST_Request $request ) {
        // Pasul 1: Validarea permisiunilor.
        // Doar anumite roluri ar trebui să poată trimite email-uri.
        // Vom folosi o capabilitate custom pe care o vom adăuga rolului de "Manager".
        if ( ! current_user_can( 'send_api_emails' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to send emails through the API.', 'ahoi-api' ),
                [ 'status' => 403 ]
            );
        }

        // Pasul 2: Preluarea și validarea parametrilor din cererea POST.
        $params = $request->get_json_params();
        $to      = sanitize_email( $params['to'] ?? '' );
        $subject = sanitize_text_field( $params['subject'] ?? '' );
        $body    = wp_kses_post( $params['body'] ?? '' ); // Permite HTML de bază, dar securizat

        if ( empty( $to ) || ! is_email( $to ) || empty( $subject ) || empty( $body ) ) {
            return new WP_Error(
                'ahoi_api_missing_email_params',
                __( 'Required parameters are missing or invalid: "to", "subject", "body".', 'ahoi-api' ),
                [ 'status' => 400 ]
            );
        }

        // Pasul 3: Setarea header-elor pentru a trimite email-uri HTML.
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        // Pasul 4: Trimiterea email-ului folosind funcția WordPress.
        $result = wp_mail( $to, $subject, $body, $headers );

        if ( ! $result ) {
            return new WP_Error(
                'ahoi_api_email_failed',
                __( 'The email could not be sent. Check your SMTP settings in WordPress.', 'ahoi-api' ),
                [ 'status' => 500 ] // Internal Server Error
            );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'Email sent successfully.',
        ], 200 );
    }
}