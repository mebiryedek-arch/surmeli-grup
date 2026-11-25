# Sürmeli Grup 0.3.0 – Veritabanı Şeması

## Genel ilkeler
- Tablolar `$wpdb->prefix` + `sg_` önekiyle oluşturulur.
- Tüm tablolar InnoDB ve WordPress veritabanı karakter seti/collation değerlerini kullanır.
- Para alanları `DECIMAL(18,2)`; para birimi `TRY` olarak sabittir.
- WordPress post/meta tabloları işletme hareketlerinin deposu değildir.
- Migration kayıtları `sg_schema_migrations` tablosunda tutulur.
- DDL işlemleri MySQL tarafından otomatik commit edilebildiğinden migration sürümü, bütün tablolar doğrulandıktan sonra yazılır.
- İş tablolarında fiziksel FOREIGN KEY yerine indekslenmiş ilişki alanları kullanılır; ilişkisel doğrulama servis katmanında yapılır. Bu tercih dbDelta ve farklı WordPress/MySQL barındırma ortamlarında yükseltme uyumluluğu içindir.

## Tablolar
| Tablo | Amaç |
|---|---|
| `sg_schema_migrations` | Uygulanan migration kayıtları |
| `sg_parties` | Müşteri ve tedarikçi kartları |
| `sg_accounts` | Tarafların cari hesap kartları |
| `sg_ledger_accounts` | Muhasebe hesap planı |
| `sg_journal_entries` | Finansal fiş başlıkları |
| `sg_journal_lines` | Borç/alacak fiş satırları |
| `sg_cash_accounts` | Kasa ve banka hesap kartları |
| `sg_cash_transactions` | Kasa/banka alt defter hareketleri |
| `sg_categories` | Gelir/gider kategori ağacı |
| `sg_personnel` | WordPress kullanıcılarıyla eşleşen personel |
| `sg_jobs` | İş/görev kayıtları |
| `sg_job_history` | Görev durum ve atama geçmişi |
| `sg_job_notes` | Görev notları |
| `sg_financial_documents` | Finansal belge referansları |
| `sg_audit_logs` | Denetim kayıtları |
| `sg_idempotency_keys` | Yinelenen finansal istekleri engelleme altyapısı |
| `sg_permissions` | Uygulama izin sözlüğü |
| `sg_role_permissions` | Rol/izin eşleştirmesi |
| `sg_settings` | Uygulamaya özel ayarlar |

## İlişki ve bakiye prensipleri
- `sg_accounts.party_id` → `sg_parties.id`
- `sg_journal_lines.entry_id` → `sg_journal_entries.id`
- `sg_journal_lines.ledger_account_id` → `sg_ledger_accounts.id`
- `sg_journal_lines.party_account_id` → `sg_accounts.id`
- `sg_journal_lines.cash_account_id` → `sg_cash_accounts.id`
- `sg_cash_accounts.ledger_account_id` → `sg_ledger_accounts.id`
- `sg_cash_transactions.journal_entry_id` → `sg_journal_entries.id`
- `sg_jobs.party_id` → `sg_parties.id`; `assigned_personnel_id` → `sg_personnel.id`
- `sg_job_history.job_id` ve `sg_job_notes.job_id` → `sg_jobs.id`

Cari ve kasa bakiyeleri bağımsız elle güncellenen bakiye alanlarından değil, muhasebeleştirilmiş fiş satırlarından hesaplanır. Bir fişin borç ve alacak toplamlarının eşitliği finansal servis tarafından kayıt öncesinde zorunlu tutulacaktır. Bu aşama tabloları hazırlar; finansal kayıt servisleri sonraki modüllerde uygulanacaktır.

## Yetkilendirme
- `sg_permissions.permission_key` uygulama izin anahtarıdır.
- `sg_role_permissions` rol bazında izinleri saklar; `allowed=1` izin verir, `allowed=0` vermez.
- WordPress kullanıcı rol anahtarları (`sg_manager`, `sg_personnel`) bu eşleştirmelerle kullanılır.
- Personel kayıtları `sg_personnel.user_id` üzerinden WordPress kullanıcılarına bağlanır.

## Migration
`001_create_core_schema` ve `002_seed_permissions` migration'ı mevcut tabloları silmez. `dbDelta()` kullanılarak tablo/kolon/index yapısı uyumlu biçimde oluşturulur veya genişletilir. Migration kaydı ve `sg_db_version` yalnızca bütün beklenen tablolar doğrulandıktan sonra güncellenir.


## Cari alt hesap kullanımı (0.10.0)
- `sg_accounts` müşteri/tedarikçi cari hesabını `sg_parties` kaydına bağlar.
- `sg_ledger_accounts` içinde cari başına alt hesap ve ortak cari açılış/düzeltme karşı hesabı kullanılır.
- Her cari hareket bir `sg_journal_entries` başlığı ve dengeli iki `sg_journal_lines` satırı üretir.
- `sg_journal_lines.party_account_id` yalnızca cari taraf satırında doldurulur.
- Tutarlar `DECIMAL(18,2)` alanlarda tutulur.


## 0.11.0 kasa/banka kullanımı
Mevcut `sg_cash_accounts` tablosu kasa/banka kartlarını, `sg_cash_transactions` tablosu nakit hareketlerini tutar. Muhasebe etkisi `sg_journal_entries` ve `sg_journal_lines` üzerinden yazılır. Kasa bakiyesi, yalnızca `posted` fişlerin `cash_account_id` satırlarında borç-alacak farkı olarak hesaplanır. Bu sürümde yeni tablo oluşturulmaz.
