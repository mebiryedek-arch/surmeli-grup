<?php
/** Safely extend personnel cards for records that do not need a WordPress login. */
defined( 'ABSPATH' ) || exit;

final class SG_Migration_008_Personnel_Tracking {
    public function up(): bool|WP_Error {
        global $wpdb;
        $table = $wpdb->prefix . 'sg_personnel';
        if ( ! SG_Migrator::table_exists( $table ) ) {
            return new WP_Error( 'sg_personnel_table_missing', 'Personel tablosu bulunamadı; mevcut veriler değiştirilmedi.' );
        }

        $column = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'user_id' ), ARRAY_A );
        if ( ! $column ) { return new WP_Error( 'sg_personnel_user_column_missing', 'Personel kullanıcı bağlantısı alanı bulunamadı.' ); }

        $index_rows = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", 'user_id' ), ARRAY_A );
        $has_index = ! empty( $index_rows );
        $is_unique = $has_index && 0 === (int) $index_rows[0]['Non_unique'];
        $alter = array();
        if ( 'YES' !== strtoupper( (string) $column['Null'] ) ) { $alter[] = 'MODIFY user_id bigint(20) unsigned DEFAULT NULL'; }
        if ( $is_unique ) { $alter[] = 'DROP INDEX user_id'; $alter[] = 'ADD KEY user_id (user_id)'; }
        elseif ( ! $has_index ) { $alter[] = 'ADD KEY user_id (user_id)'; }

        $department = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'department' ), ARRAY_A );
        if ( ! $department ) { $alter[] = 'ADD department varchar(120) DEFAULT NULL AFTER job_title'; }

        if ( $alter && false === $wpdb->query( 'ALTER TABLE ' . $table . ' ' . implode( ', ', $alter ) ) ) {
            return new WP_Error( 'sg_personnel_schema_update', 'Personel tablosu güvenli biçimde güncellenemedi: ' . $wpdb->last_error );
        }

        $check = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'department' ), ARRAY_A );
        if ( ! $check ) { return new WP_Error( 'sg_personnel_schema_incomplete', 'Personel departman alanı oluşturulamadı.' ); }
        return true;
    }
}
