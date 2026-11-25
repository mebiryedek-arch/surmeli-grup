<?php
/** Income and expense register. */
defined( 'ABSPATH' ) || exit;
final class SG_Migration_006_Income_Expense {
    public function up(): bool|WP_Error {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'sg_income_expenses';
        $cc = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            record_type varchar(10) NOT NULL,
            category_id bigint(20) unsigned NOT NULL,
            cash_account_id bigint(20) unsigned NOT NULL,
            journal_entry_id bigint(20) unsigned NOT NULL,
            amount decimal(18,2) NOT NULL,
            record_date date NOT NULL,
            document_no varchar(80) DEFAULT NULL,
            description text DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY type_date (record_type,record_date),
            KEY category_id (category_id),
            KEY cash_account_id (cash_account_id),
            KEY journal_entry_id (journal_entry_id)
        ) ENGINE=InnoDB {$cc};";
        dbDelta( $sql );
        if ( ! SG_Migrator::table_exists( $table ) ) {
            return new WP_Error( 'sg_income_expense_schema', 'Gelir/gider tablosu oluşturulamadı: ' . $wpdb->last_error );
        }
        $categories = $wpdb->prefix . 'sg_categories';
        $now = current_time( 'mysql', true );
        foreach ( array(
            'income' => array( 'Hizmet Geliri', 'Ürün Satışı', 'Diğer Gelir' ),
            'expense' => array( 'Personel Gideri', 'Kira', 'Elektrik / Su / Doğalgaz', 'Malzeme Alımı', 'Ulaşım', 'Diğer Gider' ),
        ) as $type => $names ) {
            foreach ( $names as $name ) {
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$categories} WHERE category_type=%s AND category_name=%s AND parent_id IS NULL LIMIT 1", $type, $name ) );
                if ( ! $exists ) {
                    $wpdb->insert( $categories, array( 'category_type'=>$type, 'category_name'=>$name, 'parent_id'=>null, 'is_active'=>1, 'created_by'=>0, 'created_at'=>$now, 'updated_at'=>$now ) );
                }
            }
        }
        return true;
    }
}
