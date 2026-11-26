<?php
/**
 * Plugin Name: Sürmeli Grup İşletme Yönetimi
 * Description: Sürmeli Grup işletme, cari hesap ve görev yönetimi uygulamasının çekirdeği.
 * Version: 0.12.6
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Mebir.NET (Tuncer Ünal)
 * Author URI: https://mebir.net
 * Text Domain: surmeli-grup
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'SG_VERSION', '0.12.6' );
define( 'SG_DB_VERSION', '1.7.0' );
// Fixed application address: intentionally not configurable from WordPress settings.
define( 'SG_APP_URL', 'https://surmeligrup.com.tr/musteritakip/' );
define( 'SG_PLUGIN_FILE', __FILE__ );
define( 'SG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SG_PLUGIN_DIR . 'core/class-loader.php';

register_activation_hook( __FILE__, array( 'SG_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SG_Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', static function () {
    load_plugin_textdomain( 'surmeli-grup', false, dirname( SG_PLUGIN_BASENAME ) . '/languages' );
    SG_Loader::init();
} );
