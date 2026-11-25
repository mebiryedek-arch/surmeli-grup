<?php
/** Versioned database migration runner. */
defined( 'ABSPATH' ) || exit;

final class SG_Migrator {
    private const LOCK_OPTION = 'sg_migration_lock';
    private const LOCK_TTL = 300;

    public static function migrate(): bool|WP_Error {
        global $wpdb;

        $lock_value = (string) time() . ':' . wp_generate_uuid4();
        if ( ! add_option( self::LOCK_OPTION, $lock_value, '', false ) ) {
            $existing = (string) get_option( self::LOCK_OPTION, '' );
            $parts = explode( ':', $existing, 2 );
            if ( isset( $parts[0] ) && ctype_digit( $parts[0] ) && ( time() - (int) $parts[0] ) > self::LOCK_TTL ) {
                delete_option( self::LOCK_OPTION );
                if ( ! add_option( self::LOCK_OPTION, $lock_value, '', false ) ) {
                    return new WP_Error( 'sg_migration_locked', __( 'Veritabanı güncellemesi başka bir işlem tarafından yürütülüyor.', 'surmeli-grup' ) );
                }
            } else {
                return new WP_Error( 'sg_migration_locked', __( 'Veritabanı güncellemesi başka bir işlem tarafından yürütülüyor.', 'surmeli-grup' ) );
            }
        }

        try {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $table = $wpdb->prefix . 'sg_schema_migrations';
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                migration varchar(191) NOT NULL,
                applied_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY migration (migration)
            ) ENGINE=InnoDB {$charset_collate};";
            dbDelta( $sql );

            if ( ! self::table_exists( $table ) ) {
                return new WP_Error( 'sg_migration_table_failed', __( 'Migration takip tablosu oluşturulamadı: ', 'surmeli-grup' ) . $wpdb->last_error );
            }

            require_once SG_PLUGIN_DIR . 'database/migrations/001-create-core-schema.php';
            require_once SG_PLUGIN_DIR . 'database/migrations/002-seed-permissions.php';
            require_once SG_PLUGIN_DIR . 'database/migrations/006-income-expense.php';
            require_once SG_PLUGIN_DIR . 'database/migrations/007-job-tracking.php';
            require_once SG_PLUGIN_DIR . 'database/migrations/008-personnel-tracking.php';
            $migration = new SG_Migration_001_Create_Core_Schema();
            $missing_tables = array();
            foreach ( $migration->get_tables() as $table_name ) {
                if ( ! self::table_exists( $table_name ) ) { $missing_tables[] = $table_name; }
            }
            if ( ! empty( $missing_tables ) ) {
                $migration->up();
                foreach ( $migration->get_tables() as $table_name ) {
                    if ( ! self::table_exists( $table_name ) ) {
                        return new WP_Error( 'sg_schema_incomplete', sprintf( __( 'Veritabanı tablosu oluşturulamadı: %s. Ayrıntı: %s', 'surmeli-grup' ), $table_name, $wpdb->last_error ) );
                    }
                }
            }

            $migrations = array(
                '001_create_core_schema' => static function () use ( $wpdb ) {
                    $table = $wpdb->prefix . 'sg_schema_migrations';
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", '001_create_core_schema' ) );
                    if ( ! $exists ) {
                        return $wpdb->insert( $table, array( 'migration' => '001_create_core_schema', 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) ) !== false;
                    }
                    return true;
                },
                '002_seed_permissions' => static function () use ( $wpdb ) {
                    $table = $wpdb->prefix . 'sg_schema_migrations';
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", '002_seed_permissions' ) );
                    if ( $exists ) { return true; }
                    $seed = new SG_Migration_002_Seed_Permissions();
                    $result = $seed->up();
                    if ( is_wp_error( $result ) ) { return $result; }
                    $inserted = $wpdb->insert( $table, array( 'migration' => '002_seed_permissions', 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                    return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
                },
            );
            $migrations['003_refresh_permissions'] = static function () use ( $wpdb ) {
                $table = $wpdb->prefix . 'sg_schema_migrations';
                $name = '003_refresh_permissions';
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", $name ) );
                if ( $exists ) { return true; }
                $seed = new SG_Migration_002_Seed_Permissions();
                $result = $seed->up();
                if ( is_wp_error( $result ) ) { return $result; }
                $inserted = $wpdb->insert( $table, array( 'migration' => $name, 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
            };

            $migrations['004_accounts_permissions'] = static function () use ( $wpdb ) {
                $table = $wpdb->prefix . 'sg_schema_migrations';
                $name = '004_accounts_permissions';
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", $name ) );
                if ( $exists ) { return true; }
                $seed = new SG_Migration_002_Seed_Permissions();
                $result = $seed->up();
                if ( is_wp_error( $result ) ) { return $result; }
                $inserted = $wpdb->insert( $table, array( 'migration' => $name, 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
            };

            $migrations['005_cash_bank_permissions'] = static function () use ( $wpdb ) {
                $table = $wpdb->prefix . 'sg_schema_migrations';
                $name = '005_cash_bank_permissions';
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", $name ) );
                if ( $exists ) { return true; }
                $seed = new SG_Migration_002_Seed_Permissions();
                $result = $seed->up();
                if ( is_wp_error( $result ) ) { return $result; }
                $inserted = $wpdb->insert( $table, array( 'migration' => $name, 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
            };

            $migrations['006_income_expense'] = static function () use ( $wpdb ) {
                $table = $wpdb->prefix . 'sg_schema_migrations';
                $name = '006_income_expense';
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", $name ) );
                if ( $exists ) { return true; }
                $migration = new SG_Migration_006_Income_Expense();
                $result = $migration->up();
                if ( is_wp_error( $result ) ) { return $result; }
                $inserted = $wpdb->insert( $table, array( 'migration' => $name, 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
            };

            $migrations['007_job_tracking'] = static function () use ( $wpdb ) {
                $table = $wpdb->prefix . 'sg_schema_migrations';
                $name = '007_job_tracking';
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", $name ) );
                if ( $exists ) {
                    $migration = new SG_Migration_007_Job_Tracking();
                    return $migration->up();
                }
                $migration = new SG_Migration_007_Job_Tracking();
                $result = $migration->up();
                if ( is_wp_error( $result ) ) { return $result; }
                $inserted = $wpdb->insert( $table, array( 'migration' => $name, 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
            };

            $migrations['008_personnel_tracking'] = static function () use ( $wpdb ) {
                $table = $wpdb->prefix . 'sg_schema_migrations';
                $name = '008_personnel_tracking';
                $migration = new SG_Migration_008_Personnel_Tracking();
                $result = $migration->up();
                if ( is_wp_error( $result ) ) { return $result; }
                $seed = new SG_Migration_002_Seed_Permissions();
                $seed_result = $seed->up();
                if ( is_wp_error( $seed_result ) ) { return $seed_result; }
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT migration FROM {$table} WHERE migration = %s LIMIT 1", $name ) );
                if ( $exists ) { return true; }
                $inserted = $wpdb->insert( $table, array( 'migration' => $name, 'applied_at' => current_time( 'mysql', true ) ), array( '%s', '%s' ) );
                return false === $inserted ? new WP_Error( 'sg_migration_record_failed', $wpdb->last_error ) : true;
            };

            foreach ( $migrations as $migration_name => $callback ) {
                $result = $callback();
                if ( is_wp_error( $result ) ) { return $result; }
                if ( true !== $result ) { return new WP_Error( 'sg_migration_failed', 'Migration tamamlanamadı: ' . $migration_name . ' ' . $wpdb->last_error ); }
            }

            update_option( 'sg_db_version', SG_DB_VERSION, false );
            update_option( 'sg_version', SG_VERSION, false );
            return true;
        } catch ( Throwable $error ) {
            return new WP_Error( 'sg_migration_exception', $error->getMessage() );
        } finally {
            if ( (string) get_option( self::LOCK_OPTION, '' ) === $lock_value ) {
                delete_option( self::LOCK_OPTION );
            }
        }
    }

    public static function table_exists( string $table_name ): bool {
        global $wpdb;
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
        return $found === $table_name;
    }
}
