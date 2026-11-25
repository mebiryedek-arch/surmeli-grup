<?php
/** Dashboard read model. All figures are calculated from live application tables. */
defined( 'ABSPATH' ) || exit;

final class SG_Dashboard {
    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/dashboard', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'rest_summary' ),
            'permission_callback' => static function () {
                return is_user_logged_in() && SG_Permissions::current_user_can( 'dashboard.view' );
            },
        ) );
    }

    public static function rest_summary(): WP_REST_Response {
        global $wpdb;
        $p = $wpdb->prefix;
        $parties = $p . 'sg_parties';
        $jobs = $p . 'sg_jobs';
        $now = current_time( 'mysql' );
        $today = current_time( 'Y-m-d' );

        $summary = array(
            'customers' => 0,
            'suppliers' => 0,
            'jobs_total' => 0,
            'jobs_open' => 0,
            'jobs_overdue' => 0,
            'jobs_completed' => 0,
            'recent_jobs' => array(),
            'generated_at' => $now,
            'financial_available' => false,
        );

        if ( SG_Migrator::table_exists( $parties ) ) {
            $summary['customers'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$parties} WHERE party_type = 'customer' AND status = 'active'" );
            $summary['suppliers'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$parties} WHERE party_type = 'supplier' AND status = 'active'" );
        }

        if ( SG_Migrator::table_exists( $jobs ) ) {
            $scope = SG_Permissions::job_list_scope();
            $where = $scope['sql'];
            $args = $scope['args'];
            $summary['jobs_total'] = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$jobs} WHERE {$where}" );
            $summary['jobs_open'] = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs} WHERE {$where} AND status IN ('new','assigned','in_progress','on_hold')", $args ) : "SELECT COUNT(*) FROM {$jobs} WHERE {$where} AND status IN ('new','assigned','in_progress','on_hold')" );
            $summary['jobs_completed'] = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs} WHERE {$where} AND status = 'completed'", $args ) : "SELECT COUNT(*) FROM {$jobs} WHERE {$where} AND status = 'completed'" );
            $summary['jobs_overdue'] = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs} WHERE {$where} AND due_date IS NOT NULL AND due_date < %s AND status NOT IN ('completed','cancelled')", array_merge( $args, array( $now ) ) ) : $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs} WHERE {$where} AND due_date IS NOT NULL AND due_date < %s AND status NOT IN ('completed','cancelled')", $now ) );

            $recent_sql = "SELECT id, job_no, title, status, priority, due_date, created_at FROM {$jobs} WHERE {$where} ORDER BY created_at DESC LIMIT 6";
            $rows = $args ? $wpdb->get_results( $wpdb->prepare( $recent_sql, $args ), ARRAY_A ) : $wpdb->get_results( $recent_sql, ARRAY_A );
            foreach ( (array) $rows as $row ) {
                $summary['recent_jobs'][] = array(
                    'id' => (int) $row['id'],
                    'job_no' => (string) $row['job_no'],
                    'title' => (string) $row['title'],
                    'status' => (string) $row['status'],
                    'priority' => (string) $row['priority'],
                    'due_date' => $row['due_date'],
                    'created_at' => (string) $row['created_at'],
                );
            }
        }

        return new WP_REST_Response( $summary, 200 );
    }
}
