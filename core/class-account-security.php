<?php
/** Authenticated self-service password changes for portal users. */
defined( 'ABSPATH' ) || exit;

final class SG_Account_Security {
    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/me/password', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( __CLASS__, 'change_password' ),
            'permission_callback' => static function () {
                return is_user_logged_in() && SG_Permissions::current_user_can( 'app.access' );
            },
            'args' => array(
                'currentPassword' => array( 'type' => 'string', 'required' => true ),
                'newPassword' => array( 'type' => 'string', 'required' => true ),
            ),
        ) );
    }

    public static function change_password( WP_REST_Request $request ): WP_REST_Response {
        $user = wp_get_current_user();
        $current = (string) $request->get_param( 'currentPassword' );
        $new = (string) $request->get_param( 'newPassword' );

        if ( '' === $current || ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
            return new WP_REST_Response( array( 'message' => 'Mevcut şifreniz hatalı.' ), 400 );
        }

        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $new, 'UTF-8' ) : strlen( $new );
        if ( $length < 8 || ! preg_match( '/[A-Z]/', $new ) || ! preg_match( '/[a-z]/', $new ) || ! preg_match( '/[0-9]/', $new ) ) {
            return new WP_REST_Response( array( 'message' => 'Yeni şifre en az 8 karakter olmalı; büyük harf, küçük harf ve rakam içermelidir.' ), 400 );
        }
        if ( hash_equals( $current, $new ) ) {
            return new WP_REST_Response( array( 'message' => 'Yeni şifreniz mevcut şifrenizden farklı olmalıdır.' ), 400 );
        }

        $updated = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $new ) );
        if ( is_wp_error( $updated ) ) {
            return new WP_REST_Response( array( 'message' => 'Şifre güncellenemedi. Lütfen tekrar deneyin.' ), 500 );
        }

        // Keep this browser session active and invalidate other sessions for this account.
        $token = wp_get_session_token();
        if ( $token ) {
            WP_Session_Tokens::get_instance( $user->ID )->destroy_others( $token );
        }

        return new WP_REST_Response( array( 'message' => 'Şifreniz başarıyla değiştirildi.' ), 200 );
    }
}
