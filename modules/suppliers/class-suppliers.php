<?php
/** Supplier records REST controller. */
defined( 'ABSPATH' ) || exit;
final class SG_Suppliers {
    public static function init(): void { add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) ); }
    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/suppliers', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'list_suppliers' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'suppliers.view' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create_supplier' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'suppliers.create' ) ),
        ) );
        register_rest_route( 'sg/v1', '/suppliers/(?P<id>\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'get_supplier' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'suppliers.view' ) ),
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( __CLASS__, 'update_supplier' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'suppliers.edit' ) ),
        ) );
    }
    private static function table(): string { global $wpdb; return $wpdb->prefix . 'sg_parties'; }
    private static function normalize_phone( string $phone ): string {
        $digits = preg_replace( '/\D+/', '', $phone );
        if ( '' === $digits ) { return ''; }
        if ( 10 === strlen( $digits ) && str_starts_with( $digits, '5' ) ) { $digits = '0' . $digits; }
        return $digits;
    }
    private static function next_code(): string|WP_Error {
        global $wpdb;
        $year = wp_date( 'Y' );
        $prefix = 'T-' . $year . '-';
        $lock = 'sg_supplier_code_' . $year;
        $locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) );
        if ( 1 !== $locked ) { return new WP_Error( 'sg_supplier_code_lock', 'Kayıt numarası şu anda oluşturulamıyor. Lütfen tekrar deneyin.', array( 'status' => 503 ) ); }
        $like = $wpdb->esc_like( $prefix ) . '%';
        $last = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT party_code FROM ' . self::table() . ' WHERE party_type=%s AND party_code LIKE %s ORDER BY party_code DESC LIMIT 1', 'supplier', $like ) );
        $number = $last ? (int) substr( $last, strlen( $prefix ) ) + 1 : 1;
        return $prefix . str_pad( (string) $number, 5, '0', STR_PAD_LEFT );
    }
    private static function payload( WP_REST_Request $r ): array|WP_Error {
        $name = sanitize_text_field( (string) $r->get_param( 'company_name' ) );
        if ( '' === trim( $name ) ) { return new WP_Error( 'sg_supplier_name', 'Tedarikçi / firma adı zorunludur.', array( 'status' => 400 ) ); }
        $email = sanitize_email( (string) $r->get_param( 'email' ) );
        if ( $email && ! is_email( $email ) ) { return new WP_Error( 'sg_supplier_email', 'E-posta adresi geçerli değil.', array( 'status' => 400 ) ); }
        $phone = self::normalize_phone( (string) $r->get_param( 'phone' ) );
        if ( '' !== $phone && ( 11 !== strlen( $phone ) || ! str_starts_with( $phone, '0' ) ) ) { return new WP_Error( 'sg_supplier_phone', 'Telefon numarası 0 (555) 555 55 55 biçiminde olmalıdır.', array( 'status' => 400 ) ); }
        $kind = sanitize_key( (string) $r->get_param( 'party_kind' ) );
        return array(
            'party_kind' => in_array( $kind, array( 'corporate', 'individual' ), true ) ? $kind : 'corporate',
            'company_name' => $name,
            'contact_name' => sanitize_text_field( (string) $r->get_param( 'contact_name' ) ),
            'phone' => $phone,
            'email' => $email,
            'address' => sanitize_textarea_field( (string) $r->get_param( 'address' ) ),
            'tax_office' => sanitize_text_field( (string) $r->get_param( 'tax_office' ) ),
            'tax_number' => substr( preg_replace( '/\D+/', '', (string) $r->get_param( 'tax_number' ) ), 0, 32 ),
            'description' => sanitize_textarea_field( (string) $r->get_param( 'description' ) ),
            'status' => in_array( $r->get_param( 'status' ), array( 'active', 'passive' ), true ) ? $r->get_param( 'status' ) : 'active',
        );
    }
    public static function list_suppliers( WP_REST_Request $r ): WP_REST_Response {
        global $wpdb; $where = array( "party_type = 'supplier'" ); $args = array();
        $search = sanitize_text_field( (string) $r->get_param( 'search' ) );
        if ( $search !== '' ) { $like = '%' . $wpdb->esc_like( $search ) . '%'; $where[] = '(company_name LIKE %s OR contact_name LIKE %s OR phone LIKE %s OR party_code LIKE %s OR tax_number LIKE %s)'; array_push( $args, $like, $like, $like, $like, $like ); }
        $status = sanitize_key( (string) $r->get_param( 'status' ) );
        if ( in_array( $status, array( 'active', 'passive' ), true ) ) { $where[] = 'status = %s'; $args[] = $status; }
        $sql = 'SELECT id,party_code,party_kind,company_name,contact_name,phone,email,tax_office,tax_number,status,created_at FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY company_name ASC LIMIT 250';
        $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
        return new WP_REST_Response( array( 'items' => $rows ?: array(), 'count' => count( $rows ?: array() ) ), 200 );
    }
    public static function get_supplier( WP_REST_Request $r ): WP_REST_Response|WP_Error {
        global $wpdb; $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id=%d AND party_type='supplier' LIMIT 1", absint( $r['id'] ) ), ARRAY_A );
        return $row ? new WP_REST_Response( $row, 200 ) : new WP_Error( 'sg_supplier_missing', 'Tedarikçi kaydı bulunamadı.', array( 'status' => 404 ) );
    }
    public static function create_supplier( WP_REST_Request $r ): WP_REST_Response|WP_Error {
        global $wpdb; $data = self::payload( $r ); if ( is_wp_error( $data ) ) { return $data; }
        $duplicate = self::duplicate( $data ); if ( $duplicate ) { return new WP_Error( 'sg_supplier_duplicate', 'Bu telefon veya vergi numarası başka bir tedarikçi kaydında kullanılıyor: ' . $duplicate, array( 'status' => 409 ) ); }
        $code = self::next_code(); if ( is_wp_error( $code ) ) { return $code; }
        $now = current_time( 'mysql', true ); $data += array( 'party_code' => $code, 'party_type' => 'supplier', 'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now );
        if ( false === $wpdb->insert( self::table(), $data ) ) { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'sg_supplier_code_' . wp_date( 'Y' ) ) ); return new WP_Error( 'sg_supplier_insert', 'Tedarikçi kaydedilemedi. Lütfen tekrar deneyin.', array( 'status' => 500 ) ); }
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'sg_supplier_code_' . wp_date( 'Y' ) ) );
        self::audit( 'supplier.created', (int) $wpdb->insert_id, array( 'party_code' => $data['party_code'], 'company_name' => $data['company_name'] ) );
        return new WP_REST_Response( array( 'id' => (int) $wpdb->insert_id, 'party_code' => $data['party_code'], 'message' => 'Tedarikçi kaydedildi.' ), 201 );
    }
    public static function update_supplier( WP_REST_Request $r ): WP_REST_Response|WP_Error {
        global $wpdb; $id = absint( $r['id'] );
        if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . self::table() . " WHERE id=%d AND party_type='supplier'", $id ) ) ) { return new WP_Error( 'sg_supplier_missing', 'Tedarikçi kaydı bulunamadı.', array( 'status' => 404 ) ); }
        $data = self::payload( $r ); if ( is_wp_error( $data ) ) { return $data; }
        $duplicate = self::duplicate( $data, $id ); if ( $duplicate ) { return new WP_Error( 'sg_supplier_duplicate', 'Bu telefon veya vergi numarası başka bir tedarikçi kaydında kullanılıyor: ' . $duplicate, array( 'status' => 409 ) ); }
        $data['updated_by'] = get_current_user_id(); $data['updated_at'] = current_time( 'mysql', true );
        if ( false === $wpdb->update( self::table(), $data, array( 'id' => $id, 'party_type' => 'supplier' ) ) ) { return new WP_Error( 'sg_supplier_update', 'Tedarikçi güncellenemedi. Lütfen tekrar deneyin.', array( 'status' => 500 ) ); }
        self::audit( 'supplier.updated', $id, array( 'company_name' => $data['company_name'], 'status' => $data['status'] ) );
        return new WP_REST_Response( array( 'id' => $id, 'message' => 'Tedarikçi bilgileri güncellendi.' ), 200 );
    }
    private static function audit( string $event, int $id, array $details ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'sg_audit_logs';
        if ( ! SG_Migrator::table_exists( $table ) ) { return; }
        $wpdb->insert( $table, array(
            'user_id' => get_current_user_id(), 'event_key' => $event, 'object_type' => 'supplier',
            'object_id' => $id, 'request_id' => wp_generate_uuid4(),
            'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null,
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : null,
            'details' => wp_json_encode( $details ), 'created_at' => current_time( 'mysql', true ),
        ) );
    }

    private static function duplicate( array $data, int $exclude = 0 ): string {
        global $wpdb; $parts = array(); $args = array();
        if ( $data['phone'] !== '' ) { $parts[] = 'phone=%s'; $args[] = $data['phone']; }
        if ( $data['tax_number'] !== '' ) { $parts[] = 'tax_number=%s'; $args[] = $data['tax_number']; }
        if ( ! $parts ) { return ''; }
        $sql = "SELECT company_name FROM " . self::table() . " WHERE party_type='supplier' AND (" . implode( ' OR ', $parts ) . ')';
        if ( $exclude ) { $sql .= ' AND id!=%d'; $args[] = $exclude; }
        return (string) $wpdb->get_var( $wpdb->prepare( $sql . ' LIMIT 1', $args ) );
    }
}
