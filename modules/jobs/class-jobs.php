<?php
/** Job tracking REST controller. */
defined( 'ABSPATH' ) || exit;

final class SG_Jobs {
    private static function table( string $name = 'jobs' ): string {
        global $wpdb;
        return $wpdb->prefix . 'sg_' . $name;
    }

    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/jobs', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'listing' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'jobs.view' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'jobs.create' ) ),
        ) );
        register_rest_route( 'sg/v1', '/jobs/(?P<id>\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'detail' ), 'permission_callback' => static fn( $r ) => is_user_logged_in() && SG_Permissions::can_access_job( absint( $r['id'] ) ) ),
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( __CLASS__, 'update' ), 'permission_callback' => static fn( $r ) => is_user_logged_in() && SG_Permissions::can_access_job( absint( $r['id'] ) ) && ( SG_Permissions::current_user_can( 'jobs.assign' ) || SG_Permissions::current_user_can( 'jobs.update_status' ) ) ),
        ) );
        register_rest_route( 'sg/v1', '/jobs/(?P<id>\d+)/notes', array(
            'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'add_note' ),
            'permission_callback' => static fn( $r ) => is_user_logged_in() && SG_Permissions::can_access_job( absint( $r['id'] ) ) && SG_Permissions::current_user_can( 'jobs.add_note' ),
        ) );
        register_rest_route( 'sg/v1', '/job-options', array(
            'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'options' ),
            'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'jobs.view' ),
        ) );
    }

    private static function record( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT j.*, p.company_name, s.full_name AS assigned_name FROM ' . self::table() . ' j LEFT JOIN ' . self::table( 'parties' ) . ' p ON p.id=j.party_id LEFT JOIN ' . self::table( 'personnel' ) . ' s ON s.id=j.assigned_personnel_id WHERE j.id=%d', $id ), ARRAY_A );
        return $row ?: null;
    }

    public static function listing( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $scope = SG_Permissions::job_list_scope();
        $where = array( $scope['sql'] );
        $args = $scope['args'];
        $from = sanitize_text_field( (string) $request->get_param( 'from' ) );
        $to = sanitize_text_field( (string) $request->get_param( 'to' ) );
        $status = sanitize_key( (string) $request->get_param( 'status' ) );
        if ( $from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) { $where[] = 'j.due_date >= %s'; $args[] = $from . ' 00:00:00'; }
        if ( $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) { $where[] = 'j.due_date <= %s'; $args[] = $to . ' 23:59:59'; }
        if ( in_array( $status, array( 'new', 'assigned', 'in_progress', 'on_hold', 'completed', 'cancelled' ), true ) ) { $where[] = 'j.status = %s'; $args[] = $status; }
        $sql = 'SELECT j.id,j.job_no,j.title,j.description,j.party_id,j.assigned_personnel_id,j.start_date,j.due_date,j.priority,j.status,j.created_at,j.updated_at,p.company_name,s.full_name AS assigned_name FROM ' . self::table() . ' j LEFT JOIN ' . self::table( 'parties' ) . ' p ON p.id=j.party_id LEFT JOIN ' . self::table( 'personnel' ) . ' s ON s.id=j.assigned_personnel_id WHERE ' . implode( ' AND ', $where ) . ' ORDER BY COALESCE(j.due_date,j.start_date) ASC,j.id DESC LIMIT 500';
        $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
        return new WP_REST_Response( array( 'items' => $rows ?: array(), 'count' => count( $rows ?: array() ) ), 200 );
    }

    public static function options(): WP_REST_Response {
        if ( ! SG_Permissions::current_user_can( 'jobs.assign' ) ) {
            return new WP_REST_Response( array( 'personnel' => array(), 'customers' => array() ), 200 );
        }
        global $wpdb;
        $people = $wpdb->get_results( "SELECT id,full_name,job_title FROM " . self::table( 'personnel' ) . " WHERE status='active' ORDER BY full_name", ARRAY_A );
        $customers = $wpdb->get_results( "SELECT id,company_name FROM " . self::table( 'parties' ) . " WHERE party_type='customer' AND status='active' ORDER BY company_name", ARRAY_A );
        return new WP_REST_Response( array( 'personnel' => $people ?: array(), 'customers' => $customers ?: array() ), 200 );
    }

    private static function payload( WP_REST_Request $request, bool $creating ): array|WP_Error {
        $title = sanitize_text_field( (string) $request->get_param( 'title' ) );
        if ( $creating && '' === trim( $title ) ) { return new WP_Error( 'sg_job_title', 'Görev başlığı zorunludur.', array( 'status' => 400 ) ); }
        $status = sanitize_key( (string) $request->get_param( 'status' ) );
        $priority = sanitize_key( (string) $request->get_param( 'priority' ) );
        $data = array();
        if ( null !== $request->get_param( 'title' ) ) { $data['title'] = $title; }
        if ( null !== $request->get_param( 'description' ) ) { $data['description'] = sanitize_textarea_field( (string) $request->get_param( 'description' ) ); }
        if ( null !== $request->get_param( 'party_id' ) ) { $data['party_id'] = absint( $request->get_param( 'party_id' ) ) ?: null; }
        if ( null !== $request->get_param( 'assigned_personnel_id' ) ) { $data['assigned_personnel_id'] = absint( $request->get_param( 'assigned_personnel_id' ) ) ?: null; }
        foreach ( array( 'start_date', 'due_date' ) as $field ) {
            if ( null !== $request->get_param( $field ) ) {
                $value = sanitize_text_field( (string) $request->get_param( $field ) );
                if ( '' !== $value && ! preg_match( '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $value ) ) { return new WP_Error( 'sg_job_date', 'Başlangıç ve termin tarihlerini kontrol edin.', array( 'status' => 400 ) ); }
                $data[ $field ] = '' === $value ? null : ( 10 === strlen( $value ) ? $value . ' 00:00:00' : $value );
            }
        }
        if ( null !== $request->get_param( 'priority' ) ) {
            if ( ! in_array( $priority, array( 'low', 'normal', 'high', 'urgent' ), true ) ) { return new WP_Error( 'sg_job_priority', 'Öncelik değeri geçersiz.', array( 'status' => 400 ) ); }
            $data['priority'] = $priority;
        }
        if ( null !== $request->get_param( 'status' ) ) {
            if ( ! in_array( $status, array( 'new', 'assigned', 'in_progress', 'on_hold', 'completed', 'cancelled' ), true ) ) { return new WP_Error( 'sg_job_status', 'Görev durumu geçersiz.', array( 'status' => 400 ) ); }
            $data['status'] = $status;
        }
        if ( ! empty( $data['start_date'] ) && ! empty( $data['due_date'] ) && $data['start_date'] > $data['due_date'] ) {
            return new WP_Error( 'sg_job_date_order', 'Termin tarihi başlangıç tarihinden önce olamaz.', array( 'status' => 400 ) );
        }
        return $data;
    }

    private static function validate_references( array $data ): true|WP_Error {
        global $wpdb;
        if ( ! empty( $data['assigned_personnel_id'] ) && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . self::table( 'personnel' ) . " WHERE id=%d AND status='active'", $data['assigned_personnel_id'] ) ) ) {
            return new WP_Error( 'sg_job_personnel', 'Seçilen personel aktif değil.', array( 'status' => 400 ) );
        }
        if ( ! empty( $data['party_id'] ) && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . self::table( 'parties' ) . " WHERE id=%d AND party_type='customer' AND status='active'", $data['party_id'] ) ) ) {
            return new WP_Error( 'sg_job_customer', 'Seçilen müşteri bulunamadı.', array( 'status' => 400 ) );
        }
        return true;
    }

    private static function history( int $id, string $action, $old, $new, string $note = '' ): void {
        global $wpdb;
        $wpdb->insert( self::table( 'job_history' ), array(
            'job_id' => $id, 'action' => $action,
            'old_value' => null === $old ? null : wp_json_encode( $old ),
            'new_value' => null === $new ? null : wp_json_encode( $new ),
            'note' => $note, 'performed_by' => get_current_user_id(), 'created_at' => current_time( 'mysql', true ),
        ) );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $data = self::payload( $request, true );
        if ( is_wp_error( $data ) ) { return $data; }
        $references = self::validate_references( $data );
        if ( is_wp_error( $references ) ) { return $references; }
        $now = current_time( 'mysql', true );
        $lock = 'sg_job_no_' . wp_date( 'Y' );
        if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock ) ) ) { return new WP_Error( 'sg_job_lock', 'Görev numarası üretilemedi; tekrar deneyin.', array( 'status' => 503 ) ); }
        $prefix = 'IS-' . wp_date( 'Y' ) . '-';
        $last = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT job_no FROM ' . self::table() . ' WHERE job_no LIKE %s ORDER BY job_no DESC LIMIT 1', $wpdb->esc_like( $prefix ) . '%' ) );
        $number = $last ? (int) substr( $last, strlen( $prefix ) ) + 1 : 1;
        $job_no = $prefix . str_pad( (string) $number, 5, '0', STR_PAD_LEFT );
        $data += array( 'job_no' => $job_no, 'created_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now );
        if ( ! isset( $data['priority'] ) ) { $data['priority'] = 'normal'; }
        if ( ! isset( $data['status'] ) ) { $data['status'] = ! empty( $data['assigned_personnel_id'] ) ? 'assigned' : 'new'; }
        if ( false === $wpdb->insert( self::table(), $data ) ) {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
            return new WP_Error( 'sg_job_create', 'Görev kaydedilemedi.', array( 'status' => 500 ) );
        }
        $id = (int) $wpdb->insert_id;
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
        self::history( $id, 'created', null, $data );
        return new WP_REST_Response( array( 'id' => $id, 'job_no' => $job_no, 'message' => 'Görev oluşturuldu.' ), 201 );
    }

    public static function detail( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $id = absint( $request['id'] );
        $job = self::record( $id );
        if ( ! $job ) { return new WP_Error( 'sg_job_missing', 'Görev bulunamadı.', array( 'status' => 404 ) ); }
        $notes = $wpdb->get_results( $wpdb->prepare( 'SELECT n.id,n.note,n.created_at,u.display_name FROM ' . self::table( 'job_notes' ) . ' n LEFT JOIN ' . $wpdb->users . ' u ON u.ID=n.created_by WHERE n.job_id=%d ORDER BY n.created_at DESC LIMIT 100', $id ), ARRAY_A );
        $history = $wpdb->get_results( $wpdb->prepare( 'SELECT h.action,h.old_value,h.new_value,h.note,h.created_at,u.display_name FROM ' . self::table( 'job_history' ) . ' h LEFT JOIN ' . $wpdb->users . ' u ON u.ID=h.performed_by WHERE h.job_id=%d ORDER BY h.created_at DESC LIMIT 100', $id ), ARRAY_A );
        return new WP_REST_Response( array( 'job' => $job, 'notes' => $notes ?: array(), 'history' => $history ?: array() ), 200 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $id = absint( $request['id'] );
        $old = self::record( $id );
        if ( ! $old ) { return new WP_Error( 'sg_job_missing', 'Görev bulunamadı.', array( 'status' => 404 ) ); }
        $data = self::payload( $request, false );
        if ( is_wp_error( $data ) ) { return $data; }
        if ( ! SG_Permissions::current_user_can( 'jobs.assign' ) ) {
            $allowed = array();
            if ( isset( $data['status'] ) ) { $allowed['status'] = $data['status']; }
            $data = $allowed;
        }
        if ( ! $data ) { return new WP_Error( 'sg_job_empty', 'Güncellenecek alan bulunamadı.', array( 'status' => 400 ) ); }
        $references = self::validate_references( $data );
        if ( is_wp_error( $references ) ) { return $references; }
        $data['updated_at'] = current_time( 'mysql', true );
        if ( 'completed' === ( $data['status'] ?? '' ) ) { $data['completed_at'] = $data['updated_at']; }
        if ( false === $wpdb->update( self::table(), $data, array( 'id' => $id ) ) ) { return new WP_Error( 'sg_job_update', 'Görev güncellenemedi.', array( 'status' => 500 ) ); }
        self::history( $id, 'updated', $old, $data );
        return new WP_REST_Response( array( 'message' => 'Görev güncellendi.' ), 200 );
    }

    public static function add_note( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $id = absint( $request['id'] );
        $note = sanitize_textarea_field( (string) $request->get_param( 'note' ) );
        if ( '' === trim( $note ) ) { return new WP_Error( 'sg_job_note', 'Not alanı boş olamaz.', array( 'status' => 400 ) ); }
        if ( false === $wpdb->insert( self::table( 'job_notes' ), array( 'job_id' => $id, 'note' => $note, 'visibility' => 'internal', 'created_by' => get_current_user_id(), 'created_at' => current_time( 'mysql', true ) ) ) ) { return new WP_Error( 'sg_job_note_save', 'Not kaydedilemedi.', array( 'status' => 500 ) ); }
        self::history( $id, 'note_added', null, null, $note );
        return new WP_REST_Response( array( 'message' => 'Not eklendi.' ), 201 );
    }
}
