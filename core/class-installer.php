<?php
/** Safe activation and database installation. */
defined( 'ABSPATH' ) || exit;

final class SG_Installer {
    public static function activate(): void {
        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
            deactivate_plugins( SG_PLUGIN_BASENAME );
            wp_die( esc_html__( 'Sürmeli Grup için PHP 8.1 veya üzeri gereklidir.', 'surmeli-grup' ) );
        }

        global $wpdb;
        if ( ! $wpdb->has_cap( 'collation' ) ) {
            deactivate_plugins( SG_PLUGIN_BASENAME );
            wp_die( esc_html__( 'Sunucu veritabanı gerekli MySQL özelliklerini desteklemiyor.', 'surmeli-grup' ) );
        }

        add_option( 'sg_version', SG_VERSION, '', false );
        add_option( 'sg_db_version', '0.0.0', '', false );
        add_option( 'sg_currency', 'TRY', '', false );
        add_option( 'sg_installation_completed', 0, '', false );

        $result = SG_Migrator::migrate();
        if ( is_wp_error( $result ) ) {
            deactivate_plugins( SG_PLUGIN_BASENAME );
            wp_die( esc_html( sprintf( __( 'Sürmeli Grup veritabanı kurulamadı: %s', 'surmeli-grup' ), $result->get_error_message() ) ) );
        }

        update_option( 'sg_installation_completed', 1, false );
        SG_App::register_route();
        flush_rewrite_rules( false );
    }
}
