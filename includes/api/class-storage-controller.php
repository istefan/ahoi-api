<?php
/**
 * Controller pentru gestionarea fișierelor (upload).
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */
namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Storage_Controller {

    /**
     * Gestionează upload-ul unui fișier în WordPress Media Library.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_upload( WP_REST_Request $request ) {
        // Pasul 1: Verifică dacă utilizatorul are dreptul de a încărca fișiere.
        // Aceasta este o capabilitate standard WordPress.
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to upload files.', 'ahoi-api' ),
                [ 'status' => 403 ]
            );
        }

        // Include fișierele necesare pentru funcțiile de upload WordPress.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Pasul 2: Preluăm fișierele din cerere.
        // Pentru upload de fișiere, se folosește get_file_params() în loc de get_json_params().
        $files = $request->get_file_params();

        if ( empty( $files ) || ! isset( $files['file'] ) ) {
            return new WP_Error(
                'ahoi_api_no_file',
                __( 'No file was uploaded.', 'ahoi-api' ),
                [ 'status' => 400 ]
            );
        }

        // Pasul 3: Folosim funcția securizată WordPress pentru a gestiona upload-ul.
        // 'file' este numele câmpului din formularul de upload (ex: <input type="file" name="file">).
        $attachment_id = media_handle_sideload( $files['file'], 0 );

        if ( is_wp_error( $attachment_id ) ) {
            return new WP_Error(
                'ahoi_api_upload_error',
                $attachment_id->get_error_message(),
                [ 'status' => 500 ]
            );
        }

        // Pasul 4: Pregătim un răspuns util pentru client.
        $attachment_post = get_post( $attachment_id );
        $attachment_url = wp_get_attachment_url( $attachment_id );
        
        $response_data = [
            'success'   => true,
            'id'        => $attachment_id,
            'url'       => $attachment_url,
            'mime_type' => $attachment_post->post_mime_type,
            'title'     => $attachment_post->post_title,
            'file_name' => basename( get_attached_file( $attachment_id ) ),
        ];

        return new WP_REST_Response( $response_data, 201 ); // 201 Created
    }

    /**
     * Șterge un fișier din WordPress Media Library.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_delete( WP_REST_Request $request ) {
        $attachment_id = (int) $request['id'];

        // Verifică dacă atașamentul există.
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return new WP_Error( 'ahoi_api_not_found', __( 'File not found.', 'ahoi-api' ), [ 'status' => 404 ] );
        }

        // CRITIC: Verifică dacă utilizatorul curent are dreptul de a șterge acest fișier specific.
        // Previne un utilizator să șteargă fișierele altuia.
        if ( ! current_user_can( 'delete_post', $attachment_id ) ) {
            return new WP_Error( 'rest_forbidden', __( 'You do not have permission to delete this file.', 'ahoi-api' ), [ 'status' => 403 ] );
        }

        // Șterge permanent fișierul și intrarea din baza de date.
        // Al doilea parametru 'true' forțează ștergerea completă (nu doar mutarea în coș).
        $result = wp_delete_attachment( $attachment_id, true );

        if ( false === $result ) {
            return new WP_Error( 'ahoi_api_delete_error', __( 'Could not delete the file.', 'ahoi-api' ), [ 'status' => 500 ] );
        }

        return new WP_REST_Response( [ 'success' => true, 'message' => 'File deleted successfully.' ], 200 );
    }
    
}