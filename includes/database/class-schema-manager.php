<?php
/**
 * Manager pentru manipularea schemei bazei de date (creare/modificare tabele).
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */
namespace Ahoi_API\Database;

use WP_Error;

// Previne accesul direct.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clasa Schema_Manager.
 */
class Schema_Manager {

    /**
     * Generează numele tabelului de date pe baza slug-ului structurii.
     * @param string $slug
     * @return string
     */
    public function get_table_name_from_slug( $slug ) {
        global $wpdb;
        $safe_slug = preg_replace( '/[^a-zA-Z0-9_]/', '', $slug );
        return $wpdb->prefix . 'ahoi_data_' . $safe_slug;
    }

    /**
     * Creează un tabel nou în baza de date pentru o structură specifică.
     * @param string $structure_slug
     * @return bool|WP_Error
     */
    public function create_table_for_structure( $structure_slug ) {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $table_name = $this->get_table_name_from_slug( $structure_slug );
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
            return new WP_Error( 'table_exists', sprintf( __( 'Table "%s" already exists.', 'ahoi-api' ), $table_name ) );
        }

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_id BIGINT(20) UNSIGNED,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY owner_id (owner_id)
        ) {$charset_collate};";
        
        dbDelta( $sql );
        
        if ( ! empty( $wpdb->last_error ) ) {
             return new WP_Error( 'db_error', $wpdb->last_error );
        }

        return true;
    }

    /**
     * Adaugă o coloană nouă la un tabel de date existent.
     *
     * @param string $structure_slug Slug-ul structurii.
     * @param array $field Definiția câmpului (slug, type, is_required).
     * @return bool|WP_Error
     */
    public function add_column_to_table( $structure_slug, $field ) {
        global $wpdb;

        $table_name = $this->get_table_name_from_slug( $structure_slug );
        $column_name = sanitize_key( $field['slug'] );
        $sql_type = $this->map_type_to_sql( $field['type'] );
        $nullable = $field['is_required'] ? 'NOT NULL' : 'NULL';

        // MODIFICARE: Adăugăm backticks (`) în jurul numelui coloanei
        $sql = "ALTER TABLE `{$table_name}` ADD COLUMN `{$column_name}` {$sql_type} {$nullable}";

        $result = $wpdb->query( $sql );

        if ( false === $result ) {
            return new WP_Error( 'db_error', $wpdb->last_error ?: __( 'Could not add column to the table.', 'ahoi-api' ) );
        }

        return true;
    }
    
    /**
     * Șterge o coloană dintr-un tabel de date.
     *
     * @param string $structure_slug Slug-ul structurii.
     * @param string $field_slug Slug-ul câmpului de șters.
     * @return bool|WP_Error
     */
    public function drop_column_from_table( $structure_slug, $field_slug ) {
        global $wpdb;
        
        $table_name = $this->get_table_name_from_slug( $structure_slug );
        $column_name = sanitize_key( $field_slug );
        
        // MODIFICARE: Adăugăm backticks (`) în jurul numelui coloanei
        $sql = "ALTER TABLE `{$table_name}` DROP COLUMN `{$column_name}`";
        
        $result = $wpdb->query( $sql );

        if ( false === $result ) {
            return new WP_Error( 'db_error', $wpdb->last_error ?: __( 'Could not drop column from the table.', 'ahoi-api' ) );
        }

        return true;
    }
    
    /**
     * Șterge un tabel de date asociat cu o structură.
     * @param string $structure_slug
     * @return bool|WP_Error
     */
    public function delete_table_for_structure( $structure_slug ) {
        global $wpdb;
        $table_name = $this->get_table_name_from_slug( $structure_slug );
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
        return true;
    }

    /**
     * Mapează tipurile de date simple la tipuri SQL corespunzătoare.
     *
     * @param string $type Tipul simplu (ex: 'TEXT_SHORT').
     * @return string Tipul SQL (ex: 'VARCHAR(255)').
     */
    private function map_type_to_sql( $type ) {
        $map = [
            'TEXT_SHORT'     => 'VARCHAR(255)',
            'TEXT_LONG'      => 'TEXT',
            'NUMBER_INT'     => 'BIGINT(20)',
            'NUMBER_DECIMAL' => 'DECIMAL(10, 2)', // NOU: Permite 10 cifre în total, din care 2 după virgulă. Perfect pentru prețuri.
            'BOOLEAN'        => 'BOOLEAN',
            'DATETIME'       => 'DATETIME',
            'DATE'           => 'DATE',           // NOU
            'RELATIONSHIP'   => 'BIGINT(20) UNSIGNED', // NOU: Stochează un ID către altă înregistrare.
            'JSON'           => 'JSON',           // NOU: Sau 'LONGTEXT' ca alternativă pe versiuni vechi de MySQL.
        ];

        return $map[ $type ] ?? 'VARCHAR(255)'; // Default la VARCHAR
    }
}