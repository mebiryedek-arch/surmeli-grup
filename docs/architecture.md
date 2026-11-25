# Sürmeli Grup – Teknik Mimari

## Temel ilkeler
- İşletme verileri WordPress post/meta tablolarında tutulmaz; özel InnoDB tabloları kullanılır.
- Tüm finansal tutarlar DECIMAL(18,2) ve TRY olarak saklanır.
- Finansal kayıtlar tek bir servis katmanından ve veritabanı transaction sınırları içinde oluşturulur.
- Finansal kayıt silinmez; ters kayıt/iptal mekanizmasıyla düzeltilir.
- REST uç noktalarında kimlik doğrulama, capability/özel izin kontrolü, nonce ve alan doğrulaması sunucu tarafında uygulanır.
- PWA özel müşteri ve finansal verileri cache etmez.

## Kimlik ve yetkilendirme (0.3.0)
- Kimlik doğrulama WordPress oturumu üzerinden yapılır; ayrı parola deposu oluşturulmaz.
- `sg_manager` ve `sg_personnel` WordPress rolleri kullanılır. WordPress yöneticileri `manage_options` yetkisiyle uygulama izinlerini aşabilir.
- İzin anahtarları `sg_permissions`, rol eşleştirmeleri `sg_role_permissions` tablolarında tutulur. Uygulama izin denetimi sunucu tarafındadır.
- Personel rolü için iş listesi kapsamı `assigned_personnel_id` üzerinden sınırlandırılır. Personel kartı, WordPress `user_id` ile eşleştirilmelidir.
- REST uç noktaları WordPress REST nonce/oturum doğrulamasına ve `app.access` iznine tabidir.

## Finansal model
`sg_journal_entries` fiş başlığıdır. `sg_journal_lines` borç/alacak satırlarıdır. Her fişte borç toplamı alacak toplamına eşit olmalıdır. `sg_cash_transactions` kasa/banka alt defteridir ve journal fişine referans verir. Cari ve nakit bakiyeleri bu kayıtların kontrollü toplamlarından türetilir; çift kayıt riski olan bağımsız toplam alanları kaynak kabul edilmez.

## Uygulama rotası
`/musteritakip/` eklentinin sabit rewrite rotasıdır. WordPress sayfası veya shortcode kullanılmaz; eklenti portalı bağımsız HTML kabuğu olarak sunar.

## Geliştirme sırası
1. Mimari ve iskelet (bu sürüm)
2. Veritabanı ve migration
3. Kimlik doğrulama ve izinler
4. Uygulama kabuğu ve REST altyapısı
5. Dashboard
6. Müşteri ve tedarikçi
7. Cari hesap ve finansal motor
8. Kasa/banka
9. Gelir/gider
10. Personel ve görev
11. Raporlama
12. PWA
13. Güvenlik, performans ve kabul testleri


## Sabit uygulama rotası ve arayüz (0.4.0)
- `/musteritakip/` rotası eklenti rewrite kuralıyla doğrudan uygulama kabuğunu açar; WordPress sayfası/shortcode kurulumu zorunlu değildir.
- Rewrite kuralları `sg_rewrite_version` seçeneğiyle sürüm kontrollü yenilenir.
- Arayüz kullanıcı ve izin bilgilerini `sg/v1/me` ve `sg/v1/permissions` REST uç noktalarından alır.
- Menü görünürlüğü izin listesine göre düzenlenir; güvenlik kararı REST/PHP tarafındaki izin kontrolüne aittir.


## 0.5.0 geçiş notu
0.5.0 sürümünde kısa süreliğine sayfa/shortcode yaklaşımı denenmişti. Bu yaklaşım terk edilmiştir; sonraki sürümlerde portal eklentinin sabit rewrite rotasında çalışır. Eski eklenti tarafından oluşturulduğu kesin olarak doğrulanan sayfa varsa yalnızca o kayıt temizlenir.

## Standalone portal route (0.6.0)
- `^musteritakip/?$` rewrite rule maps to private query var `sg_portal=1`.
- `template_redirect` serves a complete standalone HTML document; no theme page, WordPress Page, or shortcode is involved.
- Login uses `wp_signon`, a dedicated nonce, POST validation, no-cache headers and a fixed same-site redirect.
- The 0.5.0 generated page is removed only when its ID is stored in `sg_portal_page_id` and its type, slug, and exact shortcode content match. Other pages are untouched.
- Database schema remains at `SG_DB_VERSION` 1.1.0.


## Dashboard read model (0.7.1)
- `modules/dashboard/class-dashboard.php` yalnızca okuma amaçlı REST özeti sağlar.
- `/wp-json/sg/v1/dashboard` endpoint'i `dashboard.view` izni gerektirir.
- Müşteri/tedarikçi sayıları aktif taraf kayıtlarından; görev sayıları `sg_jobs` tablosundan hesaplanır.
- Son işler listesi `SG_Permissions::job_list_scope()` ile kullanıcı kapsamına göre sınırlandırılır.
- Finansal toplamlar henüz gösterilmez; cari ve kasa bakiyeleri ancak muhasebe servisleri tamamlandığında eklenecektir.


## Customer management (0.8.0)
- `modules/customers/class-customers.php` müşteri REST uç noktalarını sağlar: listeleme, detay, oluşturma ve güncelleme.
- Müşteri kayıtları mevcut `sg_parties` tablosunda `party_type=customer` ile saklanır; yeni tablo açılmaz.
- `customers.view`, `customers.create` ve `customers.edit` izinleri sunucu tarafında denetlenir.
- Telefon/vergi numarası eşleşmeleri kayıt öncesinde kontrol edilir; finansal veya müşteri kayıtları için silme uç noktası sunulmaz.
- Oluşturma ve güncelleme olayları `sg_audit_logs` tablosuna yazılır.
- Veritabanı sürümü 1.2.0; 003 migration izin kataloğunu mevcut role tablolarına tekrar işler.


## Cari hesap 0.10.0
Cari alt hesaplar `sg_accounts` ile müşteri/tedarikçi kartlarına bağlanır. Her cari hareket, `sg_journal_entries` ve iki `sg_journal_lines` satırı olarak aynı InnoDB transaction içinde kaydedilir. Cari satırı `party_account_id` ile ilişkilendirilir; karşı satır sistemin cari açılış/düzeltme karşı hesabını kullanır. Kayıt uç noktası `Idempotency-Key` zorunlu tutar. Tahsilat/ödeme ve kasa entegrasyonu bu sürümün kapsamı değildir.


## Kasa ve banka yönetimi (0.11.0)
- `modules/cash-bank/class-cash-bank.php` kasa/banka hesap kartlarını, açılış bakiyelerini, tahsilat/ödemeleri, diğer nakit giriş/çıkışlarını ve hesaplar arası transferleri yönetir.
- Tahsilat fişinde kasa/banka borçlandırılır ve müşteri cari hesabı alacaklandırılır; ödeme fişinde tersi uygulanır.
- Transfer tek dengeli yevmiye fişidir; kaynak hesap çıkış ve hedef hesap giriş hareketi alır.
- Kasa/banka bakiyesi `sg_journal_lines.cash_account_id` üzerinden posted fişlerden hesaplanır. `sg_cash_transactions` hareket alt defteridir.
- 0.11.0 yeni tablo eklemez; var olan `sg_cash_accounts`, `sg_cash_transactions`, `sg_journal_entries`, `sg_journal_lines` tablolarını kullanır. DB sürümü 1.4.0'dır.
