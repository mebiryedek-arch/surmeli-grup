<?php
/** Verify the job tables created by the core schema; do not alter existing tables. */
defined( 'ABSPATH' ) || exit;

final class SG_Migration_007_Job_Tracking {
    public function up(): bool|WP_Error {
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'sg_jobs',
            $wpdb->prefix . 'sg_job_history',
            $wpdb->prefix . 'sg_job_notes',
        );

        foreach ( $tables as $table ) {
            if ( ! SG_Migrator::table_exists( $table ) ) {
                return new WP_Error(
                    'sg_job_schema_missing',
                    'İş Takibi için gerekli tablo bulunamadı: ' . $table . '. Mevcut kayıtlar korunmuştur.'
                );
            }
        }

        return true;
    }
}
