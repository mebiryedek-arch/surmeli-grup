=== Sürmeli Grup İşletme Yönetimi ===
Contributors: surmeligrup
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.12.0

Sürmeli Grup işletme yönetimi uygulaması. Portal, WordPress sayfası veya shortcode oluşturmadan eklentinin özel URL rotasında çalışır.

== 0.6.0 Güncellemesi ==
- Sabit portal adresi: https://surmeligrup.com.tr/musteritakip/
- Portal eklentiye ait add_rewrite_rule rotası üzerinden açılır.
- WordPress Sayfalar bölümüne portal sayfası eklenmez; shortcode kullanılmaz.
- 0.5.0 tarafından oluşturulan sayfa yalnızca sg_portal_page_id seçeneğiyle işaretlenmiş ve içeriği tam olarak [sg_app] ise otomatik silinir. Başka sayfalara dokunulmaz.
- WordPress kullanıcıları wp_signon ile doğrulanır; nonce, zorunlu alan kontrolü, güvenli yönlendirme ve no-cache başlıkları uygulanır.
- Kullanıcı uygulama erişimi SG_Permissions sunucu tarafı kontrolünden geçer.
- Logo ve mobil uyumlu giriş ekranı eklendi.
- Veritabanı şeması değiştirilmedi; mevcut tablolar ve kayıtlar korunur.

== Kurulum ==
1. Mevcut eklentiyi devre dışı bırakmadan önce dosya ve veritabanı yedeği alın.
2. 0.11.0 ZIP dosyasını mevcut eklentinin üzerine güncelleyin. Eklenti klasörünü veya tabloları silmeyin; mevcut veriler korunur.
3. WordPress > Ayarlar > Kalıcı Bağlantılar ekranında Değişiklikleri Kaydet işlemini bir kez yapın.
4. https://surmeligrup.com.tr/musteritakip/ adresini açın.

== Güvenlik ==
İşletme verileri özel InnoDB tablolarında tutulur. Cari hareketler InnoDB transaction ile dengeli yevmiye fişi olarak kaydedilir. Aynı işlem anahtarıyla çift kayıt engellenir. Finansal kayıtlar doğrudan silinmez. Uninstall veri silmez.

= 0.6.1 =
* Corrected invalid-login rendering so the full standalone login page and styles remain intact after failed credentials.
* Adjusted login logo sizing and spacing to prevent the brand and heading text from crowding.
* Added an Oturumu kapat link at the bottom of the application sidebar.


== 0.7.1 ==
- İlk gerçek Dashboard görünümü eklendi.
- Aktif müşteri ve tedarikçi sayıları, açık/geciken/tamamlanan iş sayıları gerçek tablolardan hesaplanır.
- Son iş listesi kullanıcı kapsamına göre filtrelenir; personel yalnızca kendisine atanmış işleri görür.
- Dashboard REST endpoint'i `dashboard.view` izniyle korunur.
- Finansal bakiye/gelir-gider rakamları henüz hesaplanmaz; finansal servisler tamamlanmadan örnek değer gösterilmez.
- Veritabanı şeması değişmedi; migration gerektirmez.


== 0.11.0 ==
* Kasa ve banka hesap kartları, TRY bakiye görüntüleme ve isteğe bağlı açılış bakiyesi eklendi.
* Müşteri tahsilatı ve tedarikçi ödemesi ilgili cari hesabı ve kasa/banka hesabını aynı dengeli yevmiye fişinde günceller.
* Diğer para giriş/çıkışları gelir/gider muhasebe hesaplarına bağlanır.
* Kasa ve banka hesapları arasında çift taraflı transfer, hesap hareket geçmişi ve hesap bazlı bakiye eklendi.
* Finansal kayıtlar InnoDB transaction, idempotency anahtarı ve denetim kaydıyla korunur.
* Yeni tablo açılmadı; 0.10.0 cari hareketleri ve mevcut veriler korunur. Veritabanı sürümü 1.4.0'a yükseltilir.

== 0.10.0 ==
* Cari hesap ekranı, müşteri/tedarikçi cari listesi, borç-alacak ve açılış hareketleri.
* Hareketler dengeli yevmiye kaydı olarak InnoDB transaction içinde yazılır; idempotency anahtarıyla tekrar kayıt engellenir.
* Cari ekstre ve hareket geçmişi eklendi.

= 0.9.0 ==
- Müşteri ekleme, detay ve düzenleme formları modal pencereye taşındı.
- Müşteri telefon alanında 0 (555) 555 55 55 biçiminde otomatik maske uygulandı; sunucu tarafında telefon doğrulaması eklendi.
- Müşteri kodları M-2026-00001 biçiminde sıralı üretilir; eski müşteri kodları değiştirilmez.
- Tedarikçi listeleme, arama, durum filtresi, ekleme, detay ve düzenleme ekranları eklendi.
- Tedarikçi kodları T-2026-00001 biçiminde sıralı üretilir.
- Tedarikçi kayıtları yeni tablo açılmadan mevcut sg_parties tablosunda party_type=supplier olarak tutulur.
- Müşteri ve tedarikçi REST uç noktaları kendi görüntüleme/oluşturma/düzenleme izinleriyle korunur.
- Veritabanı şeması değişmedi; mevcut kayıtlar korunur.
- Müşteri Yönetimi ekranı eklendi; müşteri listesi, arama ve aktif/pasif filtreleri kullanılabilir.
- Müşteri kartı oluşturma, detay görüntüleme ve düzenleme eklendi.
- Müşteri kodu sunucu tarafında otomatik üretilir.
- Telefon ve vergi numarası çakışmaları kayıt öncesinde kontrol edilir.
- Müşteri oluşturma ve güncelleme işlemleri audit log tablosuna kaydedilir.
- REST uç noktaları customers.view/create/edit izinleriyle korunur; kayıtlar özel sg_parties tablosunda tutulur.
- Veritabanı sürümü 1.2.0 olarak yükseltildi; izin kataloğu için güvenli, tekrarlanabilir migration eklendi. Mevcut müşteri ve finansal kayıtlar silinmez.


== 0.11.3 ==
* Gelir ve gider kayıt ekranı eklendi.
* Gelir/gider kaydıyla aynı işlem içinde muhasebe fişi ve otomatik kasa/banka hareketi oluşturulur.
* Tekrarlı gönderimleri önlemek için işlem anahtarı ve veritabanı transaction koruması eklendi.
* Gelir/gider kategori başlangıç kayıtları ve veritabanı migration'ı eklendi.
* Kasa/banka transfer tarihi alanının hatalı HTML kimliği düzeltildi.
* Modül başlatma hataları oturum doğrulama hatalarından ayrıldı.


== 0.12.0 ==
- İş Takibi modülü eklendi: görev oluşturma, düzenleme, personel ve müşteri bağlantısı, öncelik ve durum yönetimi.
- Görev detaylarında notlar ve işlem geçmişi gösterilir.
- İş Takibi ekranına liste, Kanban pano ve aylık takvim görünümleri eklendi.
- Ana sayfaya gerçek görev kayıtlarından beslenen aylık görev takvimi eklendi.
- Personel kullanıcıları yalnızca kendilerine atanmış görevleri görüntüler ve izinleri ölçüsünde durum/not günceller.
- Mevcut sg_jobs, sg_job_history ve sg_job_notes tabloları kullanılır; finansal ve cari tablolara dokunulmaz.
