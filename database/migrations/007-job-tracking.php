<?php
/** Add isolated job-tracking tables without altering existing business records. */
defined( 'ABSPATH' ) || exit;

final class SG_Migration_007_Job_Tracking {
    public function up(): bool|WP_Error {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $jobs = $wpdb->prefix . 'sg_jobs';
        $history = $wpdb->prefix . 'sg_job_history';
        $notes = $wpdb->prefix . 'sg_job_notes';

        dbDelta( "CREATE TABLE {$jobs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_no varchar(40) NOT NULL,
            title varchar(190) NOT NULL,
            description longtext NULL,
            party_id bigint(20) unsigned NULL,
            assigned_personnel_id bigint(20) unsigned NULL,
            start_date datetime NULL,
            due_date datetime NULL,
            priority varchar(20) NOT NULL DEFAULT 'normal',
            status varchar(20) NOT NULL DEFAULT 'new',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            completed_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_no (job_no),
            KEY party_id (party_id),
            KEY assigned_personnel_id (assigned_personnel_id),
            KEY due_date (due_date),
            KEY status (status)
        ) ENGINE=InnoDB {$charset_collate};" );

        dbDelta( "CREATE TABLE {$history} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            action varchar(40) NOT NULL,
            old_value longtext NULL,
            new_value longtext NULL,
            note longtext NULL,
            performed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY performed_by (performed_by)
        ) ENGINE=InnoDB {$charset_collate};" );

        dbDelta( "CREATE TABLE {$notes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            note longtext NOT NULL,
            visibility varchar(20) NOT NULL DEFAULT 'internal',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY created_by (created_by)
        ) ENGINE=InnoDB {$charset_collate};" );

        foreach ( array( $jobs, $history, $notes ) as $table ) {
            if ( ! SG_Migrator::table_exists( $table ) ) {
                return new WP_Error( 'sg_job_schema_failed', 'İş Takibi tablosu oluşturulamadı: ' . $table . ' ' . $wpdb->last_error );
            }
        }
        return true;
    }
}
