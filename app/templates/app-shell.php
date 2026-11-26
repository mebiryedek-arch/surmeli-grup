<?php
/** Responsive application shell. */
defined( 'ABSPATH' ) || exit;
?>
<div class="sg-app" id="sg-app-root">
    <div class="sg-loading-overlay" id="sg-loading-overlay" role="status" aria-live="polite" aria-label="İşlem sürüyor" hidden>
        <div class="sg-loading-card">
            <img class="sg-loading-logo" src="<?php echo esc_url( SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg' ); ?>" alt="">
            <span class="sg-loading-spinner" aria-hidden="true"></span>
            <strong>İşleminiz gerçekleştiriliyor</strong>
            <small>Lütfen bekleyin…</small>
        </div>
    </div>
    <header class="sg-topbar">
        <div class="sg-topbar-left">
            <a class="sg-home-button" href="<?php echo esc_url( SG_APP_URL ); ?>" aria-label="Ana sayfa" title="Ana sayfa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            <button class="sg-menu-toggle" type="button" aria-label="Menüyü aç" aria-expanded="false" aria-controls="sg-sidebar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        </div>
        <a class="sg-topbar-logo" href="<?php echo esc_url( SG_APP_URL ); ?>" aria-label="Sürmeli Grup ana ekran"><img src="<?php echo esc_url( SG_PLUGIN_URL . 'assets/images/surmeli-grup-logo.svg' ); ?>" alt="Sürmeli Grup"></a>
        <div class="sg-topbar-right"><span class="sg-user-chip" id="sg-user-name" data-logout-url="<?php echo esc_url( wp_logout_url( SG_APP_URL ) ); ?>">Hesap bilgisi yükleniyor…</span></div>
    </header>
    <button class="sg-menu-backdrop" type="button" aria-label="Menüyü kapat" tabindex="-1"></button>
    <div class="sg-layout">
        <aside class="sg-sidebar" id="sg-sidebar" aria-label="Ana navigasyon">
            <nav>
                <a class="is-active" href="#dashboard" data-section="dashboard"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="10" width="8" height="11" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/></svg></span><span class="sg-nav-label">Genel Bakış</span></a>
                <span class="sg-nav-heading">İşletme</span>
                <a href="#customers" data-section="customers" data-permission="customers.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM16 3.2a4 4 0 0 1 0 7.6M17 14h.5a4.5 4.5 0 0 1 4.5 4.5V20"/></svg></span><span class="sg-nav-label">Müşteriler</span></a>
                <a href="#suppliers" data-section="suppliers" data-permission="suppliers.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21V7l9-4 9 4v14M3 11h18M8 21v-6h8v6M8 8h.01M12 8h.01M16 8h.01"/></svg></span><span class="sg-nav-label">Tedarikçiler</span></a>
                <a href="#accounts" data-section="accounts" data-permission="accounts.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg></span><span class="sg-nav-label">Cari Hesaplar</span></a>
                <a href="#cash-bank" data-section="cash-bank" data-permission="cash.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9h18v11H3zM2 9l2-5h16l2 5M8 14h8M12 12v4"/></svg></span><span class="sg-nav-label">Kasa ve Banka</span></a>
                <a href="#income-expense" data-section="income-expense" data-permission="income_expense.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h17M7 15l4-4 3 2 6-7M16 6h4v4"/></svg></span><span class="sg-nav-label">Gelir ve Gider</span></a>
                <span class="sg-nav-heading">Organizasyon</span>
                <a href="#personnel" data-section="personnel" data-permission="personnel.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M16 4a4 4 0 0 1 0 8M18 15a5 5 0 0 1 4 5v1"/></svg></span><span class="sg-nav-label">Personel</span></a>
                <a href="#jobs" data-section="jobs" data-permission="jobs.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 9h18M8 14l2 2 5-5"/></svg></span><span class="sg-nav-label">İş Takip</span></a>
                <a href="#reports" data-section="reports" data-permission="reports.view"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V4M4 20h17M8 16v-4M13 16V7M18 16v-7"/></svg></span><span class="sg-nav-label">Raporlar</span></a>
                            <span class="sg-nav-heading">Yönetim</span>
                <a href="#settings" data-section="settings" data-permission="settings.manage"><span class="sg-nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 1.7-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-2.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1L8 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H6.7v-2.4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9L8 8.6l1.7-1.7.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5v-.2h2.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.7 1.7-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2V14h-.2a1.7 1.7 0 0 0-1.5 1z"/></svg></span><span class="sg-nav-label">Ayarlar</span></a>
            </nav>
            <div class="sg-sidebar-foot">
                <div class="sg-sidebar-secure"><span class="sg-status-dot"></span> Güvenli oturum</div>
                <a class="sg-logout-link" href="<?php echo esc_url( wp_logout_url( SG_APP_URL ) ); ?>"><span aria-hidden="true">↪</span> Oturumu kapat</a>
            </div>
        </aside>
        <main class="sg-main" id="sg-main-content">
            <div class="sg-page-heading"><div><p class="sg-eyebrow">SÜRMELİ GRUP</p><h1>İşletme Yönetimi</h1><p>Günlük operasyonlarınız için merkezi yönetim ekranı.</p></div><span class="sg-version">Sürüm <?php echo esc_html( SG_VERSION ); ?></span></div>
            <div class="sg-section-view" data-section-view="dashboard">
            <section class="sg-dashboard-grid" id="sg-dashboard-cards" aria-live="polite">
                <article class="sg-dashboard-card"><span class="sg-dashboard-label">Aktif Müşteriler</span><strong data-stat="customers">—</strong><small>kayıtlı müşteri</small></article>
                <article class="sg-dashboard-card"><span class="sg-dashboard-label">Aktif Tedarikçiler</span><strong data-stat="suppliers">—</strong><small>kayıtlı tedarikçi</small></article>
                <article class="sg-dashboard-card"><span class="sg-dashboard-label">Açık İşler</span><strong data-stat="jobs_open">—</strong><small>tamamlanmamış görev</small></article>
                <article class="sg-dashboard-card sg-dashboard-card-warning"><span class="sg-dashboard-label">Geciken İşler</span><strong data-stat="jobs_overdue">—</strong><small>termin tarihi geçmiş</small></article>
            </section>
            <section class="sg-dashboard-lower">
                <article class="sg-panel">
                    <div class="sg-panel-heading"><div><p class="sg-card-kicker">OPERASYON</p><h2>Son İşler</h2></div><span class="sg-panel-meta" id="sg-dashboard-updated">Veriler yükleniyor</span></div>
                    <div class="sg-table-wrap">
                        <table class="sg-dashboard-table">
                            <thead><tr><th>İş No</th><th>İş Başlığı</th><th>Durum</th><th>Termin</th></tr></thead>
                            <tbody id="sg-recent-jobs"><tr><td colspan="4" class="sg-table-empty">Kayıtlar yükleniyor…</td></tr></tbody>
                        </table>
                    </div>
                </article>
                <aside class="sg-panel sg-dashboard-side">
                    <p class="sg-card-kicker">SİSTEM BİLGİSİ</p>
                    <h2>Genel Durum</h2>
                    <div class="sg-status-line"><span>Toplam iş</span><strong data-stat="jobs_total">—</strong></div>
                    <div class="sg-status-line"><span>Tamamlanan iş</span><strong data-stat="jobs_completed">—</strong></div>
                    <div class="sg-status-line"><span>Oturum</span><strong id="sg-session-state">Doğrulanıyor</strong></div>
                    <div class="sg-status-line"><span>Bağlantı</span><strong id="sg-api-state">Kontrol ediliyor</strong></div>
                    <div class="sg-finance-note">Cari, kasa ve banka özetleri ilgili finans modülleri devreye alındığında gerçek muhasebe kayıtlarından gösterilecektir. Bu aşamada örnek bakiye gösterilmez.</div>
                </aside>
            </section>

            <section class="sg-panel sg-dashboard-calendar-panel"><div class="sg-calendar-head"><div><p class="sg-card-kicker">PLANLAMA</p><h2>Görev Takvimi</h2></div><div class="sg-calendar-head-actions"><button type="button" class="sg-button sg-button-light" id="sg-dashboard-calendar-prev">‹</button><strong id="sg-dashboard-calendar-title">Takvim</strong><button type="button" class="sg-button sg-button-light" id="sg-dashboard-calendar-next">›</button><a href="#jobs" class="sg-button sg-button-light">Tüm görevler</a></div></div><div class="sg-calendar-grid" id="sg-dashboard-calendar-grid"></div><div class="sg-calendar-holiday-list" id="sg-dashboard-holiday-list" aria-live="polite"></div></section>
            <p class="sg-footnote">Veriler veritabanından anlık olarak okunur. Görebildiğiniz iş kayıtları kullanıcı yetkilerinizle sınırlandırılır.</p>
            </div>
            <section class="sg-section-view" data-section-view="customers" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">İŞLETME / KAYITLAR</p><h1>Müşteri Yönetimi</h1><p>Müşteri kartlarını görüntüleyin, arayın ve yönetin.</p></div><button type="button" class="sg-button sg-button-primary" id="sg-customer-add" data-permission="customers.create">+ Yeni Müşteri</button></div>
                <div class="sg-customer-toolbar"><label class="sg-search-field"><span> Müşteri ara</span><input type="search" id="sg-customer-search" placeholder="Firma, yetkili, telefon veya müşteri kodu"></label><label class="sg-filter-field"><span>Durum</span><select id="sg-customer-status"><option value="">Tüm kayıtlar</option><option value="active">Aktif</option><option value="passive">Pasif</option></select></label><span class="sg-customer-count" id="sg-customer-count">Kayıtlar yükleniyor…</span></div>
                <div class="sg-panel sg-customer-list-panel"><div class="sg-table-wrap"><table class="sg-dashboard-table sg-customer-table"><thead><tr><th>Müşteri Kodu</th><th>Müşteri / Firma</th><th>Yetkili</th><th>Telefon</th><th>E-posta</th><th>Durum</th><th>İşlem</th></tr></thead><tbody id="sg-customer-rows"><tr><td colspan="7" class="sg-table-empty">Müşteriler yükleniyor…</td></tr></tbody></table></div></div>
                <dialog class="sg-modal sg-customer-editor" id="sg-customer-editor">
                    <div class="sg-panel-heading"><div><p class="sg-card-kicker">MÜŞTERİ KARTI</p><h2 id="sg-customer-editor-title">Yeni Müşteri</h2></div><button type="button" class="sg-button sg-button-light" id="sg-customer-cancel">Kapat</button></div>
                    <form id="sg-customer-form" class="sg-customer-form">
                        <input type="hidden" id="sg-customer-id" value="">
                        <label><span>Müşteri türü</span><select id="sg-customer-kind" required><option value="">Önce Ayarlar bölümünden müşteri türü ekleyin</option></select></label>
                        <label class="sg-field-wide"><span>Firma / Müşteri adı *</span><input id="sg-customer-company" required maxlength="190"></label>
                        <label><span>Yetkili kişi</span><input id="sg-customer-contact" maxlength="190"></label>
                        <label><span>Telefon</span><input id="sg-customer-phone" type="tel" maxlength="17" placeholder="0 (555) 555 55 55" inputmode="numeric" autocomplete="tel"></label>
                        <label><span>E-posta</span><input id="sg-customer-email" type="email" maxlength="190"></label>
                        <label><span>Vergi dairesi</span><input id="sg-customer-tax-office" maxlength="120"></label>
                        <label><span>Vergi / T.C. kimlik no</span><input id="sg-customer-tax-number" inputmode="numeric" maxlength="32"></label>
                        <label><span>Durum</span><select id="sg-customer-state"><option value="active">Aktif</option><option value="passive">Pasif</option></select></label>
                        <label class="sg-field-wide"><span>Adres</span><textarea id="sg-customer-address" rows="3"></textarea></label>
                        <label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-customer-description" rows="2"></textarea></label>
                        <div class="sg-form-actions"><p id="sg-customer-message" role="status"></p><button type="submit" class="sg-button sg-button-primary" id="sg-customer-save">Kaydet</button></div>
                    </form>
                </dialog>
            </section>
            <section class="sg-section-view" data-section-view="accounts" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">FİNANS / CARİ TAKİP</p><h1>Cari Hesap Yönetimi</h1><p>Müşteri ve tedarikçi bakiyelerini, borç ve alacak hareketlerini izleyin.</p></div></div>
                <div class="sg-customer-toolbar"><label class="sg-search-field"><span>Cari ara</span><input type="search" id="sg-account-search" placeholder="Firma, cari kodu veya telefon"></label><label class="sg-filter-field"><span>Cari türü</span><select id="sg-account-type"><option value="">Tüm cariler</option><option value="customer">Müşteriler</option><option value="supplier">Tedarikçiler</option></select></label><span class="sg-customer-count" id="sg-account-count">Cariler yükleniyor…</span></div>
                <div class="sg-panel sg-customer-list-panel"><div class="sg-table-wrap"><table class="sg-dashboard-table sg-customer-table"><thead><tr><th>Cari Kodu</th><th>Firma</th><th>Tür</th><th>Toplam Borç</th><th>Toplam Alacak</th><th>Bakiye</th><th>İşlem</th></tr></thead><tbody id="sg-account-rows"><tr><td colspan="7" class="sg-table-empty">Cariler yükleniyor…</td></tr></tbody></table></div></div>
                <dialog class="sg-modal sg-account-dialog" id="sg-account-dialog">
                    <div class="sg-panel-heading"><div><p class="sg-card-kicker">CARİ HESAP EKSTRESİ</p><h2 id="sg-account-party-title">Cari hareketler</h2></div><button type="button" class="sg-button sg-button-light" id="sg-account-close">Kapat</button></div>
                    <div class="sg-account-summary" id="sg-account-summary"></div>
                    <form id="sg-account-movement-form" class="sg-customer-form sg-account-form">
                        <input type="hidden" id="sg-account-party-id">
                        <label><span>Hareket türü</span><select id="sg-account-movement-type"><option value="debit">Borçlandır</option><option value="credit">Alacaklandır</option><option value="opening_debit">Açılış borcu</option><option value="opening_credit">Açılış alacağı</option></select></label>
                        <label><span>Tutar (₺) *</span><input id="sg-account-amount" inputmode="decimal" placeholder="0.00" required></label>
                        <label><span>İşlem tarihi *</span><input id="sg-account-date" type="date" required></label>
                        <label><span>Belge numarası</span><input id="sg-account-document" maxlength="80"></label>
                        <label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-account-description" rows="2"></textarea></label>
                        <div class="sg-form-actions sg-field-wide"><p id="sg-account-message" role="status"></p><button type="submit" class="sg-button sg-button-primary" id="sg-account-save" data-permission="accounts.adjust">Hareket Kaydet</button></div>
                    </form>
                    <h3 class="sg-account-history-title">Hareket geçmişi</h3>
                    <div class="sg-table-wrap"><table class="sg-dashboard-table"><thead><tr><th>Tarih</th><th>İşlem No</th><th>Açıklama</th><th>Borç</th><th>Alacak</th></tr></thead><tbody id="sg-account-movements"><tr><td colspan="5" class="sg-table-empty">Hareketler yükleniyor…</td></tr></tbody></table></div>
                </dialog>
            </section>
            <section class="sg-section-view" data-section-view="cash-bank" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">FİNANS / NAKİT YÖNETİMİ</p><h1>Kasa ve Banka Yönetimi</h1><p>Kasa ve banka bakiyelerini, tahsilatları, ödemeleri ve hesaplar arası transferleri yönetin.</p></div><button type="button" class="sg-button sg-button-primary" id="sg-cash-add" data-permission="cash.create">+ Yeni Hesap</button></div>
                <div class="sg-cash-cards" id="sg-cash-cards"><div class="sg-panel">Hesaplar yükleniyor…</div></div>
                <div class="sg-panel sg-cash-transfer-panel"><div class="sg-panel-heading"><div><p class="sg-card-kicker">HESAPLAR ARASI</p><h2>Kasa / Banka Transferi</h2><p class="sg-panel-subtitle">Kasalar ve banka hesapları arasındaki para aktarımlarını buradan takip edin.</p></div><button type="button" class="sg-button sg-button-primary" id="sg-transfer-add" data-permission="cash.transfer">+ Transfer Ekle</button><p class="sg-transfer-availability" id="sg-transfer-availability" role="status" hidden></p></div>
                    <div class="sg-table-wrap sg-transfer-table-wrap"><table class="sg-dashboard-table"><thead><tr><th>Tarih</th><th>İşlem No</th><th>Kaynak Hesap</th><th>Hedef Hesap</th><th>Tutar</th><th>Açıklama</th></tr></thead><tbody id="sg-cash-transfer-rows"><tr><td colspan="6" class="sg-table-empty">Transfer kayıtları yükleniyor…</td></tr></tbody></table></div>
                </div>
                <dialog class="sg-modal sg-transfer-dialog" id="sg-cash-transfer-dialog"><div class="sg-panel-heading"><div><p class="sg-card-kicker">HESAPLAR ARASI</p><h2>Kasa / Banka Transferi Ekle</h2><p class="sg-panel-subtitle">Kaynak hesaptan hedef hesaba aktarılacak tutarı girin.</p></div><button type="button" class="sg-button sg-button-light" id="sg-cash-transfer-close">Kapat</button></div>
                    <form id="sg-cash-transfer-form" class="sg-customer-form"><label><span>Kaynak hesap *</span><select id="sg-transfer-from" required></select></label><label><span>Hedef hesap *</span><select id="sg-transfer-to" required></select></label><label><span>Tutar (₺) *</span><input id="sg-transfer-amount" inputmode="decimal" required placeholder="0.00"></label><label><span>İşlem tarihi *</span><input id="sg-transfer-date" type="date" required></label><label><span>Belge numarası</span><input id="sg-transfer-document" maxlength="80"></label><label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-transfer-description" rows="2" maxlength="255"></textarea></label><div class="sg-form-actions sg-field-wide"><p id="sg-transfer-message" role="status"></p><button class="sg-button sg-button-primary" type="submit" data-permission="cash.transfer">Transferi Kaydet</button></div></form>
                </dialog>
                <dialog class="sg-modal" id="sg-cash-account-dialog"><div class="sg-panel-heading"><div><p class="sg-card-kicker">KASA / BANKA KARTI</p><h2>Yeni Hesap Oluştur</h2></div><button type="button" class="sg-button sg-button-light" id="sg-cash-account-close">Kapat</button></div>
                    <form id="sg-cash-account-form" class="sg-customer-form"><label><span>Hesap türü *</span><select id="sg-cash-kind"><option value="cash">Kasa</option><option value="bank">Banka</option></select></label><label><span>Hesap adı *</span><input id="sg-cash-name" required maxlength="190" placeholder="Merkez Kasa / Banka hesabı"></label><label><span>Banka adı</span><input id="sg-cash-bank" maxlength="120"></label><label><span>IBAN</span><input id="sg-cash-iban" maxlength="34" placeholder="TR…"></label><label><span>Açılış bakiyesi (₺)</span><input id="sg-cash-opening" inputmode="decimal" placeholder="Boş bırakılabilir"></label><label><span>Açılış tarihi</span><input id="sg-cash-opening-date" type="date"></label><div class="sg-form-actions"><p id="sg-cash-account-message" role="status"></p><button class="sg-button sg-button-primary" type="submit">Hesabı Kaydet</button></div></form>
                </dialog>
                <dialog class="sg-modal sg-account-dialog" id="sg-cash-detail-dialog"><div class="sg-panel-heading"><div><p class="sg-card-kicker">HESAP HAREKETLERİ</p><h2 id="sg-cash-detail-title">Hesap</h2></div><button type="button" class="sg-button sg-button-light" id="sg-cash-detail-close">Kapat</button></div>
                    <div class="sg-account-summary" id="sg-cash-detail-summary"></div>
                    <form id="sg-cash-transaction-form" class="sg-customer-form sg-account-form"><label><span>İşlem türü *</span><select id="sg-cash-transaction-type"><option value="collection">Müşteri Tahsilatı</option><option value="payment">Tedarikçi Ödemesi</option><option value="cash_in">Diğer Para Girişi</option><option value="cash_out">Diğer Para Çıkışı</option></select></label><label><span>Cari seçimi</span><select id="sg-cash-party"><option value="">Önce işlem türünü seçin</option></select></label><label><span>Tutar (₺) *</span><input id="sg-cash-transaction-amount" inputmode="decimal" required></label><label><span>İşlem tarihi *</span><input id="sg-cash-transaction-date" type="date" required></label><label><span>Belge numarası</span><input id="sg-cash-transaction-document" maxlength="80"></label><label><span>Açıklama</span><input id="sg-cash-transaction-description" maxlength="255"></label><div class="sg-form-actions"><p id="sg-cash-transaction-message" role="status"></p><button class="sg-button sg-button-primary" type="submit">Hareket Kaydet</button></div></form>
                    <h3 class="sg-account-history-title">Hareket geçmişi</h3><div class="sg-table-wrap"><table class="sg-dashboard-table"><thead><tr><th>Tarih</th><th>İşlem No</th><th>İşlem / Cari</th><th>Giriş</th><th>Çıkış</th></tr></thead><tbody id="sg-cash-transactions"><tr><td colspan="5" class="sg-table-empty">Hareketler yükleniyor…</td></tr></tbody></table></div>
                </dialog>
            </section>
            <section class="sg-section-view" data-section-view="suppliers" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">İŞLETME / KAYITLAR</p><h1>Tedarikçi Yönetimi</h1><p>Tedarikçi kartlarını görüntüleyin, arayın ve yönetin.</p></div><button type="button" class="sg-button sg-button-primary" id="sg-supplier-add" data-permission="suppliers.create">+ Yeni Tedarikçi</button></div>
                <div class="sg-customer-toolbar"><label class="sg-search-field"><span>Tedarikçi ara</span><input type="search" id="sg-supplier-search" placeholder="Firma, yetkili, telefon veya tedarikçi kodu"></label><label class="sg-filter-field"><span>Durum</span><select id="sg-supplier-status"><option value="">Tüm kayıtlar</option><option value="active">Aktif</option><option value="passive">Pasif</option></select></label><span class="sg-customer-count" id="sg-supplier-count">Kayıtlar yükleniyor…</span></div>
                <div class="sg-panel sg-customer-list-panel"><div class="sg-table-wrap"><table class="sg-dashboard-table sg-customer-table"><thead><tr><th>Tedarikçi Kodu</th><th>Tedarikçi / Firma</th><th>Yetkili</th><th>Telefon</th><th>E-posta</th><th>Durum</th><th>İşlem</th></tr></thead><tbody id="sg-supplier-rows"><tr><td colspan="7" class="sg-table-empty">Tedarikçiler yükleniyor…</td></tr></tbody></table></div></div>
                <dialog class="sg-modal sg-customer-editor" id="sg-supplier-editor">
                    <div class="sg-panel-heading"><div><p class="sg-card-kicker">TEDARİKÇİ KARTI</p><h2 id="sg-supplier-editor-title">Yeni Tedarikçi</h2></div><button type="button" class="sg-button sg-button-light" id="sg-supplier-cancel">Kapat</button></div>
                    <form id="sg-supplier-form" class="sg-customer-form">
                        <input type="hidden" id="sg-supplier-id" value="">
                        <label><span>Firma türü</span><select id="sg-supplier-kind"><option value="corporate">Kurumsal</option><option value="individual">Bireysel</option></select></label>
                        <label class="sg-field-wide"><span>Firma / Tedarikçi adı *</span><input id="sg-supplier-company" required maxlength="190"></label>
                        <label><span>Yetkili kişi</span><input id="sg-supplier-contact" maxlength="190"></label>
                        <label><span>Telefon</span><input id="sg-supplier-phone" type="tel" maxlength="17" placeholder="0 (555) 555 55 55" inputmode="numeric" autocomplete="tel"></label>
                        <label><span>E-posta</span><input id="sg-supplier-email" type="email" maxlength="190"></label>
                        <label><span>Vergi dairesi</span><input id="sg-supplier-tax-office" maxlength="120"></label>
                        <label><span>Vergi / T.C. kimlik no</span><input id="sg-supplier-tax-number" inputmode="numeric" maxlength="32"></label>
                        <label><span>Durum</span><select id="sg-supplier-state"><option value="active">Aktif</option><option value="passive">Pasif</option></select></label>
                        <label class="sg-field-wide"><span>Adres</span><textarea id="sg-supplier-address" rows="3"></textarea></label>
                        <label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-supplier-description" rows="2"></textarea></label>
                        <div class="sg-form-actions"><p id="sg-supplier-message" role="status"></p><button type="submit" class="sg-button sg-button-primary" id="sg-supplier-save">Kaydet</button></div>
                    </form>
                </dialog>
            </section>

            <section class="sg-section-view" data-section-view="personnel" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">ORGANİZASYON / PERSONEL</p><h1>Personel Takibi</h1><p>Personel iletişim, görev ve çalışma bilgilerini tek yerden yönetin.</p></div><button type="button" class="sg-button sg-button-primary" id="sg-personnel-add" data-permission="personnel.manage">+ Yeni Personel</button></div>
                <div class="sg-customer-toolbar"><label class="sg-search-field"><span>Personel ara</span><input type="search" id="sg-personnel-search" placeholder="Ad, telefon, e-posta, görev veya departman"></label><label class="sg-filter-field"><span>Durum</span><select id="sg-personnel-status"><option value="">Tüm kayıtlar</option><option value="active">Aktif</option><option value="passive">Pasif</option></select></label><span class="sg-customer-count" id="sg-personnel-count">Kayıtlar yükleniyor…</span></div>
                <div class="sg-panel sg-customer-list-panel"><div class="sg-table-wrap"><table class="sg-dashboard-table sg-customer-table"><thead><tr><th>Ad Soyad</th><th>Departman</th><th>Görev / Pozisyon</th><th>Telefon</th><th>E-posta</th><th>İşe Giriş</th><th>Durum</th><th>İşlem</th></tr></thead><tbody id="sg-personnel-rows"><tr><td colspan="8" class="sg-table-empty">Personel kayıtları yükleniyor…</td></tr></tbody></table></div></div>
                <dialog class="sg-modal sg-customer-editor" id="sg-personnel-editor">
                    <div class="sg-panel-heading"><div><p class="sg-card-kicker">PERSONEL KARTI</p><h2 id="sg-personnel-editor-title">Yeni Personel</h2></div><button type="button" class="sg-button sg-button-light" id="sg-personnel-cancel">Kapat</button></div>
                    <form id="sg-personnel-form" class="sg-customer-form">
                        <input type="hidden" id="sg-personnel-id" value=""><input type="hidden" id="sg-personnel-user-id" value="">
                        <label class="sg-field-wide"><span>Ad soyad *</span><input id="sg-personnel-name" required maxlength="190"></label>
                        <label><span>Telefon</span><input id="sg-personnel-phone" type="tel" maxlength="17" placeholder="0 (555) 555 55 55" inputmode="numeric"></label>
                        <label><span>E-posta</span><input id="sg-personnel-email" type="email" maxlength="190"></label>
                        <label><span>Departman</span><input id="sg-personnel-department" maxlength="120" placeholder="Örn. Operasyon"></label>
                        <label><span>Görev / Pozisyon</span><input id="sg-personnel-title" maxlength="120" placeholder="Örn. Saha personeli"></label>
                        <label><span>İşe giriş tarihi</span><input id="sg-personnel-date" type="date"></label>
                        <label><span>Çalışma durumu</span><select id="sg-personnel-state"><option value="active">Aktif</option><option value="passive">Pasif</option></select></label>
                        <label class="sg-field-wide"><span>Not / Açıklama</span><textarea id="sg-personnel-description" rows="3" maxlength="2000"></textarea></label>
                        <div class="sg-form-actions"><p id="sg-personnel-message" role="status"></p><button type="submit" class="sg-button sg-button-primary" id="sg-personnel-save">Kaydet</button></div>
                    </form>
                </dialog>
                <p class="sg-footnote">Pasif personel kayıtları silinmez; geçmiş iş atamalarının bağlantıları korunur.</p>
            </section>

            <section class="sg-section-view" data-section-view="income-expense" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">FİNANS / GELİR GİDER</p><h1>Gelir ve Giderler</h1><p>Kaydettiğiniz gelir ve giderler seçilen kasa veya banka hesabına otomatik işlenir.</p></div></div>
                <div class="sg-dashboard-grid"><article class="sg-dashboard-card"><span class="sg-dashboard-label">Toplam Gelir</span><strong id="sg-ie-income-total">₺0,00</strong><small>kayıtlı gelirler</small></article><article class="sg-dashboard-card"><span class="sg-dashboard-label">Toplam Gider</span><strong id="sg-ie-expense-total">₺0,00</strong><small>kayıtlı giderler</small></article><article class="sg-dashboard-card"><span class="sg-dashboard-label">Net Durum</span><strong id="sg-ie-net-total">₺0,00</strong><small>gelir eksi gider</small></article></div>
                <div class="sg-panel sg-cash-transfer-panel"><div class="sg-panel-heading"><div><p class="sg-card-kicker">YENİ FİNANS KAYDI</p><h2>Gelir / Gider Ekle</h2></div></div>
                    <form id="sg-ie-form" class="sg-customer-form"><label><span>Kayıt türü *</span><select id="sg-ie-type" required><option value="income">Gelir</option><option value="expense">Gider</option></select></label><label><span>Kategori *</span><select id="sg-ie-category" required></select></label><label><span>Kasa / Banka *</span><select id="sg-ie-cash" required></select></label><label><span>Tutar (₺) *</span><input id="sg-ie-amount" inputmode="decimal" required placeholder="0.00"></label><label><span>İşlem tarihi *</span><input id="sg-ie-date" type="date" required></label><label><span>Belge numarası</span><input id="sg-ie-document" maxlength="80"></label><label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-ie-description" rows="2" maxlength="1000"></textarea></label><div class="sg-form-actions sg-field-wide"><p id="sg-ie-message" role="status"></p><button type="submit" class="sg-button sg-button-primary" data-permission="income_expense.create">Kaydı Oluştur</button></div></form>
                </div>
                <div class="sg-panel sg-customer-list-panel"><div class="sg-panel-heading"><div><p class="sg-card-kicker">HAREKET LİSTESİ</p><h2>Son Gelir ve Giderler</h2></div><select id="sg-ie-filter" aria-label="Kayıt türü"><option value="">Tümü</option><option value="income">Gelirler</option><option value="expense">Giderler</option></select></div><div class="sg-table-wrap"><table class="sg-dashboard-table"><thead><tr><th>Tarih</th><th>Tür</th><th>Kategori</th><th>Kasa / Banka</th><th>Açıklama</th><th>Tutar</th><th>Fiş No</th></tr></thead><tbody id="sg-ie-rows"><tr><td colspan="7" class="sg-table-empty">Kayıtlar yükleniyor…</td></tr></tbody></table></div></div>
            </section>

            <section class="sg-section-view" data-section-view="jobs" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">OPERASYON / PLANLAMA</p><h1>İş Takibi</h1><p>Görevleri planlayın, ekiplere atayın ve ilerlemeyi tek ekrandan izleyin.</p></div><button type="button" class="sg-button sg-button-primary" id="sg-job-add" data-permission="jobs.create">+ Yeni Görev</button></div>
                <div class="sg-job-metrics"><article><span>Açık görev</span><strong id="sg-job-open-count">—</strong></article><article><span>Geciken</span><strong id="sg-job-late-count">—</strong></article><article><span>Devam eden</span><strong id="sg-job-progress-count">—</strong></article><article><span>Tamamlanan</span><strong id="sg-job-done-count">—</strong></article></div>
                <div class="sg-job-toolbar"><label class="sg-search-field"><span>Görev ara</span><input id="sg-job-search" type="search" placeholder="Görev no veya başlık"></label><label class="sg-filter-field"><span>Durum</span><select id="sg-job-status-filter"><option value="">Tüm durumlar</option><option value="new">Yeni</option><option value="assigned">Atandı</option><option value="in_progress">Devam ediyor</option><option value="on_hold">Beklemede</option><option value="completed">Tamamlandı</option><option value="cancelled">İptal</option></select></label><div class="sg-job-view-switch"><button type="button" class="is-active" data-job-view="list">Liste</button><button type="button" data-job-view="board">Pano</button><button type="button" data-job-view="calendar">Takvim</button></div></div>
                <div class="sg-panel sg-job-list-panel"><div class="sg-table-wrap"><table class="sg-dashboard-table sg-job-table"><thead><tr><th>Görev</th><th>Müşteri</th><th>Sorumlu</th><th>Öncelik</th><th>Durum</th><th>Termin</th><th></th></tr></thead><tbody id="sg-job-rows"><tr><td colspan="7" class="sg-table-empty">Görevler yükleniyor…</td></tr></tbody></table></div></div>
                <div class="sg-job-board" id="sg-job-board" hidden></div><div class="sg-panel sg-job-calendar-panel" id="sg-job-calendar" hidden><div class="sg-calendar-head"><button type="button" class="sg-button sg-button-light" id="sg-calendar-prev">‹</button><h2 id="sg-calendar-title">Takvim</h2><button type="button" class="sg-button sg-button-light" id="sg-calendar-next">›</button></div><div class="sg-calendar-grid" id="sg-calendar-grid"></div><div class="sg-calendar-holiday-list" id="sg-job-holiday-list" aria-live="polite"></div></div>
                <dialog class="sg-modal sg-job-dialog" id="sg-job-dialog"><div class="sg-panel-heading"><div><p class="sg-card-kicker">GÖREV KARTI</p><h2 id="sg-job-dialog-title">Yeni Görev</h2></div><button type="button" class="sg-button sg-button-light" id="sg-job-close">Kapat</button></div>
                    <form id="sg-job-form" class="sg-customer-form"><input type="hidden" id="sg-job-id"><label class="sg-field-wide"><span>Görev başlığı *</span><input id="sg-job-title" required maxlength="190"></label><label><span>Başlangıç tarihi</span><input id="sg-job-start" type="date"></label><label><span>Termin tarihi</span><input id="sg-job-due" type="date"></label><label><span>Sorumlu personel</span><select id="sg-job-person"><option value="">Atama yok</option></select></label><label><span>Müşteri / Proje</span><select id="sg-job-customer"><option value="">Bağlantı yok</option></select></label><label><span>Öncelik</span><select id="sg-job-priority"><option value="low">Düşük</option><option value="normal" selected>Normal</option><option value="high">Yüksek</option><option value="urgent">Acil</option></select></label><label><span>Durum</span><select id="sg-job-status"><option value="new">Yeni</option><option value="assigned">Atandı</option><option value="in_progress">Devam ediyor</option><option value="on_hold">Beklemede</option><option value="completed">Tamamlandı</option><option value="cancelled">İptal</option></select></label><label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-job-description" rows="3"></textarea></label><div class="sg-form-actions"><p id="sg-job-message" role="status"></p><button type="submit" class="sg-button sg-button-primary">Kaydet</button></div></form>
                    <div id="sg-job-detail" hidden><h3 class="sg-account-history-title">Notlar ve aktivite</h3><div id="sg-job-notes"></div><form id="sg-job-note-form" class="sg-job-note-form"><textarea id="sg-job-note" rows="2" placeholder="Göreve not ekleyin…"></textarea><button type="submit" class="sg-button sg-button-primary">Not ekle</button></form><div id="sg-job-history" class="sg-job-history"></div></div>
                </dialog>
            </section>
            <section class="sg-section-view" data-section-view="settings" hidden>
                <div class="sg-module-heading"><div><p class="sg-eyebrow">YÖNETİM / AYARLAR</p><h1>Uygulama Ayarları</h1><p>Müşteri kartlarında kullanılacak türleri buradan tanımlayın.</p></div></div>
                <div class="sg-panel sg-settings-panel">
                    <div class="sg-panel-heading"><div><p class="sg-card-kicker">MÜŞTERİ YÖNETİMİ</p><h2>Müşteri Türleri</h2><p class="sg-panel-subtitle">Tür adlarını işletmenizin ihtiyaçlarına göre siz belirleyin. Henüz varsayılan tür eklenmemiştir.</p></div></div>
                    <form id="sg-customer-type-form" class="sg-customer-type-form"><label><span>Yeni müşteri türü</span><input id="sg-customer-type-name" maxlength="60" required placeholder="Örn. Bayi, VIP, Proje müşterisi"></label><button type="submit" class="sg-button sg-button-primary" id="sg-customer-type-save">Tür Ekle</button></form>
                    <p id="sg-customer-type-message" class="sg-settings-message" role="status"></p>
                    <div class="sg-table-wrap"><table class="sg-dashboard-table"><thead><tr><th>Müşteri türü</th><th>İşlem</th></tr></thead><tbody id="sg-customer-type-rows"><tr><td colspan="2" class="sg-table-empty">Türler yükleniyor…</td></tr></tbody></table></div>
                    <p class="sg-footnote">Kullanımda olan müşteri türleri silinemez. Önce ilgili müşteri kartlarının türünü değiştirmeniz gerekir.</p>
                </div>
            </section>
        </main>
    </div>
</div>
