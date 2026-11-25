<?php
/** Initial business database schema. Relationships are enforced by services and indexed identifiers. */
defined( 'ABSPATH' ) || exit;

final class SG_Migration_001_Create_Core_Schema {
    public function get_tables(): array {
        global $wpdb;
        $p = $wpdb->prefix;
        return array(
            $p . 'sg_parties', $p . 'sg_accounts', $p . 'sg_ledger_accounts',
            $p . 'sg_journal_entries', $p . 'sg_journal_lines', $p . 'sg_cash_accounts',
            $p . 'sg_cash_transactions', $p . 'sg_categories', $p . 'sg_personnel',
            $p . 'sg_jobs', $p . 'sg_job_history', $p . 'sg_job_notes',
            $p . 'sg_financial_documents', $p . 'sg_audit_logs', $p . 'sg_idempotency_keys',
            $p . 'sg_permissions', $p . 'sg_role_permissions', $p . 'sg_settings',
        );
    }

    public function up(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $p = $wpdb->prefix;
        $cc = $wpdb->get_charset_collate();
        $tables = array();

        $tables[] = "CREATE TABLE {$p}sg_parties (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            party_code varchar(40) NOT NULL,
            party_type varchar(20) NOT NULL DEFAULT 'customer',
            party_kind varchar(20) NOT NULL DEFAULT 'corporate',
            company_name varchar(190) NOT NULL,
            contact_name varchar(190) DEFAULT NULL,
            phone varchar(40) DEFAULT NULL,
            email varchar(190) DEFAULT NULL,
            address text DEFAULT NULL,
            tax_office varchar(120) DEFAULT NULL,
            tax_number varchar(32) DEFAULT NULL,
            description text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY party_code (party_code),
            KEY party_type_status (party_type,status),
            KEY company_name (company_name),
            KEY phone (phone),
            KEY tax_number (tax_number),
            KEY created_at (created_at)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            party_id bigint(20) unsigned NOT NULL,
            account_code varchar(40) NOT NULL,
            account_type varchar(20) NOT NULL DEFAULT 'receivable',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY account_code (account_code),
            UNIQUE KEY party_account_type (party_id,account_type),
            KEY party_id (party_id),
            KEY status (status)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_ledger_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_code varchar(30) NOT NULL,
            account_name varchar(190) NOT NULL,
            account_class varchar(20) NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            is_system tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY account_code (account_code),
            KEY parent_id (parent_id),
            KEY account_class (account_class,is_active)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_journal_entries (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            entry_no varchar(50) NOT NULL,
            entry_date date NOT NULL,
            entry_type varchar(30) NOT NULL,
            source_module varchar(40) DEFAULT NULL,
            source_id bigint(20) unsigned DEFAULT NULL,
            source_key varchar(100) DEFAULT NULL,
            document_no varchar(80) DEFAULT NULL,
            description text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            reversal_of bigint(20) unsigned DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            posted_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            posted_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY entry_no (entry_no),
            UNIQUE KEY source_key (source_key),
            KEY entry_date_status (entry_date,status),
            KEY source_reference (source_module,source_id),
            KEY reversal_of (reversal_of),
            KEY created_by (created_by)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_journal_lines (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            entry_id bigint(20) unsigned NOT NULL,
            line_no smallint(5) unsigned NOT NULL,
            ledger_account_id bigint(20) unsigned NOT NULL,
            party_account_id bigint(20) unsigned DEFAULT NULL,
            cash_account_id bigint(20) unsigned DEFAULT NULL,
            description varchar(255) DEFAULT NULL,
            debit decimal(18,2) NOT NULL DEFAULT 0.00,
            credit decimal(18,2) NOT NULL DEFAULT 0.00,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY entry_line (entry_id,line_no),
            KEY ledger_account_id (ledger_account_id),
            KEY party_account_id (party_account_id),
            KEY cash_account_id (cash_account_id)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_cash_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_code varchar(40) NOT NULL,
            account_name varchar(190) NOT NULL,
            account_kind varchar(20) NOT NULL DEFAULT 'cash',
            bank_name varchar(120) DEFAULT NULL,
            branch_name varchar(120) DEFAULT NULL,
            iban varchar(34) DEFAULT NULL,
            currency char(3) NOT NULL DEFAULT 'TRY',
            ledger_account_id bigint(20) unsigned NOT NULL,
            opening_date date DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY account_code (account_code),
            KEY ledger_account_id (ledger_account_id),
            KEY account_kind_status (account_kind,status),
            KEY iban (iban)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_cash_transactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            journal_entry_id bigint(20) unsigned NOT NULL,
            cash_account_id bigint(20) unsigned NOT NULL,
            transaction_date date NOT NULL,
            direction varchar(10) NOT NULL,
            amount decimal(18,2) NOT NULL,
            document_no varchar(80) DEFAULT NULL,
            transfer_group varchar(64) DEFAULT NULL,
            description text DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY journal_entry_id (journal_entry_id),
            KEY cash_date (cash_account_id,transaction_date),
            KEY transfer_group (transfer_group),
            KEY created_by (created_by)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_categories (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_type varchar(20) NOT NULL,
            category_name varchar(120) NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY type_name_parent (category_type,category_name,parent_id),
            KEY parent_id (parent_id),
            KEY category_type (category_type,is_active)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_personnel (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            full_name varchar(190) NOT NULL,
            phone varchar(40) DEFAULT NULL,
            email varchar(190) DEFAULT NULL,
            job_title varchar(120) DEFAULT NULL,
            employment_date date DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            description text DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY full_name (full_name),
            KEY status (status)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_jobs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_no varchar(50) NOT NULL,
            title varchar(190) NOT NULL,
            description longtext DEFAULT NULL,
            party_id bigint(20) unsigned DEFAULT NULL,
            assigned_personnel_id bigint(20) unsigned DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            start_date datetime DEFAULT NULL,
            due_date datetime DEFAULT NULL,
            priority varchar(20) NOT NULL DEFAULT 'normal',
            status varchar(30) NOT NULL DEFAULT 'new',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_no (job_no),
            KEY assigned_status (assigned_personnel_id,status),
            KEY party_id (party_id),
            KEY due_date (due_date),
            KEY created_by (created_by)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_job_history (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            action varchar(40) NOT NULL,
            old_value longtext DEFAULT NULL,
            new_value longtext DEFAULT NULL,
            note text DEFAULT NULL,
            performed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_created (job_id,created_at),
            KEY performed_by (performed_by)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_job_notes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            note text NOT NULL,
            visibility varchar(20) NOT NULL DEFAULT 'internal',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_created (job_id,created_at),
            KEY created_by (created_by)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_financial_documents (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            document_type varchar(30) NOT NULL,
            document_no varchar(80) DEFAULT NULL,
            journal_entry_id bigint(20) unsigned DEFAULT NULL,
            party_id bigint(20) unsigned DEFAULT NULL,
            document_date date DEFAULT NULL,
            file_attachment_id bigint(20) unsigned DEFAULT NULL,
            description text DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY document_no (document_no),
            KEY journal_entry_id (journal_entry_id),
            KEY party_id (party_id),
            KEY document_date (document_date)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_audit_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            event_key varchar(100) NOT NULL,
            object_type varchar(60) DEFAULT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            request_id varchar(64) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            details longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_created (user_id,created_at),
            KEY object_reference (object_type,object_id),
            KEY event_created (event_key,created_at),
            KEY request_id (request_id)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_idempotency_keys (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            idempotency_key varchar(100) NOT NULL,
            operation varchar(80) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            payload_hash char(64) NOT NULL,
            result_reference varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'processing',
            created_at datetime NOT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY idempotency_key (idempotency_key),
            KEY operation_created (operation,created_at),
            KEY user_id (user_id)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_permissions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            permission_key varchar(100) NOT NULL,
            description varchar(190) DEFAULT NULL,
            module_key varchar(60) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY permission_key (permission_key),
            KEY module_key (module_key)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_role_permissions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            role_key varchar(80) NOT NULL,
            permission_id bigint(20) unsigned NOT NULL,
            allowed tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY role_permission (role_key,permission_id),
            KEY permission_id (permission_id)
        ) ENGINE=InnoDB {$cc};";

        $tables[] = "CREATE TABLE {$p}sg_settings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            setting_key varchar(120) NOT NULL,
            setting_value longtext DEFAULT NULL,
            is_sensitive tinyint(1) NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key (setting_key)
        ) ENGINE=InnoDB {$cc};";

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }
}
