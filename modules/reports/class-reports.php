<?php
/** Read-only reporting endpoints built on existing Sürmeli Grup records. */
defined( 'ABSPATH' ) || exit;

final class SG_Reports {
    private static function t( string $name ): string {
        global $wpdb;
        return $wpdb->prefix . 'sg_' . $name;
    }

    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
    }

    public static function routes(): void {
        $permission = static function (): bool {
            return is_user_logged_in() && SG_Permissions::current_user_can( 'reports.view' );
        };
        register_rest_route( 'sg/v1', '/reports', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'summary' ),
            'permission_callback' => $permission,
        ) );
        register_rest_route( 'sg/v1', '/reports/drilldown', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'drilldown' ),
            'permission_callback' => $permission,
        ) );
        register_rest_route( 'sg/v1', '/reports/export', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'export' ),
            'permission_callback' => $permission,
        ) );
    }

    private static function dates( WP_REST_Request $request ): array|WP_Error {
        $from = sanitize_text_field( (string) $request->get_param( 'from' ) );
        $to = sanitize_text_field( (string) $request->get_param( 'to' ) );
        foreach ( array( $from, $to ) as $date ) {
            $parsed = DateTime::createFromFormat( '!Y-m-d', $date );
            if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
                return new WP_Error( 'sg_report_date', 'Rapor tarih aralığı geçersiz.', array( 'status' => 400 ) );
            }
        }
        if ( $from > $to ) {
            return new WP_Error( 'sg_report_order', 'Başlangıç tarihi bitiş tarihinden sonra olamaz.', array( 'status' => 400 ) );
        }
        $span = (int) ( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS );
        if ( $span > 1826 ) {
            return new WP_Error( 'sg_report_range', 'Tek seferde en fazla beş yıllık rapor alınabilir.', array( 'status' => 400 ) );
        }
        return array( 'from' => $from, 'to' => $to );
    }

    private static function money( $value ): string {
        return number_format( (float) $value, 2, '.', '' );
    }

    private static function scalar( string $sql, array $args = array() ): float {
        global $wpdb;
        $value = $args ? $wpdb->get_var( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_var( $sql );
        return (float) $value;
    }

    public static function summary( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $dates = self::dates( $request );
        if ( is_wp_error( $dates ) ) { return $dates; }
        $from = $dates['from'];
        $to = $dates['to'];
        $start = $from . ' 00:00:00';
        $end = $to . ' 23:59:59';
        $ie = self::t( 'income_expenses' );
        $je = self::t( 'journal_entries' );
        $cat = self::t( 'categories' );
        $cash = self::t( 'cash_accounts' );
        $lines = self::t( 'journal_lines' );
        $accounts = self::t( 'accounts' );
        $parties = self::t( 'parties' );
        $tx = self::t( 'cash_transactions' );
        $jobs = self::t( 'jobs' );
        $personnel = self::t( 'personnel' );

        $totals = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(SUM(CASE WHEN r.record_type='income' THEN r.amount ELSE 0 END),0) income,
                    COALESCE(SUM(CASE WHEN r.record_type='expense' THEN r.amount ELSE 0 END),0) expense,
                    COUNT(*) record_count
             FROM {$ie} r INNER JOIN {$je} j ON j.id=r.journal_entry_id AND j.status='posted'
             WHERE r.record_date BETWEEN %s AND %s",
            $from, $to
        ), ARRAY_A );
        $income = (float) ( $totals['income'] ?? 0 );
        $expense = (float) ( $totals['expense'] ?? 0 );
        $profit = $income - $expense;

        $categories = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.record_type,c.id category_id,c.category_name,SUM(r.amount) amount,COUNT(*) record_count
             FROM {$ie} r INNER JOIN {$je} j ON j.id=r.journal_entry_id AND j.status='posted'
             INNER JOIN {$cat} c ON c.id=r.category_id
             WHERE r.record_date BETWEEN %s AND %s
             GROUP BY r.record_type,c.id,c.category_name ORDER BY amount DESC",
            $from, $to
        ), ARRAY_A );

        $period_days = (int) ( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS ) + 1;
        if ( $period_days <= 45 ) {
            $trend_expression = 'r.record_date';
            $trend_granularity = 'daily';
        } elseif ( $period_days <= 180 ) {
            $trend_expression = 'YEARWEEK(r.record_date,3)';
            $trend_granularity = 'weekly';
        } else {
            $trend_expression = "DATE_FORMAT(r.record_date,'%%Y-%%m')";
            $trend_granularity = 'monthly';
        }
        $trend = $wpdb->get_results( $wpdb->prepare(
            "SELECT {$trend_expression} period,
                    SUM(CASE WHEN r.record_type='income' THEN r.amount ELSE 0 END) income,
                    SUM(CASE WHEN r.record_type='expense' THEN r.amount ELSE 0 END) expense
             FROM {$ie} r INNER JOIN {$je} j ON j.id=r.journal_entry_id AND j.status='posted'
             WHERE r.record_date BETWEEN %s AND %s GROUP BY {$trend_expression}
             ORDER BY period",
            $from, $to
        ), ARRAY_A );

        $collected = self::scalar(
            "SELECT COALESCE(SUM(t.amount),0) FROM {$tx} t INNER JOIN {$je} j ON j.id=t.journal_entry_id
             WHERE j.status='posted' AND j.entry_type='collection' AND t.direction='in' AND t.transaction_date BETWEEN %s AND %s",
            array( $from, $to )
        );
        $paid = self::scalar(
            "SELECT COALESCE(SUM(t.amount),0) FROM {$tx} t INNER JOIN {$je} j ON j.id=t.journal_entry_id
             WHERE j.status='posted' AND j.entry_type='payment' AND t.direction='out' AND t.transaction_date BETWEEN %s AND %s",
            array( $from, $to )
        );

        $party_balances = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.party_type,
                    SUM(CASE WHEN p.party_type='customer' THEN GREATEST(b.net,0) ELSE 0 END) receivable,
                    SUM(CASE WHEN p.party_type='supplier' THEN GREATEST(0,-b.net) ELSE 0 END) payable
             FROM {$parties} p
             INNER JOIN {$accounts} a ON a.party_id=p.id AND a.status='active'
             LEFT JOIN (
                SELECT jl.party_account_id,SUM(jl.debit-jl.credit) net
                FROM {$lines} jl INNER JOIN {$je} j ON j.id=jl.entry_id AND j.status='posted'
                WHERE j.entry_date <= %s GROUP BY jl.party_account_id
             ) b ON b.party_account_id=a.id
             WHERE p.status='active' GROUP BY p.party_type",
            $to
        ), ARRAY_A );
        $receivable = 0.0; $payable = 0.0;
        foreach ( $party_balances ?: array() as $balance ) {
            $receivable += (float) $balance['receivable'];
            $payable += (float) $balance['payable'];
        }

        $cash_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.id,c.account_name,c.account_kind,
                    COALESCE(SUM(CASE WHEN j.status='posted' AND j.entry_date <= %s THEN l.debit-l.credit ELSE 0 END),0) balance
             FROM {$cash} c LEFT JOIN {$lines} l ON l.cash_account_id=c.id
             LEFT JOIN {$je} j ON j.id=l.entry_id
             WHERE c.status='active' GROUP BY c.id,c.account_name,c.account_kind ORDER BY c.account_kind,c.account_name",
            $to
        ), ARRAY_A );
        $cash_balance = 0.0; $bank_balance = 0.0;
        foreach ( $cash_rows ?: array() as $row ) {
            if ( 'bank' === $row['account_kind'] ) { $bank_balance += (float) $row['balance']; }
            else { $cash_balance += (float) $row['balance']; }
        }

        $party_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.id,p.party_type,p.company_name,COALESCE(SUM(l.debit-l.credit),0) balance
             FROM {$parties} p INNER JOIN {$accounts} a ON a.party_id=p.id AND a.status='active'
             LEFT JOIN {$lines} l ON l.party_account_id=a.id
             LEFT JOIN {$je} j ON j.id=l.entry_id AND j.status='posted' AND j.entry_date <= %s
             WHERE p.status='active' GROUP BY p.id,p.party_type,p.company_name ORDER BY ABS(balance) DESC LIMIT 100",
            $to
        ), ARRAY_A );
        $customer_balances = array(); $supplier_balances = array();
        foreach ( $party_rows ?: array() as $party_row ) {
            $net = (float) $party_row['balance'];
            if ( 'customer' === $party_row['party_type'] && $net > 0 ) {
                $customer_balances[] = array( 'party_id' => (int) $party_row['id'], 'name' => $party_row['company_name'], 'balance' => self::money( $net ) );
            } elseif ( 'supplier' === $party_row['party_type'] && $net < 0 ) {
                $supplier_balances[] = array( 'party_id' => (int) $party_row['id'], 'name' => $party_row['company_name'], 'balance' => self::money( abs( $net ) ) );
            }
        }

        $opened = self::scalar( "SELECT COUNT(*) FROM {$jobs} WHERE created_at BETWEEN %s AND %s", array( $start, $end ) );
        $completed = self::scalar( "SELECT COUNT(*) FROM {$jobs} WHERE completed_at BETWEEN %s AND %s AND status='completed'", array( $start, $end ) );
        $ongoing = self::scalar( "SELECT COUNT(*) FROM {$jobs} WHERE created_at <= %s AND status IN ('new','assigned','in_progress','on_hold')", array( $end ) );
        $overdue = self::scalar( "SELECT COUNT(*) FROM {$jobs} WHERE due_date < %s AND status IN ('new','assigned','in_progress','on_hold')", array( $end ) );
        $cancelled = self::scalar( "SELECT COUNT(*) FROM {$jobs} WHERE updated_at BETWEEN %s AND %s AND status='cancelled'", array( $start, $end ) );
        $avg_days = self::scalar( "SELECT COALESCE(AVG(TIMESTAMPDIFF(SECOND,created_at,completed_at))/86400,0) FROM {$jobs} WHERE completed_at BETWEEN %s AND %s AND status='completed'", array( $start, $end ) );

        $job_statuses = $wpdb->get_results( $wpdb->prepare(
            "SELECT status,COUNT(*) count FROM {$jobs} WHERE created_at BETWEEN %s AND %s GROUP BY status ORDER BY count DESC",
            $start, $end
        ), ARRAY_A );
        $staff = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.id personnel_id,p.full_name,
                    SUM(CASE WHEN j.status='completed' AND j.completed_at BETWEEN %s AND %s THEN 1 ELSE 0 END) completed,
                    SUM(CASE WHEN j.status IN ('new','assigned','in_progress','on_hold') THEN 1 ELSE 0 END) ongoing,
                    COUNT(j.id) assigned_total
             FROM {$personnel} p LEFT JOIN {$jobs} j ON j.assigned_personnel_id=p.id
             WHERE p.status='active' GROUP BY p.id,p.full_name ORDER BY completed DESC,ongoing DESC,p.full_name",
            $start, $end
        ), ARRAY_A );

        $compare_from = sanitize_text_field( (string) $request->get_param( 'compare_from' ) );
        $compare_to = sanitize_text_field( (string) $request->get_param( 'compare_to' ) );
        $comparison = null;
        if ( $compare_from && $compare_to ) {
            $compare_request = new WP_REST_Request( 'GET' );
            $compare_request->set_param( 'from', $compare_from );
            $compare_request->set_param( 'to', $compare_to );
            $compare_dates = self::dates( $compare_request );
            if ( ! is_wp_error( $compare_dates ) ) {
                $comparison = $wpdb->get_row( $wpdb->prepare(
                    "SELECT COALESCE(SUM(CASE WHEN r.record_type='income' THEN r.amount ELSE 0 END),0) income,
                            COALESCE(SUM(CASE WHEN r.record_type='expense' THEN r.amount ELSE 0 END),0) expense
                     FROM {$ie} r INNER JOIN {$je} j ON j.id=r.journal_entry_id AND j.status='posted'
                     WHERE r.record_date BETWEEN %s AND %s",
                    $compare_dates['from'], $compare_dates['to']
                ), ARRAY_A );
                $comparison = array( 'from' => $compare_dates['from'], 'to' => $compare_dates['to'], 'income' => self::money( $comparison['income'] ?? 0 ), 'expense' => self::money( $comparison['expense'] ?? 0 ), 'profit' => self::money( (float) ( $comparison['income'] ?? 0 ) - (float) ( $comparison['expense'] ?? 0 ) ) );
            }
        }

        return new WP_REST_Response( array(
            'period' => $dates,
            'generated_at' => current_time( 'mysql' ),
            'kpis' => array(
                'income' => self::money( $income ), 'expense' => self::money( $expense ),
                'profit' => self::money( $profit ), 'profitability' => $income > 0 ? self::money( ( $profit / $income ) * 100 ) : null,
                'collected' => self::money( $collected ), 'paid' => self::money( $paid ),
                'receivable' => self::money( $receivable ), 'payable' => self::money( $payable ),
                'cash_balance' => self::money( $cash_balance ), 'bank_balance' => self::money( $bank_balance ),
            ),
            'counts' => array( 'income_expense_records' => (int) ( $totals['record_count'] ?? 0 ), 'jobs_opened' => (int) $opened, 'jobs_completed' => (int) $completed, 'jobs_ongoing' => (int) $ongoing, 'jobs_overdue' => (int) $overdue, 'jobs_cancelled' => (int) $cancelled, 'average_completion_days' => self::money( $avg_days ) ),
            'categories' => $categories ?: array(), 'trend' => $trend ?: array(), 'trend_granularity' => $trend_granularity,
            'job_statuses' => $job_statuses ?: array(), 'personnel' => $staff ?: array(),
            'cash_accounts' => $cash_rows ?: array(), 'customers' => $customer_balances, 'suppliers' => $supplier_balances, 'comparison' => $comparison,
            'limitations' => array(
                'due_dates' => 'Cari hesap ve ödeme kayıtlarında vade tarihi alanı bulunmadığı için vadesi geçmiş alacaklar ve yaklaşan ödemeler hesaplanmıyor.',
                'income_sources' => 'Gelir/gider kayıtlarında ayrı kaynak alanı bulunmadığından kategori bazlı rapor sunuluyor.',
                'cash_date' => 'Kasa ve banka bakiyeleri seçilen bitiş tarihi itibarıyla hesaplanır.',
            ),
        ), 200 );
    }

    public static function drilldown( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $dates = self::dates( $request );
        if ( is_wp_error( $dates ) ) { return $dates; }
        $kind = sanitize_key( (string) $request->get_param( 'kind' ) );
        $from = $dates['from']; $to = $dates['to'];
        $ie = self::t( 'income_expenses' ); $je = self::t( 'journal_entries' );
        $cat = self::t( 'categories' ); $cash = self::t( 'cash_accounts' );
        $tx = self::t( 'cash_transactions' ); $lines = self::t( 'journal_lines' );
        $accounts = self::t( 'accounts' ); $parties = self::t( 'parties' );
        $jobs = self::t( 'jobs' ); $personnel = self::t( 'personnel' );
        $rows = array(); $args = array();

        if ( in_array( $kind, array( 'income', 'expense' ), true ) ) {
            $sql = "SELECT r.id,r.record_date date,r.record_type type,c.category_name category,a.account_name account,r.amount,r.document_no,r.description,j.entry_no
                    FROM {$ie} r INNER JOIN {$je} j ON j.id=r.journal_entry_id AND j.status='posted'
                    INNER JOIN {$cat} c ON c.id=r.category_id INNER JOIN {$cash} a ON a.id=r.cash_account_id
                    WHERE r.record_type=%s AND r.record_date BETWEEN %s AND %s";
            $args = array( $kind, $from, $to );
            $category_id = absint( $request->get_param( 'category_id' ) );
            if ( $category_id ) { $sql .= ' AND r.category_id=%d'; $args[] = $category_id; }
            $sql .= ' ORDER BY r.record_date DESC,r.id DESC LIMIT 2000';
        } elseif ( in_array( $kind, array( 'collection', 'payment', 'cash' ), true ) ) {
            $sql = "SELECT t.id,t.transaction_date date,t.direction type,t.amount,t.document_no,t.description,a.account_name account,j.entry_no,j.entry_type,
                    p.company_name party
                    FROM {$tx} t INNER JOIN {$je} j ON j.id=t.journal_entry_id AND j.status='posted'
                    INNER JOIN {$cash} a ON a.id=t.cash_account_id
                    LEFT JOIN {$lines} l ON l.entry_id=j.id AND l.party_account_id IS NOT NULL
                    LEFT JOIN {$accounts} ca ON ca.id=l.party_account_id LEFT JOIN {$parties} p ON p.id=ca.party_id
                    WHERE t.transaction_date BETWEEN %s AND %s";
            $args = array( $from, $to );
            if ( 'collection' === $kind ) { $sql .= " AND j.entry_type='collection' AND t.direction='in'"; }
            if ( 'payment' === $kind ) { $sql .= " AND j.entry_type='payment' AND t.direction='out'"; }
            $cash_account_id = absint( $request->get_param( 'cash_account_id' ) );
            if ( $cash_account_id ) { $sql .= ' AND t.cash_account_id=%d'; $args[] = $cash_account_id; }
            $sql .= ' ORDER BY t.transaction_date DESC,t.id DESC LIMIT 2000';
        } elseif ( 'accounts' === $kind ) {
            $sql = "SELECT p.id party_id,p.party_type,p.company_name,
                    COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit-l.credit ELSE 0 END),0) net_balance
                    FROM {$parties} p INNER JOIN {$accounts} a ON a.party_id=p.id AND a.status='active'
                    LEFT JOIN {$lines} l ON l.party_account_id=a.id
                    LEFT JOIN {$je} j ON j.id=l.entry_id AND j.status='posted' AND j.entry_date <= %s
                    WHERE p.status='active' GROUP BY p.id,p.party_type,p.company_name
                    HAVING net_balance<>0 ORDER BY ABS(net_balance) DESC LIMIT 2000";
            $args = array( $to );
        } elseif ( 'party' === $kind ) {
            $party_id = absint( $request->get_param( 'party_id' ) );
            if ( ! $party_id ) { return new WP_Error( 'sg_report_party', 'Cari hesap seçilmedi.', array( 'status' => 400 ) ); }
            $sql = "SELECT j.id,j.entry_no,j.entry_date date,j.entry_type type,j.document_no,j.description,l.debit,l.credit,
                    (SELECT SUM(l2.debit-l2.credit) FROM {$lines} l2 INNER JOIN {$je} j2 ON j2.id=l2.entry_id AND j2.status='posted'
                     INNER JOIN {$accounts} a2 ON a2.id=l2.party_account_id WHERE a2.party_id=%d AND j2.entry_date <= j.entry_date) running_balance
                    FROM {$accounts} a INNER JOIN {$lines} l ON l.party_account_id=a.id
                    INNER JOIN {$je} j ON j.id=l.entry_id AND j.status='posted'
                    WHERE a.party_id=%d AND j.entry_date BETWEEN %s AND %s
                    ORDER BY j.entry_date,j.id,l.id LIMIT 2000";
            $args = array( $party_id, $party_id, $from, $to );
        } elseif ( 'jobs' === $kind || 'personnel' === $kind ) {
            $sql = "SELECT j.id,j.job_no,j.title,j.status,j.created_at date,j.due_date,j.completed_at,j.assigned_personnel_id,p.full_name assigned_name,
                    CASE WHEN j.completed_at IS NOT NULL THEN ROUND(TIMESTAMPDIFF(SECOND,j.created_at,j.completed_at)/86400,2) ELSE NULL END duration_days
                    FROM {$jobs} j LEFT JOIN {$personnel} p ON p.id=j.assigned_personnel_id
                    WHERE j.created_at BETWEEN %s AND %s";
            $args = array( $from . ' 00:00:00', $to . ' 23:59:59' );
            if ( 'personnel' === $kind ) {
                $personnel_id = absint( $request->get_param( 'personnel_id' ) );
                if ( $personnel_id ) { $sql .= ' AND j.assigned_personnel_id=%d'; $args[] = $personnel_id; }
            }
            $status = sanitize_key( (string) $request->get_param( 'status' ) );
            if ( in_array( $status, array( 'new', 'assigned', 'in_progress', 'on_hold', 'completed', 'cancelled' ), true ) ) { $sql .= ' AND j.status=%s'; $args[] = $status; }
            $sql .= ' ORDER BY j.created_at DESC,j.id DESC LIMIT 2000';
        } else {
            return new WP_Error( 'sg_report_kind', 'İstenen rapor detayı desteklenmiyor.', array( 'status' => 400 ) );
        }
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
        return new WP_REST_Response( array( 'kind' => $kind, 'period' => $dates, 'items' => $rows ?: array(), 'count' => count( $rows ?: array() ) ), 200 );
    }

    public static function export( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $kind = sanitize_key( (string) $request->get_param( 'kind' ) );
        if ( 'summary' === $kind ) {
            $summary = self::summary( $request );
            if ( is_wp_error( $summary ) ) { return $summary; }
            $data = $summary->get_data();
            $items = array();
            foreach ( $data['kpis'] as $key => $value ) { $items[] = array( 'bölüm' => 'Finansal göstergeler', 'kalem' => $key, 'değer' => $value ); }
            foreach ( $data['counts'] as $key => $value ) { $items[] = array( 'bölüm' => 'Operasyon göstergeleri', 'kalem' => $key, 'değer' => $value ); }
            foreach ( $data['categories'] as $row ) { $items[] = array( 'bölüm' => 'Kategori', 'kalem' => $row['record_type'] . ' · ' . $row['category_name'], 'değer' => $row['amount'] ); }
            foreach ( $data['trend'] as $row ) {
                $items[] = array( 'bölüm' => 'Aylık trend', 'kalem' => $row['period'] . ' · gelir', 'değer' => $row['income'] );
                $items[] = array( 'bölüm' => 'Aylık trend', 'kalem' => $row['period'] . ' · gider', 'değer' => $row['expense'] );
            }
        } else {
            $detail = self::drilldown( $request );
            if ( is_wp_error( $detail ) ) { return $detail; }
            $items = $detail->get_data()['items'];
        }
        if ( empty( $items ) ) {
            return new WP_REST_Response( array( 'filename' => 'surmeli-grup-rapor.csv', 'csv' => "\xEF\xBB\xBFVeri bulunamadı\r\n" ), 200 );
        }
        $headers = array_keys( $items[0] );
        $stream = fopen( 'php://temp', 'r+' );
        fputcsv( $stream, $headers, ';' );
        foreach ( $items as $item ) {
            $row = array();
            foreach ( $headers as $header ) { $row[] = $item[ $header ] ?? ''; }
            fputcsv( $stream, $row, ';' );
        }
        rewind( $stream );
        $csv = "\xEF\xBB\xBF" . stream_get_contents( $stream );
        fclose( $stream );
        return new WP_REST_Response( array( 'filename' => 'surmeli-grup-' . ( $kind ?: 'rapor' ) . '.csv', 'csv' => $csv ), 200 );
    }
}
