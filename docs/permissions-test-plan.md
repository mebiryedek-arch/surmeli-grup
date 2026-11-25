# 0.3.0 Yetkilendirme Kabul Testleri

Bu kontroller gerçek WordPress test ortamında yapılmalıdır. Geliştirme ortamında statik PHP kontrolleri tek başına kabul testi sayılmaz.

1. Eklenti güncellendiğinde mevcut `sg_` tabloları ve kayıtları korunmalı; `002_seed_permissions` yalnızca izin sözlüğünü/rol eşleştirmelerini eklemelidir.
2. WordPress yöneticisi uygulamaya girebilmeli ve izin listesindeki tüm anahtarları almalıdır.
3. Sürmeli Grup Yöneticisi rolü `app.access` ve tüm modül izinlerini almalıdır.
4. Sürmeli Grup Personeli rolü `app.access`, `dashboard.view`, `jobs.view`, `jobs.update_status`, `jobs.add_note` izinlerini almalı; müşteri, cari, kasa, personel yönetimi ve rapor izinlerini almamalıdır.
5. Oturum açmamış kullanıcı `/wp-json/sg/v1/me` ve `/wp-json/sg/v1/permissions` uç noktalarından veri alamamalıdır.
6. Uygulama erişim izni olmayan oturum açmış kullanıcı uygulama içeriğini ve bu REST uç noktalarını kullanamamalıdır.
7. Aktif `sg_personnel` kaydı olmayan personel kullanıcısına iş listesi kapsamı `1=0` dönmelidir.
8. Personel kullanıcısı yalnızca `assigned_personnel_id` kendi personel kartı olan işi açabilmelidir; başka personele atanmış iş için erişim reddedilmelidir.
9. `jobs.view_all` izni olan kullanıcı iş listesinin tamamına erişebilmelidir.
10. İzin endpoint'i WordPress REST nonce ile çağrılmalı; eksik/geçersiz nonce durumunda WordPress'in REST güvenlik davranışı uygulanmalıdır.

Not: 0.3.0, sonraki modüllerin kullanacağı yetki servislerini sağlar. Henüz müşteri, cari veya görev CRUD ekranları bu sürümün kapsamı değildir. Her yeni REST route ve kayıt servisi kendi permission callback/servis kontrolünü eklemelidir.
