<?php
/** Responsive application shell. */
defined( 'ABSPATH' ) || exit;
?>
<div class="sg-app" id="sg-app-root">
    <header class="sg-topbar">
        <a class="sg-brand" href="<?php echo esc_url( SG_APP_URL ); ?>" aria-label="Sürmeli Grup ana ekran">
            <span class="sg-brand-mark" aria-hidden="true">SG</span><span>Sürmeli Grup</span>
        </a>
        <div class="sg-topbar-right"><span class="sg-user-chip" id="sg-user-name">Hesap bilgisi yükleniyor…</span><button class="sg-menu-toggle" type="button" aria-label="Menüyü aç" aria-expanded="false">☰</button></div>
    </header>
    <div class="sg-layout">
        <aside class="sg-sidebar" aria-label="Ana navigasyon">
            <nav>
                <a class="is-active" href="#dashboard" data-section="dashboard">Genel Bakış</a>
                <span class="sg-nav-heading">İşletme</span>
                <a href="#customers" data-section="customers" data-permission="customers.view">Müşteriler</a>
                <a href="#suppliers" data-section="suppliers" data-permission="suppliers.view">Tedarikçiler</a>
                <a href="#accounts" data-section="accounts" data-permission="accounts.view">Cari Hesaplar</a>
                <a href="#cash-bank" data-section="cash-bank" data-permission="cash.view">Kasa ve Banka</a>
                <a href="#income-expense" data-section="income-expense" data-permission="income_expense.view">Gelir ve Gider</a>
                <span class="sg-nav-heading">Organizasyon</span>
                <a href="#personnel" data-section="personnel" data-permission="personnel.view">Personel</a>
                <a href="#jobs" data-section="jobs" data-permission="jobs.view">İş Takip</a>
                <a href="#reports" data-section="reports" data-permission="reports.view">Raporlar</a>
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

            <section class="sg-panel sg-dashboard-calendar-panel"><div class="sg-calendar-head"><div><p class="sg-card-kicker">PLANLAMA</p><h2>Görev Takvimi</h2></div><div class="sg-calendar-head-actions"><button type="button" class="sg-button sg-button-light" id="sg-dashboard-calendar-prev">‹</button><strong id="sg-dashboard-calendar-title">Takvim</strong><button type="button" class="sg-button sg-button-light" id="sg-dashboard-calendar-next">›</button><a href="#jobs" class="sg-button sg-button-light">Tüm görevler</a></div></div><div class="sg-calendar-grid" id="sg-dashboard-calendar-grid"></div></section>
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
                        <label><span>Müşteri türü</span><select id="sg-customer-kind"><option value="corporate">Kurumsal</option><option value="individual">Bireysel</option></select></label>
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
                <div class="sg-panel sg-cash-transfer-panel"><div class="sg-panel-heading"><div><p class="sg-card-kicker">HESAPLAR ARASI</p><h2>Kasa / Banka Transferi</h2></div></div>
                    <form id="sg-cash-transfer-form" class="sg-customer-form"><label><span>Kaynak hesap *</span><select id="sg-transfer-from" required></select></label><label><span>Hedef hesap *</span><select id="sg-transfer-to" required></select></label><label><span>Tutar (₺) *</span><input id="sg-transfer-amount" inputmode="decimal" required placeholder="0.00"></label><label><span>İşlem tarihi *</span><input id="sg-transfer-date" type="date" required></label><label><span>Belge numarası</span><input id="sg-transfer-document" maxlength="80"></label><label><span>Açıklama</span><input id="sg-transfer-description" maxlength="255"></label><div class="sg-form-actions"><p id="sg-transfer-message" role="status"></p><button class="sg-button sg-button-primary" type="submit" data-permission="cash.transfer">Transferi Kaydet</button></div></form>
                </div>
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
                <div class="sg-job-board" id="sg-job-board" hidden></div><div class="sg-panel sg-job-calendar-panel" id="sg-job-calendar" hidden><div class="sg-calendar-head"><button type="button" class="sg-button sg-button-light" id="sg-calendar-prev">‹</button><h2 id="sg-calendar-title">Takvim</h2><button type="button" class="sg-button sg-button-light" id="sg-calendar-next">›</button></div><div class="sg-calendar-grid" id="sg-calendar-grid"></div></div>
                <dialog class="sg-modal sg-job-dialog" id="sg-job-dialog"><div class="sg-panel-heading"><div><p class="sg-card-kicker">GÖREV KARTI</p><h2 id="sg-job-dialog-title">Yeni Görev</h2></div><button type="button" class="sg-button sg-button-light" id="sg-job-close">Kapat</button></div>
                    <form id="sg-job-form" class="sg-customer-form"><input type="hidden" id="sg-job-id"><label class="sg-field-wide"><span>Görev başlığı *</span><input id="sg-job-title" required maxlength="190"></label><label><span>Başlangıç tarihi</span><input id="sg-job-start" type="date"></label><label><span>Termin tarihi</span><input id="sg-job-due" type="date"></label><label><span>Sorumlu personel</span><select id="sg-job-person"><option value="">Atama yok</option></select></label><label><span>Müşteri / Proje</span><select id="sg-job-customer"><option value="">Bağlantı yok</option></select></label><label><span>Öncelik</span><select id="sg-job-priority"><option value="low">Düşük</option><option value="normal" selected>Normal</option><option value="high">Yüksek</option><option value="urgent">Acil</option></select></label><label><span>Durum</span><select id="sg-job-status"><option value="new">Yeni</option><option value="assigned">Atandı</option><option value="in_progress">Devam ediyor</option><option value="on_hold">Beklemede</option><option value="completed">Tamamlandı</option><option value="cancelled">İptal</option></select></label><label class="sg-field-wide"><span>Açıklama</span><textarea id="sg-job-description" rows="3"></textarea></label><div class="sg-form-actions"><p id="sg-job-message" role="status"></p><button type="submit" class="sg-button sg-button-primary">Kaydet</button></div></form>
                    <div id="sg-job-detail" hidden><h3 class="sg-account-history-title">Notlar ve aktivite</h3><div id="sg-job-notes"></div><form id="sg-job-note-form" class="sg-job-note-form"><textarea id="sg-job-note" rows="2" placeholder="Göreve not ekleyin…"></textarea><button type="submit" class="sg-button sg-button-primary">Not ekle</button></form><div id="sg-job-history" class="sg-job-history"></div></div>
                </dialog>
            </section>
        </main>
    </div>
</div>
