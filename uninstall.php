<?php
/**
 * Logica de curățare la ștergerea completă a pluginului Ahoi API.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

// Dacă fișierul nu este apelat de WordPress în timpul dezinstalării, ieși.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Preluăm toate slug-urile structurilor create pentru a ști ce tabele de date să ștergem.
$structures_table = $wpdb->prefix . 'ahoi_api_structures';
$structures = $wpdb->get_results( "SELECT slug FROM {$structures_table}", OBJECT_K );

if ( ! empty( $structures ) ) {
    foreach ( $structures as $structure ) {
        $table_to_delete = $wpdb->prefix . 'ahoi_data_' . $structure->slug;
        $wpdb->query( "DROP TABLE IF EXISTS {$table_to_delete}" );
    }
}

// 2. Ștergem tabelele de management.
$fields_table = $wpdb->prefix . 'ahoi_api_fields';
$wpdb->query( "DROP TABLE IF EXISTS {$structures_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$fields_table}" );

// 3. Ștergem opțiunile salvate în tabelul wp_options.
delete_option( 'ahoi_api_options' );
delete_option( 'ahoi_api_version' );