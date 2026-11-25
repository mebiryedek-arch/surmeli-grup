<?php
/** Party sub-ledgers and balanced journal posting. */
defined( 'ABSPATH' ) || exit;
final class SG_Accounts {
    public static function init(): void { add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) ); }
    private static function table( string $name ): string { global $wpdb; return $wpdb->prefix . 'sg_' . $name; }
    public static function register_routes(): void {
        register_rest_route( 'sg/v1', '/accounts', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'list_accounts' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'accounts.view' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'post_movement' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'accounts.adjust' ) ),
        ) );
        register_rest_route( 'sg/v1', '/accounts/(?P<party_id>\d+)/movements', array(
            'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'list_movements' ), 'permission_callback' => static fn() => is_user_logged_in() && SG_Permissions::current_user_can( 'accounts.view' ),
        ) );
    }
    public static function list_accounts( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $type = sanitize_key( (string) $request->get_param( 'type' ) );
        $search = sanitize_text_field( (string) $request->get_param( 'search' ) );
        $where = array( "p.status='active'" ); $args = array();
        if ( in_array( $type, array( 'customer', 'supplier' ), true ) ) { $where[] = 'p.party_type=%s'; $args[] = $type; }
        if ( $search !== '' ) { $like = '%' . $wpdb->esc_like( $search ) . '%'; $where[] = '(p.company_name LIKE %s OR p.party_code LIKE %s OR p.phone LIKE %s)'; array_push( $args, $like, $like, $like ); }
        $sql = "SELECT p.id AS party_id,p.party_type,p.party_code,p.company_name,p.contact_name,p.phone,a.id AS account_id,
            COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.debit ELSE 0 END),0) AS total_debit,COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.credit ELSE 0 END),0) AS total_credit
            FROM " . self::table('parties') . " p
            LEFT JOIN " . self::table('accounts') . " a ON a.party_id=p.id AND a.status='active'
            LEFT JOIN " . self::table('journal_lines') . " jl ON jl.party_account_id=a.id
            LEFT JOIN " . self::table('journal_entries') . " je ON je.id=jl.entry_id AND je.status='posted'
            WHERE " . implode(' AND ', $where) . " GROUP BY p.id,a.id ORDER BY p.company_name ASC LIMIT 500";
        // Keep only posted journal lines in the aggregate (the LEFT JOIN preserves zero-balance parties).
        $sql = str_replace("LEFT JOIN " . self::table('journal_entries') . " je ON je.id=jl.entry_id AND je.status='posted'", "LEFT JOIN " . self::table('journal_entries') . " je ON je.id=jl.entry_id AND je.status='posted'", $sql);
        $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $rows ?: array() as &$row ) {
            $debit = (float) $row['total_debit']; $credit = (float) $row['total_credit'];
            $net = $debit - $credit;
            $row['balance'] = number_format( abs( $net ), 2, '.', '' );
            $row['balance_side'] = $net > 0 ? 'debit' : ( $net < 0 ? 'credit' : 'zero' );
            $row['total_debit'] = number_format( $debit, 2, '.', '' ); $row['total_credit'] = number_format( $credit, 2, '.', '' );
        }
        return new WP_REST_Response( array( 'items' => $rows ?: array(), 'count' => count( $rows ?: array() ) ), 200 );
    }
    public static function list_movements( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb; $party_id = absint( $request['party_id'] );
        $party = $wpdb->get_row( $wpdb->prepare( "SELECT id,party_type,party_code,company_name FROM " . self::table('parties') . " WHERE id=%d AND status='active'", $party_id ), ARRAY_A );
        if ( ! $party ) { return new WP_Error( 'sg_account_party_missing', 'Cari kartı bulunamadı.', array( 'status' => 404 ) ); }
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT je.id,je.entry_no,je.entry_date,je.entry_type,je.document_no,je.description,je.created_at,jl.debit,jl.credit
            FROM " . self::table('accounts') . " a INNER JOIN " . self::table('journal_lines') . " jl ON jl.party_account_id=a.id
            INNER JOIN " . self::table('journal_entries') . " je ON je.id=jl.entry_id
            WHERE a.party_id=%d AND je.status='posted' ORDER BY je.entry_date DESC,je.id DESC LIMIT 500", $party_id ), ARRAY_A );
        return new WP_REST_Response( array( 'party' => $party, 'items' => $rows ?: array() ), 200 );
    }
    public static function post_movement( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $party_id = absint( $request->get_param('party_id') );
        $kind = sanitize_key( (string) $request->get_param('movement_type') );
        $amount_raw = trim( (string) $request->get_param('amount') );
        $date = sanitize_text_field( (string) $request->get_param('entry_date') );
        $description = sanitize_textarea_field( (string) $request->get_param('description') );
        $document = sanitize_text_field( (string) $request->get_param('document_no') );
        $key = sanitize_text_field( (string) $request->get_header('Idempotency-Key') );
        if ( ! preg_match('/^[0-9]{1,14}(\.[0-9]{1,2})?$/', $amount_raw ) || (float) $amount_raw <= 0 ) { return new WP_Error('sg_account_amount','Tutar 0,01 TL veya daha büyük, en fazla iki ondalık basamaklı olmalıdır.',array('status'=>400)); }
        if ( ! in_array( $kind, array('debit','credit','opening_debit','opening_credit'), true ) ) { return new WP_Error('sg_account_type','Geçersiz cari hareket türü.',array('status'=>400)); }
        $date_obj = DateTime::createFromFormat('!Y-m-d',$date);
        if ( ! $date_obj || $date_obj->format('Y-m-d') !== $date ) { return new WP_Error('sg_account_date','Geçerli bir işlem tarihi girin.',array('status'=>400)); }
        if ( strlen($key) < 16 || strlen($key) > 100 ) { return new WP_Error('sg_account_idempotency','Tekrarlı kayıt koruması için Idempotency-Key gereklidir.',array('status'=>400)); }
        $party = $wpdb->get_row( $wpdb->prepare("SELECT * FROM " . self::table('parties') . " WHERE id=%d AND status='active'",$party_id), ARRAY_A );
        if ( ! $party ) { return new WP_Error('sg_account_party_missing','Aktif müşteri veya tedarikçi bulunamadı.',array('status'=>404)); }
        $parts = explode('.', $amount_raw, 2); $amount = ltrim($parts[0], '0'); if ($amount === '') { $amount = '0'; } $amount .= '.' . str_pad($parts[1] ?? '', 2, '0');
        $hash = hash('sha256', wp_json_encode(array($party_id,$kind,$amount,$date,$document,$description)));
        $idem = self::table('idempotency_keys');
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$idem} WHERE idempotency_key=%s",$key),ARRAY_A);
        if ( $existing ) {
            if ( (int)$existing['user_id'] !== get_current_user_id() || ! hash_equals($existing['payload_hash'],$hash) ) { return new WP_Error('sg_account_idempotency_conflict','Bu işlem anahtarı daha önce farklı bir kayıt için kullanılmış.',array('status'=>409)); }
            if ( $existing['status']==='completed' ) { return new WP_REST_Response(array('message'=>'Bu işlem daha önce kaydedildi.','entry_id'=>(int)$existing['result_reference'],'duplicate'=>true),200); }
            return new WP_Error('sg_account_processing','Bu işlem halen işleniyor. Lütfen hareket listesini kontrol edin.',array('status'=>409));
        }
        $wpdb->query('START TRANSACTION');
        try {
            $insert_idem = $wpdb->insert($idem,array('idempotency_key'=>$key,'operation'=>'account.movement','user_id'=>get_current_user_id(),'payload_hash'=>$hash,'status'=>'processing','created_at'=>current_time('mysql',true)),array('%s','%s','%d','%s','%s','%s'));
            if ( false === $insert_idem ) { throw new RuntimeException('İşlem anahtarı kaydedilemedi.'); }
            $idempotency_id = (int) $wpdb->insert_id;
            $account = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table('accounts') . " WHERE party_id=%d AND account_type=%s FOR UPDATE",$party_id,$party['party_type']==='customer'?'receivable':'payable'),ARRAY_A);
            if ( str_starts_with($kind,'opening_') ) {
                $opening_exists=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . self::table('journal_entries') . " WHERE source_module='accounts' AND source_id=%d AND entry_type='opening_balance' AND status='posted'",$party_id));
                if ( $opening_exists ) { throw new RuntimeException('Bu cari hesap için açılış bakiyesi daha önce kaydedilmiş. Düzeltme gerekiyorsa normal borç/alacak hareketi kullanın.'); }
            }
            if ( ! $account ) {
                $acct_type = $party['party_type']==='customer'?'receivable':'payable';
                $code = 'CA-' . str_pad((string)$party_id,8,'0',STR_PAD_LEFT);
                $ledger_code = 'PA-' . str_pad((string)$party_id,8,'0',STR_PAD_LEFT);
                $now = current_time('mysql',true);
                if ( false === $wpdb->insert(self::table('ledger_accounts'),array('account_code'=>$ledger_code,'account_name'=>$party['company_name'],'account_class'=>$acct_type,'is_system'=>0,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now)) ) { throw new RuntimeException('Cari muhasebe hesabı oluşturulamadı.'); }
                if ( false === $wpdb->insert(self::table('accounts'),array('party_id'=>$party_id,'account_code'=>$code,'account_type'=>$acct_type,'status'=>'active','created_by'=>get_current_user_id(),'updated_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now)) ) { throw new RuntimeException('Cari hesap kartı oluşturulamadı.'); }
                $account_id=(int)$wpdb->insert_id;
                $ledger_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM " . self::table('ledger_accounts') . " WHERE account_code=%s",$ledger_code));
            } else {
                $account_id=(int)$account['id'];
                $ledger_code = 'PA-' . str_pad((string)$party_id,8,'0',STR_PAD_LEFT);
                $ledger_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM " . self::table('ledger_accounts') . " WHERE account_code=%s",$ledger_code));
                if ( ! $ledger_id ) { throw new RuntimeException('Cari muhasebe hesabı bulunamadı.'); }
            }
            $counter_code = '399999';
            $counter_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM " . self::table('ledger_accounts') . " WHERE account_code=%s",$counter_code));
            if ( ! $counter_id ) {
                $now=current_time('mysql',true);
                if ( false === $wpdb->insert(self::table('ledger_accounts'),array('account_code'=>$counter_code,'account_name'=>'Cari Açılış ve Düzeltme Karşılığı','account_class'=>'equity','is_system'=>1,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now)) ) { throw new RuntimeException('Karşı hesap oluşturulamadı.'); }
                $counter_id=(int)$wpdb->insert_id;
            }
            $entry_no='CH-' . wp_date('Ymd') . '-' . strtoupper(wp_generate_password(8,false,false));
            $source_key='account-movement:' . hash('sha256',$key);
            $now=current_time('mysql',true);
            if ( false === $wpdb->insert(self::table('journal_entries'),array('entry_no'=>$entry_no,'entry_date'=>$date,'entry_type'=>str_starts_with($kind,'opening_')?'opening_balance':'account_adjustment','source_module'=>'accounts','source_id'=>$party_id,'source_key'=>$source_key,'document_no'=>$document,'description'=>$description,'status'=>'posted','created_by'=>get_current_user_id(),'posted_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now,'posted_at'=>$now)) ) { throw new RuntimeException('Yevmiye kaydı oluşturulamadı.'); }
            $entry_id=(int)$wpdb->insert_id;
            $is_debit=in_array($kind,array('debit','opening_debit'),true);
            $party_debit=$is_debit?$amount:'0.00'; $party_credit=$is_debit?'0.00':$amount;
            $counter_debit=$is_debit?'0.00':$amount; $counter_credit=$is_debit?$amount:'0.00';
            $lines=array(
                array('entry_id'=>$entry_id,'line_no'=>1,'ledger_account_id'=>$ledger_id,'party_account_id'=>$account_id,'description'=>$description,'debit'=>$party_debit,'credit'=>$party_credit,'created_at'=>$now),
                array('entry_id'=>$entry_id,'line_no'=>2,'ledger_account_id'=>$counter_id,'description'=>$description,'debit'=>$counter_debit,'credit'=>$counter_credit,'created_at'=>$now),
            );
            foreach($lines as $line){ if(false===$wpdb->insert(self::table('journal_lines'),$line)){ throw new RuntimeException('Yevmiye satırları kaydedilemedi.'); } }
            $updated=$wpdb->update($idem,array('result_reference'=>(string)$entry_id,'status'=>'completed','completed_at'=>$now),array('id'=>$idempotency_id));
            if(false===$updated){throw new RuntimeException('İşlem tamamlandı bilgisi yazılamadı.');}
            $wpdb->insert(self::table('audit_logs'),array('user_id'=>get_current_user_id(),'event_key'=>'account.movement_posted','object_type'=>'party','object_id'=>$party_id,'request_id'=>$key,'details'=>wp_json_encode(array('entry_id'=>$entry_id,'amount'=>$amount,'type'=>$kind)),'created_at'=>$now));
            $wpdb->query('COMMIT');
            return new WP_REST_Response(array('message'=>'Cari hareket kaydedildi.','entry_id'=>$entry_id),201);
        } catch(Throwable $e){ $wpdb->query('ROLLBACK'); return new WP_Error('sg_account_post_failed',$e->getMessage(),array('status'=>500)); }
    }
}
