<?php
/** Conservative PWA support for the standalone Sürmeli Grup portal. */
defined( 'ABSPATH' ) || exit;

final class SG_PWA {
    public static function init(): void {
        add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
        add_action( 'template_redirect', array( __CLASS__, 'dispatch' ), -110 );
    }

    public static function query_vars( array $vars ): array {
        $vars[] = 'sg_pwa_manifest';
        $vars[] = 'sg_pwa_sw';
        return $vars;
    }

    public static function dispatch(): void {
        if ( '1' === (string) get_query_var( 'sg_pwa_manifest' ) ) {
            self::manifest();
            exit;
        }
        if ( '1' === (string) get_query_var( 'sg_pwa_sw' ) ) {
            self::service_worker();
            exit;
        }
    }

    private static function manifest(): void {
        nocache_headers();
        header( 'Content-Type: application/manifest+json; charset=utf-8' );
        $icon = SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg';
        echo wp_json_encode( array(
            'id' => SG_APP_URL,
            'name' => 'Sürmeli Grup',
            'short_name' => 'Sürmeli Grup',
            'description' => 'Sürmeli Grup işletme yönetim portalı',
            'start_url' => SG_APP_URL,
            'scope' => SG_APP_URL,
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#F3F6FA',
            'theme_color' => '#0878C9',
            'icons' => array(
                array( 'src' => $icon, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any' ),
            ),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }

    private static function service_worker(): void {
        nocache_headers();
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'Service-Worker-Allowed: /' );
        $assets = array(
            SG_PLUGIN_URL . 'assets/css/app.css',
            SG_PLUGIN_URL . 'assets/js/app.js',
            SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg',
        );
        echo 'const CACHE = ' . wp_json_encode( 'sg-portal-pwa-' . SG_VERSION ) . ';' . "\n";
        echo 'const ASSETS = ' . wp_json_encode( $assets ) . ';' . "\n";
        echo "self.addEventListener('install', event => { event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(ASSETS)).then(() => self.skipWaiting())); });\n";
        echo "self.addEventListener('activate', event => { event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('sg-portal-pwa-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim())); });\n";
        echo "self.addEventListener('fetch', event => { const req = event.request; const url = new URL(req.url); if (req.method !== 'GET' || url.origin !== self.location.origin) return; if (url.pathname.startsWith('/wp-json/') || url.pathname.startsWith('/wp-admin/')) return; if (req.mode === 'navigate') { event.respondWith(fetch(req).catch(() => new Response('<!doctype html><html lang=\"tr\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>Bağlantı yok</title><body style=\"font:16px system-ui;padding:2rem;color:#1f2937\"><h1>İnternet bağlantısı yok</h1><p>Sürmeli Grup portalına erişmek için bağlantınızı kontrol edip tekrar deneyin.</p></body></html>', {status:503, headers:{'Content-Type':'text/html; charset=utf-8'}})); return; } if (ASSETS.includes(req.url)) event.respondWith(caches.match(req).then(hit => hit || fetch(req))); });\n";
    }
}
