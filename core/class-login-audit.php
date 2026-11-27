<?php
/** Successful login audit trail for administrators and the signed-in portal user. */
defined( 'ABSPATH' ) || exit;

final class SG_Login_Audit {
    private const META_KEY = '_sg_login_history';
    private const MAX_ENTRIES = 20;

    public static function init(): void {
        add_action( 'wp_login', array( __CLASS__, 'record_login' ), 10, 2 );
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function record_login( string $user_login, WP_User $user ): void {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $history = get_user_meta( $user->ID, self::META_KEY, true );
        $history = is_array( $history ) ? $history : array();
        array_unshift( $history, array(
            'time' => current_time( 'mysql' ),
            'timestamp' => current_time( 'timestamp' ),
            'ip' => self::client_ip(),
            'device' => self::device_label( $ua ),
            'browser' => self::browser_label( $ua ),
            'os' => self::os_label( $ua ),
        ) );
        update_user_meta( $user->ID, self::META_KEY, array_slice( $history, 0, self::MAX_ENTRIES ) );
    }

    private static function client_ip(): string {
        // Use the direct peer address; do not trust spoofable forwarded headers.
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    private static function device_label( string $ua ): string {
        if ( preg_match( '/iPad|Tablet/i', $ua ) ) return 'Tablet';
        if ( preg_match( '/Mobile|Android|iPhone|iPod/i', $ua ) ) return 'Telefon';
        return 'Bilgisayar';
    }

    private static function browser_label( string $ua ): string {
        if ( preg_match( '/Edg\//i', $ua ) ) return 'Microsoft Edge';
        if ( preg_match( '/OPR\//i', $ua ) ) return 'Opera';
        if ( preg_match( '/Firefox\//i', $ua ) ) return 'Firefox';
        if ( preg_match( '/Chrome\//i', $ua ) && ! preg_match( '/Edg\//i', $ua ) ) return 'Google Chrome';
        if ( preg_match( '/Safari\//i', $ua ) && ! preg_match( '/Chrome\//i', $ua ) ) return 'Safari';
        return 'Bilinmeyen tarayıcı';
    }

    private static function os_label( string $ua ): string {
        if ( stripos( $ua, 'Windows' ) !== false ) return 'Windows';
        if ( stripos( $ua, 'iPhone' ) !== false || stripos( $ua, 'iPad' ) !== false ) return 'iOS';
        if ( stripos( $ua, 'Android' ) !== false ) return 'Android';
        if ( stripos( $ua, 'Mac OS' ) !== false || stripos( $ua, 'Macintosh' ) !== false ) return 'macOS';
        if ( stripos( $ua, 'Linux' ) !== false ) return 'Linux';
        return 'Bilinmeyen işletim sistemi';
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/login-history/me', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'rest_current_user_history' ),
            'permission_callback' => static function () {
                return is_user_logged_in() && SG_Permissions::current_user_can( 'app.access' );
            },
        ) );
    }

    public static function rest_current_user_history(): WP_REST_Response {
        $history = get_user_meta( get_current_user_id(), self::META_KEY, true );
        return new WP_REST_Response( array( 'items' => is_array( $history ) ? array_values( $history ) : array() ), 200 );
    }

    public static function render_admin_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'surmeli-grup' ) );
        }
        $users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID', 'display_name', 'user_login' ) ) );
        $rows = array();
        foreach ( $users as $user ) {
            $history = get_user_meta( $user->ID, self::META_KEY, true );
            if ( ! is_array( $history ) ) continue;
            foreach ( $history as $entry ) {
                $rows[] = array( 'user' => $user->display_name ?: $user->user_login, 'login' => $user->user_login, 'entry' => $entry );
            }
        }
        usort( $rows, static function ( $a, $b ) {
            return (int) ( $b['entry']['timestamp'] ?? 0 ) <=> (int) ( $a['entry']['timestamp'] ?? 0 );
        } );
        $rows = array_slice( $rows, 0, 500 );
        echo '<div class="wrap"><h1>' . esc_html__( 'Kullanıcı Giriş Kayıtları', 'surmeli-grup' ) . '</h1><p>' . esc_html__( 'Başarılı WordPress ve portal girişleri. Her kullanıcı için son 20 giriş saklanır; listede en yeni 500 kayıt gösterilir.', 'surmeli-grup' ) . '</p>';
        echo '<table class="widefat striped"><thead><tr><th>Kullanıcı</th><th>Giriş tarihi / saati</th><th>Cihaz</th><th>Tarayıcı / işletim sistemi</th><th>IP adresi</th></tr></thead><tbody>';
        if ( empty( $rows ) ) {
            echo '<tr><td colspan="5">' . esc_html__( 'Henüz giriş kaydı bulunmuyor.', 'surmeli-grup' ) . '</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $e = $row['entry'];
                echo '<tr><td>' . esc_html( $row['user'] . ' (' . $row['login'] . ')' ) . '</td><td>' . esc_html( (string) ( $e['time'] ?? '—' ) ) . '</td><td>' . esc_html( (string) ( $e['device'] ?? '—' ) ) . '</td><td>' . esc_html( (string) ( $e['browser'] ?? '—' ) . ' / ' . (string) ( $e['os'] ?? '—' ) ) . '</td><td>' . esc_html( (string) ( $e['ip'] ?? '—' ) ) . '</td></tr>';
            }
        }
        echo '</tbody></table></div>';
    }
}
