<?php
/** Minimal system-only WordPress administration. */
defined( 'ABSPATH' ) || exit;

final class SG_Admin {
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) );
    }

    public static function register_menu(): void {
        add_menu_page(
            __( 'Sürmeli Grup', 'surmeli-grup' ),
            __( 'Sürmeli Grup', 'surmeli-grup' ),
            'manage_options',
            'sg-system',
            array( __CLASS__, 'render_settings' ),
            'dashicons-chart-area',
            58
        );
    }

    public static function migration_notice(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $error = get_transient( 'sg_migration_error' );
        if ( $error ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'Sürmeli Grup veritabanı güncelleme hatası: %s', 'surmeli-grup' ), $error ) ) . '</p></div>';
        }
    }

    public static function render_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'surmeli-grup' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Sürmeli Grup – Sistem Ayarları', 'surmeli-grup' ); ?></h1>
            <p><?php echo esc_html__( 'Günlük müşteri, cari, kasa ve görev işlemleri WordPress panelinden değil, özel uygulama arayüzünden yürütülecektir.', 'surmeli-grup' ); ?></p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Uygulama adresi', 'surmeli-grup' ); ?></th>
                    <td>
                        <a href="<?php echo esc_url( SG_APP_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( SG_APP_URL ); ?></a>
                        <p class="description"><?php echo esc_html__( 'Bu adres eklentinin kendi URL rotasıdır. WordPress Sayfalar bölümünde portal sayfası veya kısa kod oluşturulmaz.', 'surmeli-grup' ); ?></p>
                    </td>
                </tr>
            </table>
            <hr>
            <p><strong><?php echo esc_html__( 'Eklenti sürümü:', 'surmeli-grup' ); ?></strong> <?php echo esc_html( SG_VERSION ); ?></p>
            <hr>
            <h2><?php echo esc_html__( 'Kullanıcı Rolleri ve Yetkilendirme', 'surmeli-grup' ); ?></h2>
            <p><strong><?php echo esc_html__( 'Sürmeli Grup Yöneticisi', 'surmeli-grup' ); ?></strong> — <?php echo esc_html__( 'Uygulama modüllerinin tamamına erişebilir.', 'surmeli-grup' ); ?></p>
            <p><strong><?php echo esc_html__( 'Sürmeli Grup Personeli', 'surmeli-grup' ); ?></strong> — <?php echo esc_html__( 'Yalnızca kendisine atanmış işleri görüntüleyebilir; durum güncelleyebilir ve not ekleyebilir.', 'surmeli-grup' ); ?></p>
            <p><?php echo esc_html__( 'Kullanıcı rolleri WordPress > Kullanıcılar bölümünden atanır. Ayrıntılı izinler sunucu tarafında kontrol edilir.', 'surmeli-grup' ); ?></p>
            <hr>
            <h2><?php echo esc_html__( 'Veritabanı Durumu', 'surmeli-grup' ); ?></h2>
            <p><strong><?php echo esc_html__( 'Şema sürümü:', 'surmeli-grup' ); ?></strong> <?php echo esc_html( (string) get_option( 'sg_db_version', '0.0.0' ) ); ?></p>
            <p><strong><?php echo esc_html__( 'Beklenen sürüm:', 'surmeli-grup' ); ?></strong> <?php echo esc_html( SG_DB_VERSION ); ?></p>
            <p><strong><?php echo esc_html__( 'Veritabanı motoru:', 'surmeli-grup' ); ?></strong> InnoDB</p>
            <p><?php echo esc_html__( 'Tablolar özel sg_ önekiyle oluşturulur. İlişkiler servis katmanında doğrulanır ve ilgili alanlar indekslenir. Finansal kayıtlar bu aşamada henüz kullanıcı arayüzünden oluşturulamaz.', 'surmeli-grup' ); ?></p>
        </div>
        <?php
    }
}
