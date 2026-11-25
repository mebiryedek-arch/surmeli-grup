<?php
/** Simple personnel records REST controller. */
defined( 'ABSPATH' ) || exit;

final class SG_Personnel {
    public static function init(): void { add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) ); }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/personnel', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'listing' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'personnel.view' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'personnel.manage' ) ),
        ) );
        register_rest_route( 'sg/v1', '/personnel/(?P<id>\\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'detail' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'personnel.view' ) ),
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( __CLASS__, 'update' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'personnel.manage' ) ),
        ) );
    }

    private static function table(): string { global $wpdb; return $wpdb->prefix . 'sg_personnel'; }

    private static function payload( WP_REST_Request $request ): array|WP_Error {
        $name = sanitize_text_field( (string) $request->get_param( 'full_name' ) );
        if ( '' === trim( $name ) ) { return new WP_Error( 'sg_personnel_name', 'Personel adı soyadı zorunludur.', array( 'status' => 400 ) ); }

        $email = sanitize_email( (string) $request->get_param( 'email' ) );
        if ( '' !== $email && ! is_email( $email ) ) { return new WP_Error( 'sg_personnel_email', 'E-posta adresi geçerli değil.', array( 'status' => 400 ) ); }

        $phone = preg_replace( '/\\D+/', '', (string) $request->get_param( 'phone' ) );
        if ( 10 === strlen( $phone ) && str_starts_with( $phone, '5' ) ) { $phone = '0' . $phone; }
        if ( '' !== $phone && ( 11 !== strlen( $phone ) || ! str_starts_with( $phone, '0' ) ) ) {
            return new WP_Error( 'sg_personnel_phone', 'Telefon numarası 0 (555) 555 55 55 biçiminde olmalıdır.', array( 'status' => 400 ) );
        }

        $date = sanitize_text_field( (string) $request->get_param( 'employment_date' ) );
        if ( '' !== $date && ( ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $date ) || ! checkdate( (int) substr( $date, 5, 2 ), (int) substr( $date, 8, 2 ), (int) substr( $date, 0, 4 ) ) ) ) {
            return new WP_Error( 'sg_personnel_date', 'İşe giriş tarihi geçerli değil.', array( 'status' => 400 ) );
        }

        $user_id = absint( $request->get_param( 'user_id' ) );
        if ( $user_id && ! get_user_by( 'id', $user_id ) ) { return new WP_Error( 'sg_personnel_user', 'Seçilen WordPress kullanıcısı bulunamadı.', array( 'status' => 400 ) ); }

        return array(
            'user_id' => $user_id ?: null,
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email,
            'job_title' => sanitize_text_field( (string) $request->get_param( 'job_title' ) ),
            'department' => sanitize_text_field( (string) $request->get_param( 'department' ) ),
            'employment_date' => '' === $date ? null : $date,
            'status' => in_array( $request->get_param( 'status' ), array( 'active', 'passive' ), true ) ? $request->get_param( 'status' ) : 'active',
            'description' => sanitize_textarea_field( (string) $request->get_param( 'description' ) ),
        );
    }

    private static function duplicate_user( ?int $user_id, int $exclude = 0 ): bool {
        if ( ! $user_id ) { return false; }
        global $wpdb;
        $sql = 'SELECT id FROM ' . self::table() . ' WHERE user_id=%d';
        $args = array( $user_id );
        if ( $exclude ) { $sql .= ' AND id!=%d'; $args[] = $exclude; }
        return (bool) $wpdb->get_var( $wpdb->prepare( $sql . ' LIMIT 1', $args ) );
    }

    public static function listing( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $where = array( '1=1' ); $args = array();
        $search = sanitize_text_field( (string) $request->get_param( 'search' ) );
        if ( '' !== $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where[] = '(full_name LIKE %s OR phone LIKE %s OR email LIKE %s OR job_title LIKE %s OR department LIKE %s)';
            array_push( $args, $like, $like, $like, $like, $like );
        }
        $status = sanitize_key( (string) $request->get_param( 'status' ) );
        if ( in_array( $status, array( 'active', 'passive' ), true ) ) { $where[] = 'status=%s'; $args[] = $status; }
        $sql = 'SELECT id,user_id,full_name,phone,email,job_title,department,employment_date,status,description,created_at,updated_at FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY status ASC,full_name ASC LIMIT 300';
        $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
        return new WP_REST_Response( array( 'items' => $rows ?: array(), 'count' => count( $rows ?: array() ) ), 200 );
    }

    public static function detail( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT id,user_id,full_name,phone,email,job_title,department,employment_date,status,description FROM ' . self::table() . ' WHERE id=%d LIMIT 1', absint( $request['id'] ) ), ARRAY_A );
        return $row ? new WP_REST_Response( $row, 200 ) : new WP_Error( 'sg_personnel_missing', 'Personel kaydı bulunamadı.', array( 'status' => 404 ) );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $data = self::payload( $request );
        if ( is_wp_error( $data ) ) { return $data; }
        if ( self::duplicate_user( $data['user_id'] ) ) { return new WP_Error( 'sg_personnel_user_duplicate', 'Bu WordPress kullanıcısı başka bir personel kartına bağlı.', array( 'status' => 409 ) ); }
        $now = current_time( 'mysql', true );
        $data += array( 'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now );
        if ( false === $wpdb->insert( self::table(), $data ) ) { return new WP_Error( 'sg_personnel_create', 'Personel kaydı oluşturulamadı.', array( 'status' => 500 ) ); }
        self::audit( 'personnel.created', (int) $wpdb->insert_id, $data['full_name'] );
        return new WP_REST_Response( array( 'id' => (int) $wpdb->insert_id, 'message' => 'Personel kaydı oluşturuldu.' ), 201 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $id = absint( $request['id'] );
        if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE id=%d', $id ) ) ) { return new WP_Error( 'sg_personnel_missing', 'Personel kaydı bulunamadı.', array( 'status' => 404 ) ); }
        $data = self::payload( $request );
        if ( is_wp_error( $data ) ) { return $data; }
        if ( self::duplicate_user( $data['user_id'], $id ) ) { return new WP_Error( 'sg_personnel_user_duplicate', 'Bu WordPress kullanıcısı başka bir personel kartına bağlı.', array( 'status' => 409 ) ); }
        $data['updated_by'] = get_current_user_id(); $data['updated_at'] = current_time( 'mysql', true );
        if ( false === $wpdb->update( self::table(), $data, array( 'id' => $id ) ) ) { return new WP_Error( 'sg_personnel_update', 'Personel kaydı güncellenemedi.', array( 'status' => 500 ) ); }
        self::audit( 'personnel.updated', $id, $data['full_name'] );
        return new WP_REST_Response( array( 'id' => $id, 'message' => 'Personel bilgileri güncellendi.' ), 200 );
    }

    private static function audit( string $event, int $id, string $name ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'sg_audit_logs';
        if ( ! SG_Migrator::table_exists( $table ) ) { return; }
        $wpdb->insert( $table, array(
            'user_id' => get_current_user_id(), 'event_key' => $event, 'object_type' => 'personnel',
            'object_id' => $id, 'request_id' => wp_generate_uuid4(), 'details' => wp_json_encode( array( 'full_name' => $name ) ),
            'created_at' => current_time( 'mysql', true ),
        ) );
    }
}
