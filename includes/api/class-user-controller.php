<?php
/**
 * Controller pentru gestionarea utilizatorilor.
 *
 * @link      https://www.ahoi.ro/
 * @since     1.0.0
 * @package   Ahoi_API
 */

namespace Ahoi_API\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Gestionează endpoint-urile legate de utilizatori (/users, /roles).
 */
class User_Controller {

    /**
     * Obține o listă de utilizatori cu datele esențiale.
     *
     * @param WP_REST_Request $request Obiectul cererii.
     * @return WP_REST_Response|WP_Error Răspunsul cu lista de utilizatori sau o eroare.
     */
    public function get_users(WP_REST_Request $request) {
        // Pasul 1: Verifică dacă utilizatorul curent are permisiunea de a vedea lista de useri.
        if (!current_user_can('list_users')) {
            return new WP_Error(
                'rest_forbidden',
                __('You cannot view users.', 'ahoi-api'),
                ['status' => 403]
            );
        }

        // Pasul 2: Preluăm obiectele complete ale utilizatorilor pentru a avea acces la toate datele, inclusiv roluri.
        $all_users = get_users();

        $response_data = []; // Inițializăm un array pentru a formata răspunsul.

        // Pasul 3: Iterăm prin fiecare obiect utilizator și construim un array curat cu datele pe care dorim să le expunem prin API.
        // Această abordare garantează că rolurile sunt întotdeauna incluse corect.
        foreach ($all_users as $user) {
            $response_data[] = [
                'ID'           => $user->ID,
                'user_login'   => $user->user_login,
                'display_name' => $user->display_name,
                'user_email'   => $user->user_email,
                'roles'        => $user->roles, // Extragem direct proprietatea 'roles' din obiectul WP_User.
            ];
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Creează un utilizator nou (endpoint securizat pentru manageri).
     *
     * @param WP_REST_Request $request Obiectul cererii care conține 'username', 'email', 'password'.
     * @return WP_REST_Response|WP_Error Răspunsul cu statusul operațiunii sau o eroare.
     */
    public function create_user(WP_REST_Request $request) {
        // Verifică dacă utilizatorul curent are permisiunea de a crea useri noi.
        if (!current_user_can('create_users')) {
            return new WP_Error(
                'rest_forbidden',
                __('You cannot create users.', 'ahoi-api'),
                ['status' => 403]
            );
        }

        // Reutilizăm logica de înregistrare din Auth_Controller, care deja gestionează validarea și crearea.
        // Această metodă este protejată de verificarea de capabilități de mai sus.
        $auth_controller = new Auth_Controller();
        return $auth_controller->register_user($request);
    }

    /**
     * Obține o listă cu toate rolurile disponibile în WordPress.
     *
     * @param WP_REST_Request $request Obiectul cererii.
     * @return WP_REST_Response|WP_Error Răspunsul cu lista de roluri sau o eroare.
     */
    public function get_roles(WP_REST_Request $request) {
        // Verifică dacă utilizatorul curent are permisiunea de a edita (și implicit vedea) roluri.
        if (!current_user_can('edit_users')) {
            return new WP_Error(
                'rest_forbidden',
                __('You cannot view roles.', 'ahoi-api'),
                ['status' => 403]
            );
        }

        // Folosim obiectul global $wp_roles pentru a extrage numele tuturor rolurilor definite.
        global $wp_roles;
        return new WP_REST_Response($wp_roles->get_names(), 200);
    }
}