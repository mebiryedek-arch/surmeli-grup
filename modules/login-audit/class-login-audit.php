<?php
/** Successful WordPress login audit trail. */
defined( 'ABSPATH' ) || exit;

final class SG_Login_Audit {
    private const META_KEY = '_sg_login_history';
    private const MAX_HISTORY = 50;

    public static function init(): void {
        add_action( 'wp_login', array( __CLASS__, 'record_login' ), 10, 2 );
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
    }

    public static function record_login( string $user_login, WP_User $user ): void {
        $agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) { $ip = ''; }

        $device = 'Bilgisayar';
        if ( preg_match( '/ipad|tablet/i', $agent ) ) { $device = 'Tablet'; }
        elseif ( preg_match( '/mobile|iphone|ipod|android/i', $agent ) ) { $device = 'Telefon'; }

        $browser = 'Bilinmeyen tarayıcı';
        $browser_patterns = array(
            'Edge' => '/Edg\//i', 'Opera' => '/OPR\//i', 'Chrome' => '/Chrome\//i',
            'Firefox' => '/Firefox\//i', 'Safari' => '/Safari\//i',
        );
        foreach ( $browser_patterns as $name => $pattern ) {
            if ( preg_match( $pattern, $agent ) ) { $browser = $name; break; }
        }
        $os = 'Bilinmeyen sistem';
        foreach ( array( 'Windows' => '/Windows/i', 'Android' => '/Android/i', 'iOS' => '/iPhone|iPad|iPod/i', 'macOS' => '/Mac OS/i', 'Linux' => '/Linux/i' ) as $name => $pattern ) {
            if ( preg_match( $pattern, $agent ) ) { $os = $name; break; }
        }

        $entry = array(
            'time' => current_time( 'mysql' ),
            'ip' => $ip,
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
        );
        $history = get_user_meta( $user->ID, self::META_KEY, true );
        $history = is_array( $history ) ? $history : array();
        array_unshift( $history, $entry );
        update_user_meta( $user->ID, self::META_KEY, array_slice( $history, 0, self::MAX_HISTORY ) );
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/my-login-info', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'rest_my_login_info' ),
            'permission_callback' => static function () { return is_user_logged_in() && SG_Permissions::current_user_can( 'app.access' ); },
        ) );
    }

    public static function rest_my_login_info(): WP_REST_Response {
        $history = get_user_meta( get_current_user_id(), self::META_KEY, true );
        $latest = is_array( $history ) && ! empty( $history ) ? $history[0] : array();
        return new WP_REST_Response( $latest, 200 );
    }

    public static function register_admin_page(): void {
        add_users_page( 'Giriş Kayıtları', 'Giriş Kayıtları', 'manage_options', 'sg-login-audit', array( __CLASS__, 'render_admin_page' ) );
    }

    public static function render_admin_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'surmeli-grup' ) ); }
        $users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID', 'display_name', 'user_login', 'user_email' ) ) );
        echo '<div class="wrap"><h1>Başarılı Kullanıcı Giriş Kayıtları</h1><p>Son başarılı giriş bilgileri gösterilir. Her kullanıcı için son 50 giriş kaydı saklanır.</p><table class="widefat striped"><thead><tr><th>Kullanıcı</th><th>Giriş tarihi / saati</th><th>Cihaz</th><th>İşletim sistemi</th><th>Tarayıcı</th><th>IP adresi</th></tr></thead><tbody>';
        foreach ( $users as $user ) {
            $history = get_user_meta( $user->ID, self::META_KEY, true );
            $entry = is_array( $history ) && ! empty( $history ) ? $history[0] : array();
            echo '<tr><td>' . esc_html( $user->display_name . ' (' . $user->user_login . ')' ) . '</td>';
            if ( empty( $entry ) ) {
                echo '<td colspan="5">Bu sürümden sonra henüz giriş kaydı yok.</td>';
            } else {
                echo '<td>' . esc_html( mysql2date( 'd.m.Y H:i:s', $entry['time'] ) ) . '</td><td>' . esc_html( $entry['device'] ) . '</td><td>' . esc_html( $entry['os'] ) . '</td><td>' . esc_html( $entry['browser'] ) . '</td><td>' . esc_html( $entry['ip'] ) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}
