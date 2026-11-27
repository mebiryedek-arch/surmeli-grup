<?php
/** Automatically cached Turkish public and religious holiday calendar. */
defined( 'ABSPATH' ) || exit;

final class SG_Holidays {
    private const CACHE_PREFIX = 'sg_holidays_';
    private const CACHE_TTL = DAY_IN_SECONDS;

    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/holidays/(?P<year>\\d{4})', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'listing' ),
            'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'jobs.view' ),
        ) );
    }

    public static function listing( WP_REST_Request $request ): WP_REST_Response {
        $year = absint( $request['year'] );
        if ( $year < 2016 || $year > 2036 ) {
            return new WP_REST_Response( array( 'items' => array(), 'source' => 'onlinetakvim.com', 'unsupported' => true ), 200 );
        }

        $key = self::CACHE_PREFIX . $year;
        $cached = get_transient( $key );
        if ( is_array( $cached ) ) {
            return new WP_REST_Response( array( 'items' => $cached, 'source' => 'onlinetakvim.com', 'cached' => true ), 200 );
        }

        $url = 'https://onlinetakvim.com/api/v1/tatiller/' . $year;
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 12,
            'redirection' => 2,
            'headers' => array( 'Accept' => 'application/json' ),
            'user-agent' => 'SurmeliGrup/' . SG_VERSION . ' (Turkish holiday calendar)',
        ) );

        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $payload = json_decode( wp_remote_retrieve_body( $response ), true );
            $source_items = is_array( $payload ) && isset( $payload['tatiller'] ) && is_array( $payload['tatiller'] ) ? $payload['tatiller'] : array();
            $items = array();
            foreach ( $source_items as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }
                $date = isset( $item['tarih'] ) ? (string) $item['tarih'] : '';
                $name = isset( $item['ad'] ) ? sanitize_text_field( (string) $item['ad'] ) : '';
                $type = isset( $item['tur'] ) ? sanitize_key( (string) $item['tur'] ) : '';
                if ( ! preg_match( '/^' . $year . '-\\d{2}-\\d{2}$/', $date ) || '' === $name ) {
                    continue;
                }
                $kind = in_array( $type, array( 'dini', 'religious', 'bayram', 'arife' ), true ) || false !== stripos( $name, 'bayram' ) || false !== stripos( $name, 'arife' ) ? 'religious' : 'official';
                $items[] = array(
                    'date' => $date,
                    'name' => $name,
                    'type' => $kind,
                    'halfDay' => ! empty( $item['tam_gun'] ) ? false : ( isset( $item['tam_gun'] ) || false !== stripos( $name, 'arife' ) ),
                    'tentative' => ! empty( $item['tahmini'] ),
                );
            }
            if ( ! empty( $items ) ) {
                usort( $items, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
                update_option( $key . '_last_good', $items, false );
                set_transient( $key, $items, self::CACHE_TTL );
                return new WP_REST_Response( array( 'items' => $items, 'source' => 'onlinetakvim.com', 'cached' => false ), 200 );
            }
        }

        // If the provider is temporarily unavailable, keep serving the last successful response.
        $fallback = get_option( $key . '_last_good', array() );
        return new WP_REST_Response( array( 'items' => is_array( $fallback ) ? $fallback : array(), 'source' => 'onlinetakvim.com', 'stale' => true ), 200 );
    }
}
