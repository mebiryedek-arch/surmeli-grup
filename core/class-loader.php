<?php
/** Core bootstrap and class loading. */
defined( 'ABSPATH' ) || exit;

require_once SG_PLUGIN_DIR . 'core/class-migrator.php';
require_once SG_PLUGIN_DIR . 'core/class-installer.php';
require_once SG_PLUGIN_DIR . 'core/class-deactivator.php';
require_once SG_PLUGIN_DIR . 'core/class-permissions.php';
require_once SG_PLUGIN_DIR . 'core/class-account-security.php';
require_once SG_PLUGIN_DIR . 'core/class-login-audit.php';
require_once SG_PLUGIN_DIR . 'core/class-holidays.php';
require_once SG_PLUGIN_DIR . 'admin/class-admin.php';
require_once SG_PLUGIN_DIR . 'app/class-app.php';
require_once SG_PLUGIN_DIR . 'app/class-pwa.php';
require_once SG_PLUGIN_DIR . 'modules/dashboard/class-dashboard.php';
require_once SG_PLUGIN_DIR . 'modules/customers/class-customers.php';
require_once SG_PLUGIN_DIR . 'modules/customers/class-customer-types.php';
require_once SG_PLUGIN_DIR . 'modules/suppliers/class-suppliers.php';
require_once SG_PLUGIN_DIR . 'modules/accounts/class-accounts.php';
require_once SG_PLUGIN_DIR . 'modules/cash-bank/class-cash-bank.php';
require_once SG_PLUGIN_DIR . 'modules/income-expense/class-income-expense.php';
require_once SG_PLUGIN_DIR . 'modules/personnel/class-personnel.php';
require_once SG_PLUGIN_DIR . 'modules/jobs/class-jobs.php';
require_once SG_PLUGIN_DIR . 'modules/reports/class-reports.php';

final class SG_Loader {
    public static function init(): void {
        SG_Permissions::init();
        SG_Account_Security::init();
        SG_Login_Audit::init();
        SG_Holidays::init();
        SG_Admin::init();
        SG_App::init();
        SG_PWA::init();
        SG_Dashboard::init();
        SG_Customers::init();
        SG_Customer_Types::init();
        SG_Suppliers::init();
        SG_Accounts::init();
        SG_Cash_Bank::init();
        SG_Income_Expense::init();
        SG_Personnel::init();
        SG_Jobs::init();
        SG_Reports::init();
        add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_database' ), 5 );
    }

    public static function maybe_upgrade_database(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( version_compare( (string) get_option( 'sg_db_version', '0.0.0' ), SG_DB_VERSION, '<' ) ) {
            $result = SG_Migrator::migrate();
            if ( is_wp_error( $result ) ) {
                set_transient( 'sg_migration_error', $result->get_error_message(), 5 * MINUTE_IN_SECONDS );
            }
        }
    }
}
