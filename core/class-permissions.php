<?php
/** Application roles, permissions and record-level access rules. */
defined( 'ABSPATH' ) || exit;

final class SG_Permissions {
    public const ROLE_MANAGER = 'sg_manager';
    public const ROLE_PERSONNEL = 'sg_personnel';

    private const PERMISSIONS = array(
        'app.access' => 'Uygulamaya giriş',
        'dashboard.view' => 'Genel bakışı görüntüleme',
        'customers.view' => 'Müşterileri görüntüleme',
        'customers.create' => 'Müşteri oluşturma',
        'customers.edit' => 'Müşteri düzenleme',
        'suppliers.view' => 'Tedarikçileri görüntüleme',
        'suppliers.create' => 'Tedarikçi oluşturma',
        'suppliers.edit' => 'Tedarikçi düzenleme',
        'accounts.view' => 'Cari hesapları görüntüleme',
        'accounts.adjust' => 'Cari borç/alacak hareketi oluşturma',
        'accounts.collect' => 'Tahsilat kaydı oluşturma',
        'accounts.payment' => 'Ödeme kaydı oluşturma',
        'cash.view' => 'Kasa ve bankayı görüntüleme',
        'cash.create' => 'Kasa/banka hareketi oluşturma',
        'cash.transfer' => 'Kasa/banka transferi',
        'income_expense.view' => 'Gelir ve giderleri görüntüleme',
        'income_expense.create' => 'Gelir/gider kaydı oluşturma',
        'personnel.view' => 'Personeli görüntüleme',
        'personnel.manage' => 'Personel kartı oluşturma ve düzenleme',
        'jobs.view' => 'İşleri görüntüleme',
        'jobs.view_all' => 'Tüm işleri görüntüleme',
        'jobs.create' => 'İş oluşturma',
        'jobs.assign' => 'İş atama',
        'jobs.update_status' => 'Atanmış iş durumunu güncelleme',
        'jobs.add_note' => 'İşe not ekleme',
        'reports.view' => 'Raporları görüntüleme',
        'settings.manage' => 'Uygulama ayarlarını yönetme',
        'permissions.manage' => 'Rol ve izinleri yönetme',
    );

    public static function init(): void {
        self::ensure_roles();
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function ensure_roles(): void {
        $manager_caps = array_fill_keys( array_keys( self::PERMISSIONS ), true );
        $manager_caps['read'] = true;
        $personnel_caps = array(
            'read' => true,
            'app.access' => true,
            'dashboard.view' => true,
            'jobs.view' => true,
            'jobs.update_status' => true,
            'jobs.add_note' => true,
        );
        add_role( self::ROLE_MANAGER, 'Sürmeli Grup Yöneticisi', $manager_caps );
        add_role( self::ROLE_PERSONNEL, 'Sürmeli Grup Personeli', $personnel_caps );
        // Refresh capabilities on existing custom roles after plugin upgrades.
        $manager = get_role( self::ROLE_MANAGER );
        if ( $manager ) {
            foreach ( $manager_caps as $cap => $grant ) { $manager->add_cap( $cap, $grant ); }
        }
        $personnel = get_role( self::ROLE_PERSONNEL );
        if ( $personnel ) {
            foreach ( $personnel_caps as $cap => $grant ) { $personnel->add_cap( $cap, $grant ); }
        }
    }

    public static function permission_catalog(): array { return self::PERMISSIONS; }

    public static function current_user_can( string $permission ): bool {
        if ( ! array_key_exists( $permission, self::PERMISSIONS ) ) { return false; }
        if ( current_user_can( 'manage_options' ) ) { return true; }

        $user = wp_get_current_user();
        if ( empty( $user->roles ) ) { return false; }
        global $wpdb;
        $permissions_table = $wpdb->prefix . 'sg_permissions';
        $role_permissions_table = $wpdb->prefix . 'sg_role_permissions';
        if ( SG_Migrator::table_exists( $permissions_table ) && SG_Migrator::table_exists( $role_permissions_table ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $user->roles ), '%s' ) );
            $args = array_merge( array( $permission ), array_values( $user->roles ) );
            $grant = $wpdb->get_var( $wpdb->prepare(
                "SELECT MAX(rp.allowed) FROM {$role_permissions_table} rp INNER JOIN {$permissions_table} p ON p.id = rp.permission_id WHERE p.permission_key = %s AND rp.role_key IN ({$placeholders})",
                $args
            ) );
            if ( null !== $grant ) { return 1 === (int) $grant; }
        }
        return current_user_can( $permission );
    }

    public static function current_personnel_id(): int {
        if ( ! is_user_logged_in() ) { return 0; }
        global $wpdb;
        $table = $wpdb->prefix . 'sg_personnel';
        if ( ! SG_Migrator::table_exists( $table ) ) { return 0; }
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND status = 'active' LIMIT 1",
            get_current_user_id()
        ) );
    }

    /** A personnel user may only access jobs assigned to their linked personnel card. */
    public static function can_access_job( int $job_id ): bool {
        if ( $job_id < 1 || ! self::current_user_can( 'jobs.view' ) ) { return false; }
        if ( self::current_user_can( 'jobs.view_all' ) ) { return true; }
        $personnel_id = self::current_personnel_id();
        if ( ! $personnel_id ) { return false; }
        global $wpdb;
        $table = $wpdb->prefix . 'sg_jobs';
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE id = %d AND assigned_personnel_id = %d LIMIT 1",
            $job_id,
            $personnel_id
        ) ) > 0;
    }

    /** SQL fragment for list queries; callers must append the returned prepared arguments. */
    public static function job_list_scope(): array {
        if ( self::current_user_can( 'jobs.view_all' ) ) { return array( 'sql' => '1=1', 'args' => array() ); }
        $personnel_id = self::current_personnel_id();
        if ( ! $personnel_id || ! self::current_user_can( 'jobs.view' ) ) { return array( 'sql' => '1=0', 'args' => array() ); }
        return array( 'sql' => 'assigned_personnel_id = %d', 'args' => array( $personnel_id ) );
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/me', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'rest_me' ),
            'permission_callback' => static function () { return is_user_logged_in() && self::current_user_can( 'app.access' ); },
        ) );
        register_rest_route( 'sg/v1', '/permissions', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'rest_permissions' ),
            'permission_callback' => static function () { return is_user_logged_in() && self::current_user_can( 'app.access' ); },
        ) );
    }

    public static function rest_me(): WP_REST_Response {
        $user = wp_get_current_user();
        return new WP_REST_Response( array(
            'id' => (int) $user->ID,
            'displayName' => $user->display_name,
            'roles' => array_values( $user->roles ),
            'personnelId' => self::current_personnel_id(),
        ), 200 );
    }

    public static function rest_permissions(): WP_REST_Response {
        $granted = array();
        foreach ( array_keys( self::PERMISSIONS ) as $permission ) {
            if ( self::current_user_can( $permission ) ) { $granted[] = $permission; }
        }
        return new WP_REST_Response( array( 'permissions' => $granted ), 200 );
    }
}
