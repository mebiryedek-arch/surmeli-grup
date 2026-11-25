<?php
/** Seed the application permission dictionary and default role grants. */
defined( 'ABSPATH' ) || exit;

final class SG_Migration_002_Seed_Permissions {
    public function up(): bool|WP_Error {
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'sg_permissions';
        $roles_table = $wpdb->prefix . 'sg_role_permissions';
        $catalog = SG_Permissions::permission_catalog();
        $now = current_time( 'mysql', true );

        foreach ( $catalog as $key => $description ) {
            $module = strtok( $key, '.' );
            $existing_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$permissions_table} WHERE permission_key = %s LIMIT 1", $key
            ) );
            if ( $existing_id ) {
                $updated = $wpdb->update( $permissions_table, array(
                    'description' => $description,
                    'module_key' => $module,
                ), array( 'id' => $existing_id ), array( '%s', '%s' ), array( '%d' ) );
                if ( false === $updated ) { return new WP_Error( 'sg_permission_update_failed', $wpdb->last_error ); }
                $permission_id = $existing_id;
            } else {
                $inserted = $wpdb->insert( $permissions_table, array(
                    'permission_key' => $key,
                    'description' => $description,
                    'module_key' => $module,
                    'created_at' => $now,
                ), array( '%s', '%s', '%s', '%s' ) );
                if ( false === $inserted ) { return new WP_Error( 'sg_permission_insert_failed', $wpdb->last_error ); }
                $permission_id = (int) $wpdb->insert_id;
            }

            // Manager role receives the complete catalog. Personnel receives only assigned-work access.
            $manager_allowed = 1;
            $personnel_allowed = in_array( $key, array( 'app.access', 'dashboard.view', 'jobs.view', 'jobs.update_status', 'jobs.add_note' ), true ) ? 1 : 0;
            foreach ( array( 'sg_manager' => $manager_allowed, 'sg_personnel' => $personnel_allowed ) as $role => $allowed ) {
                $wpdb->replace( $roles_table, array(
                    'role_key' => $role,
                    'permission_id' => $permission_id,
                    'allowed' => $allowed,
                    'created_at' => $now,
                ), array( '%s', '%d', '%d', '%s' ) );
                if ( $wpdb->last_error ) { return new WP_Error( 'sg_role_permission_seed_failed', $wpdb->last_error ); }
            }
        }
        return true;
    }
}
