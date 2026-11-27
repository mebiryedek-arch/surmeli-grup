<?php
/** Standalone portal route, authentication screen and application renderer. */
defined( 'ABSPATH' ) || exit;

final class SG_App {
    private const QUERY_VAR = 'sg_portal';
    private const ROUTE_VERSION = '1';

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register_route' ), 1 );
        add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
        add_action( 'template_redirect', array( __CLASS__, 'maybe_render_portal' ), -100 );
        add_action( 'template_redirect', array( __CLASS__, 'maybe_render_app_not_found' ), -99 );
        add_action( 'admin_init', array( __CLASS__, 'remove_legacy_plugin_page' ), 40 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_route(): void {
        add_rewrite_rule( '^musteritakip/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
    }

    public static function register_query_var( array $vars ): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /** Remove only the exact page created by this plugin in 0.5.0; never touch user pages. */
    public static function remove_legacy_plugin_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $page_id = absint( get_option( 'sg_portal_page_id', 0 ) );
        if ( $page_id ) {
            $page = get_post( $page_id );
            if ( $page instanceof WP_Post && 'page' === $page->post_type && 'musteritakip' === $page->post_name && trim( (string) $page->post_content ) === '[sg_app]' ) {
                wp_delete_post( $page_id, true );
            }
            delete_option( 'sg_portal_page_id' );
        }
        if ( get_option( 'sg_portal_route_version' ) !== self::ROUTE_VERSION ) {
            update_option( 'sg_portal_route_version', self::ROUTE_VERSION, false );
            flush_rewrite_rules( false );
        }
    }

    public static function register_assets(): void {
        wp_register_style( 'sg-app', SG_PLUGIN_URL . 'assets/css/app.css', array(), SG_VERSION );
        wp_register_script( 'sg-app', SG_PLUGIN_URL . 'assets/js/app.js', array(), SG_VERSION, true );
    }

    public static function maybe_render_portal(): void {
        if ( '1' !== (string) get_query_var( self::QUERY_VAR ) ) {
            return;
        }

        $login_error = '';
        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && isset( $_POST['sg_login_submit'] ) ) {
            $login_error = self::process_login();
        }

        status_header( 200 );
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow', true );
        self::render_standalone( $login_error );
        exit;
    }

    private static function process_login(): string {
        $nonce = isset( $_POST['_sg_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_sg_login_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'sg_portal_login' ) ) {
            return __( 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.', 'surmeli-grup' );
        }

        $username = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '';
        $password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
        $remember = ! empty( $_POST['rememberme'] );
        if ( '' === $username || '' === $password ) {
            return __( 'Kullanıcı adı ve şifre alanları zorunludur.', 'surmeli-grup' );
        }

        $user = wp_signon( array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ), is_ssl() );

        if ( is_wp_error( $user ) ) {
            return __( 'Giriş bilgileri doğrulanamadı. Kullanıcı adınızı ve şifrenizi kontrol edin.', 'surmeli-grup' );
        }

        wp_safe_redirect( SG_APP_URL );
        exit;
    }

    private static function render_standalone( string $login_error = '' ): void {
        $css_url = SG_PLUGIN_URL . 'assets/css/app.css?ver=' . rawurlencode( SG_VERSION );
        $logo_url = SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg';
        $security_css_version = file_exists( SG_PLUGIN_DIR . 'assets/css/account-security.css' ) ? (string) filemtime( SG_PLUGIN_DIR . 'assets/css/account-security.css' ) : SG_VERSION;
        $security_js_version  = file_exists( SG_PLUGIN_DIR . 'assets/js/account-security.js' ) ? (string) filemtime( SG_PLUGIN_DIR . 'assets/js/account-security.js' ) : SG_VERSION;
        $security_css_url = SG_PLUGIN_URL . 'assets/css/account-security.css?ver=' . rawurlencode( $security_css_version );
        $reports_css_url = SG_PLUGIN_URL . 'assets/css/reports.css?ver=' . rawurlencode( SG_VERSION );
        echo '<!doctype html><html lang="tr"><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0878C9"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default"><meta name="apple-mobile-web-app-title" content="Sürmeli Grup"><link rel="manifest" href="' . esc_url( add_query_arg( 'sg_pwa_manifest', '1', home_url( '/' ) ) ) . '"><link rel="apple-touch-icon" href="' . esc_url( $logo_url ) . '"><title>' . esc_html__( 'Sürmeli Grup İşletme Yönetimi', 'surmeli-grup' ) . '</title><link rel="stylesheet" href="' . esc_url( $css_url ) . '"><link rel="stylesheet" href="' . esc_url( $security_css_url ) . '"><link rel="stylesheet" href="' . esc_url( $reports_css_url ) . '"></head><body class="sg-standalone-app">';
        if ( ! is_user_logged_in() ) {
            self::render_login( $login_error );
        } elseif ( ! SG_Permissions::current_user_can( 'app.access' ) ) {
            self::render_access_denied();
        } else {
            wp_enqueue_script( 'sg-app' );
            wp_localize_script( 'sg-app', 'SG_APP', array(
                'version' => SG_VERSION,
                'logoUrl' => esc_url_raw( SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg' ),
                'restRoot' => esc_url_raw( rest_url( 'sg/v1/' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'currency' => 'TRY',
                'userEndpoint' => esc_url_raw( rest_url( 'sg/v1/me' ) ),
                'permissionsEndpoint' => esc_url_raw( rest_url( 'sg/v1/permissions' ) ),
                'dashboardEndpoint' => esc_url_raw( rest_url( 'sg/v1/dashboard' ) ),
                'customersEndpoint' => esc_url_raw( rest_url( 'sg/v1/customers' ) ),
                'suppliersEndpoint' => esc_url_raw( rest_url( 'sg/v1/suppliers' ) ),
                'accountsEndpoint' => esc_url_raw( rest_url( 'sg/v1/accounts' ) ),
                'cashAccountsEndpoint' => esc_url_raw( rest_url( 'sg/v1/cash-accounts' ) ),
                'cashPartiesEndpoint' => esc_url_raw( rest_url( 'sg/v1/cash-parties' ) ),
                'cashTransfersEndpoint' => esc_url_raw( rest_url( 'sg/v1/cash-transfers' ) ),
                'incomeExpensesEndpoint' => esc_url_raw( rest_url( 'sg/v1/income-expenses' ) ),
                'incomeExpenseOptionsEndpoint' => esc_url_raw( rest_url( 'sg/v1/income-expense-options' ) ),
                'jobsEndpoint' => esc_url_raw( rest_url( 'sg/v1/jobs' ) ),
                'jobOptionsEndpoint' => esc_url_raw( rest_url( 'sg/v1/job-options' ) ),
                'personnelEndpoint' => esc_url_raw( rest_url( 'sg/v1/personnel' ) ),
                'reportsEndpoint' => esc_url_raw( rest_url( 'sg/v1/reports' ) ),
                'reportsDrilldownEndpoint' => esc_url_raw( rest_url( 'sg/v1/reports/drilldown' ) ),
                'reportsExportEndpoint' => esc_url_raw( rest_url( 'sg/v1/reports/export' ) ),
            ) );
            echo '<script>window.SG_APP=' . wp_json_encode( array(
                'version' => SG_VERSION,
                'logoUrl' => esc_url_raw( SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg' ),
                'restRoot' => esc_url_raw( rest_url( 'sg/v1/' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'currency' => 'TRY',
                'userEndpoint' => esc_url_raw( rest_url( 'sg/v1/me' ) ),
                'permissionsEndpoint' => esc_url_raw( rest_url( 'sg/v1/permissions' ) ),
                'dashboardEndpoint' => esc_url_raw( rest_url( 'sg/v1/dashboard' ) ),
                'customersEndpoint' => esc_url_raw( rest_url( 'sg/v1/customers' ) ),
                'suppliersEndpoint' => esc_url_raw( rest_url( 'sg/v1/suppliers' ) ),
                'accountsEndpoint' => esc_url_raw( rest_url( 'sg/v1/accounts' ) ),
                'cashAccountsEndpoint' => esc_url_raw( rest_url( 'sg/v1/cash-accounts' ) ),
                'cashPartiesEndpoint' => esc_url_raw( rest_url( 'sg/v1/cash-parties' ) ),
                'cashTransfersEndpoint' => esc_url_raw( rest_url( 'sg/v1/cash-transfers' ) ),
                'incomeExpensesEndpoint' => esc_url_raw( rest_url( 'sg/v1/income-expenses' ) ),
                'incomeExpenseOptionsEndpoint' => esc_url_raw( rest_url( 'sg/v1/income-expense-options' ) ),
                'jobsEndpoint' => esc_url_raw( rest_url( 'sg/v1/jobs' ) ),
                'jobOptionsEndpoint' => esc_url_raw( rest_url( 'sg/v1/job-options' ) ),
                'personnelEndpoint' => esc_url_raw( rest_url( 'sg/v1/personnel' ) ),
                'reportsEndpoint' => esc_url_raw( rest_url( 'sg/v1/reports' ) ),
                'reportsDrilldownEndpoint' => esc_url_raw( rest_url( 'sg/v1/reports/drilldown' ) ),
                'reportsExportEndpoint' => esc_url_raw( rest_url( 'sg/v1/reports/export' ) ),
            ) ) . ';</script>';
            require SG_PLUGIN_DIR . 'app/templates/app-shell.php';
            echo '<script src="' . esc_url( SG_PLUGIN_URL . 'assets/js/app.js?ver=' . SG_VERSION ) . '" defer></script>';
            echo '<script src="' . esc_url( SG_PLUGIN_URL . 'assets/js/reports.js?ver=' . SG_VERSION ) . '" defer></script>';
            echo '<script src="' . esc_url( SG_PLUGIN_URL . 'assets/js/account-security.js?ver=' . rawurlencode( $security_js_version ) ) . '" defer></script>';
        }
        echo '<script>if ("serviceWorker" in navigator && location.protocol === "https:") { window.addEventListener("load", function () { navigator.serviceWorker.register(' . wp_json_encode( add_query_arg( 'sg_pwa_sw', '1', home_url( '/' ) ) ) . ', { scope: ' . wp_json_encode( SG_APP_URL ) . ' }).catch(function (error) { console.warn("Sürmeli Grup PWA kaydı başarısız:", error); }); }); }</script>';
        echo '</body></html>';
    }


    /**
     * Render a branded not-found screen for broken paths inside the standalone app.
     * Keep the handler scoped to the application URL so normal site 404 pages are untouched.
     */
    public static function maybe_render_app_not_found(): void {
        if ( ! is_404() ) {
            return;
        }

        $app_path = untrailingslashit( (string) wp_parse_url( SG_APP_URL, PHP_URL_PATH ) );
        $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
        if ( $request_path !== $app_path && 0 !== strpos( $request_path, $app_path . '/' ) ) {
            return;
        }

        status_header( 404 );
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow', true );

        $logo_url = SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg';
        $css_url  = SG_PLUGIN_URL . 'assets/css/app.css?ver=' . rawurlencode( SG_VERSION );

        echo '<!doctype html><html lang="tr"><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0878C9"><title>' . esc_html__( 'Sayfa bulunamadı | Sürmeli Grup', 'surmeli-grup' ) . '</title><link rel="stylesheet" href="' . esc_url( $css_url ) . '"></head><body class="sg-standalone-app"><main class="sg-app-not-found"><section class="sg-app-not-found-card" aria-labelledby="sg-app-not-found-title">';
        if ( file_exists( SG_PLUGIN_DIR . 'assets/images/surmeli-grup-logo.svg' ) ) {
            echo '<img class="sg-app-not-found-logo" src="' . esc_url( $logo_url ) . '" alt="Sürmeli Grup">';
        } else {
            echo '<div class="sg-login-brand-mark">SG</div><p class="sg-app-not-found-brand">SÜRMELİ GRUP</p>';
        }
        echo '<div class="sg-app-not-found-code" aria-hidden="true">404</div><p class="sg-login-eyebrow">İŞLETME YÖNETİM SİSTEMİ</p><h1 id="sg-app-not-found-title">Aradığınız sayfayı bulamadık</h1><p class="sg-app-not-found-description">Bu bağlantı taşınmış, değiştirilmiş veya artık kullanılmıyor olabilir. Uygulamaya dönerek işlemlerinize devam edebilirsiniz.</p><div class="sg-app-not-found-actions"><a class="sg-login-button-link" href="' . esc_url( SG_APP_URL ) . '">Uygulamaya Git <span aria-hidden="true">→</span></a><a class="sg-app-not-found-home" href="' . esc_url( home_url( '/' ) ) . '">Ana sayfaya dön</a></div><p class="sg-login-footer">Güvenli işletme yönetim platformu</p></section></main></body></html>';
        exit;
    }

    private static function render_login( string $error = '' ): void {
        $logo_path = SG_PLUGIN_DIR . 'assets/images/surmeli-grup-logo.svg';
        $logo_url = SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg';
        echo '<main class="sg-login-page"><section class="sg-login-card" aria-labelledby="sg-login-title">';
        if ( file_exists( $logo_path ) ) {
            echo '<img class="sg-login-logo" src="' . esc_url( $logo_url ) . '" alt="Sürmeli Grup">';
        } else {
            echo '<div class="sg-login-brand-mark">SG</div><div class="sg-login-brand-name">SÜRMELİ GRUP</div>';
        }
        echo '<p class="sg-login-eyebrow">İŞLETME YÖNETİM SİSTEMİ</p><h1 id="sg-login-title">Hoş geldiniz</h1><p class="sg-login-description">Devam etmek için hesabınıza giriş yapın.</p>';
        if ( '' !== $error ) {
            echo '<div class="sg-login-error" role="alert">' . esc_html( $error ) . '</div>';
        }
        echo '<form class="sg-login-form" method="post" action="' . esc_url( SG_APP_URL ) . '">';
        wp_nonce_field( 'sg_portal_login', '_sg_login_nonce', false );
        echo '<label for="sg-login-user">Kullanıcı adı</label><input id="sg-login-user" name="log" type="text" autocomplete="username" required autofocus><label for="sg-login-password">Şifre</label><input id="sg-login-password" name="pwd" type="password" autocomplete="current-password" required><label class="sg-remember"><input type="checkbox" name="rememberme" value="1"> <span>Beni hatırla</span></label><button type="submit" name="sg_login_submit" value="1">Giriş Yap <span aria-hidden="true">→</span></button></form>';
        echo '<div class="sg-login-app-install"><p class="sg-login-app-caption">MOBİL UYGULAMA</p><button type="button" class="sg-login-app-button" id="sg-pwa-login">Uygulamayı Aç / Ana Ekrana Ekle</button><p class="sg-pwa-login-help" id="sg-pwa-login-help" role="status" aria-live="polite">Dilerseniz uygulamayı telefonunuzun ana ekranına ekleyebilirsiniz.</p></div>';
        echo '<p class="sg-login-footer">Güvenli işletme yönetim platformu</p></section><div class="sg-login-copyright">© ' . esc_html( gmdate( 'Y' ) ) . ' Sürmeli Grup</div></main>';
        echo '<script>(function(){var button=document.getElementById("sg-pwa-login"),help=document.getElementById("sg-pwa-login-help"),installPrompt=null;if(!button||!help)return;window.addEventListener("beforeinstallprompt",function(event){event.preventDefault();installPrompt=event;button.textContent="Uygulamayı Yükle";});window.addEventListener("appinstalled",function(){installPrompt=null;help.textContent="Sürmeli Grup ana ekranınıza eklendi. Uygulamayı buradan açabilirsiniz.";button.textContent="Uygulama Yüklendi";});button.addEventListener("click",async function(){var standalone=(window.matchMedia&&window.matchMedia("(display-mode: standalone)").matches)||window.navigator.standalone===true;if(standalone){window.location.href=' . wp_json_encode( SG_APP_URL ) . ';return;}if(installPrompt){var promptEvent=installPrompt;installPrompt=null;try{await promptEvent.prompt();var choice=await promptEvent.userChoice;if(choice&&choice.outcome==="accepted"){help.textContent="Kurulum başlatıldı. Sürmeli Grup ana ekranınıza eklenecek.";}else{help.textContent="Kurulum iptal edildi. İstediğiniz zaman tekrar deneyebilirsiniz.";}}catch(error){help.textContent="Otomatik kurulum açılamadı. Chrome menüsünden (⋮) Uygulamayı yükle seçeneğini kullanın.";}return;}if(/Android/i.test(navigator.userAgent)&&/Chrome\/\d+/i.test(navigator.userAgent)){help.textContent="Chrome menüsünü (⋮) açın ve \\u201cUygulamayı yükle\\u201d veya \\u201cAna ekrana ekle\\u201d seçeneğine dokunun. Seçenek görünmüyorsa sayfayı yenileyip tekrar deneyin.";}else{help.textContent="Tarayıcı menüsünü açın ve \\u201cAna ekrana ekle\\u201d seçeneğini kullanın.";}});})();</script>';
    }

    private static function render_access_denied(): void {
        echo '<main class="sg-login-page"><section class="sg-login-card"><div class="sg-login-brand-mark">SG</div><h1>Erişim izniniz bulunmuyor</h1><p class="sg-login-description">Bu kullanıcı hesabına uygulama erişimi tanımlanmamış. Sistem yöneticinizle görüşün.</p><a class="sg-login-button-link" href="' . esc_url( wp_logout_url( SG_APP_URL ) ) . '">Oturumu kapat</a></section></main>';
    }
}
