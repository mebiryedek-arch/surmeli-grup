<?php
/** Customer type settings and REST endpoints. */
defined( 'ABSPATH' ) || exit;
final class SG_Customer_Types {
    private const OPTION = 'sg_customer_types';
    public static function init(): void { add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) ); }
    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/customer-types', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'list_types' ), 'permission_callback' => static fn() => is_user_logged_in() && ( SG_Permissions::current_user_can( 'customers.view' ) || SG_Permissions::current_user_can( 'settings.manage' ) ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create_type' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'settings.manage' ) ),
        ) );
        register_rest_route( 'sg/v1', '/customer-types/(?P<key>[a-z0-9_-]{1,20})', array(
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( __CLASS__, 'update_type' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'settings.manage' ) ),
            array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( __CLASS__, 'delete_type' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'settings.manage' ) ),
        ) );
    }
    public static function all(): array {
        $types = get_option( self::OPTION, array() );
        return is_array( $types ) ? array_values( $types ) : array();
    }
    public static function list_types(): WP_REST_Response { return new WP_REST_Response( array( 'items' => self::all() ), 200 ); }
    private static function clean_name( WP_REST_Request $r ): string|WP_Error {
        $name = trim( sanitize_text_field( (string) $r->get_param( 'name' ) ) );
        if ( '' === $name || mb_strlen( $name ) > 60 ) { return new WP_Error( 'sg_customer_type_name', 'Tür adı 1-60 karakter arasında olmalıdır.', array( 'status' => 400 ) ); }
        return $name;
    }
    private static function make_key( string $name ): string {
        $key = remove_accents( strtolower( $name ) );
        $key = sanitize_title( $key );
        return substr( preg_replace( '/[^a-z0-9_-]/', '', $key ), 0, 20 );
    }
    public static function create_type( WP_REST_Request $r ): WP_REST_Response|WP_Error {
        $name = self::clean_name( $r ); if ( is_wp_error( $name ) ) { return $name; }
        $key = self::make_key( $name ); if ( '' === $key ) { return new WP_Error( 'sg_customer_type_key', 'Bu tür adı için geçerli bir anahtar oluşturulamadı.', array( 'status' => 400 ) ); }
        $types = self::all();
        foreach ( $types as $type ) { if ( strcasecmp( $type['name'], $name ) === 0 || $type['key'] === $key ) { return new WP_Error( 'sg_customer_type_duplicate', 'Bu müşteri türü zaten kayıtlı veya benzer bir anahtar kullanıyor.', array( 'status' => 409 ) ); } }
        $types[] = array( 'key' => $key, 'name' => $name );
        update_option( self::OPTION, $types, false );
        return new WP_REST_Response( array( 'items' => $types, 'message' => 'Müşteri türü eklendi.' ), 201 );
    }
    public static function update_type( WP_REST_Request $r ): WP_REST_Response|WP_Error {
        $key = sanitize_key( (string) $r['key'] ); $name = self::clean_name( $r ); if ( is_wp_error( $name ) ) { return $name; }
        $types = self::all(); $found = false;
        foreach ( $types as &$type ) {
            if ( $type['key'] === $key ) { $found = true; }
            elseif ( strcasecmp( $type['name'], $name ) === 0 ) { return new WP_Error( 'sg_customer_type_duplicate', 'Bu müşteri türü adı zaten kullanılıyor.', array( 'status' => 409 ) ); }
        }
        unset( $type );
        if ( ! $found ) { return new WP_Error( 'sg_customer_type_missing', 'Müşteri türü bulunamadı.', array( 'status' => 404 ) ); }
        foreach ( $types as &$type ) { if ( $type['key'] === $key ) { $type['name'] = $name; } } unset( $type );
        update_option( self::OPTION, $types, false );
        return new WP_REST_Response( array( 'items' => $types, 'message' => 'Müşteri türü güncellendi.' ), 200 );
    }
    public static function delete_type( WP_REST_Request $r ): WP_REST_Response|WP_Error {
        global $wpdb;
        $key = sanitize_key( (string) $r['key'] ); $types = self::all(); $found = false;
        foreach ( $types as $type ) { if ( $type['key'] === $key ) { $found = true; break; } }
        if ( ! $found ) { return new WP_Error( 'sg_customer_type_missing', 'Müşteri türü bulunamadı.', array( 'status' => 404 ) ); }
        $used = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sg_parties WHERE party_type='customer' AND party_kind=%s", $key ) );
        if ( $used > 0 ) { return new WP_Error( 'sg_customer_type_in_use', sprintf( 'Bu tür %d müşteri kaydında kullanılıyor. Önce müşterilerin türünü değiştirin.', $used ), array( 'status' => 409 ) ); }
        $types = array_values( array_filter( $types, static fn( $type ) => $type['key'] !== $key ) );
        update_option( self::OPTION, $types, false );
        return new WP_REST_Response( array( 'items' => $types, 'message' => 'Müşteri türü silindi.' ), 200 );
    }
}
