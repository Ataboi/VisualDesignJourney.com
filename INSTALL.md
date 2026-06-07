# Visual Design Journey — Kurulum Rehberi

## Gece kurulum için 5 adım

### 1. Dosyaları yükle
Tüm proje klasörünü subdomain'in root'una FTP/SFTP ile yükle.
- FileZilla veya hosting panelinin File Manager'ı çalışır
- `.htaccess` dosyasının yüklendiğinden emin ol (gizli dosya olduğu için bazı FTP istemcileri atlar)

### 2. Database oluştur
cPanel / hosting panelinde:
- MySQL Databases → "Create Database" (örn. `kullanici_visualdesign`)
- "Create User" → şifre belirle
- "Add User to Database" → All Privileges

### 3. Installer'ı aç
Tarayıcıda: `https://subdomain.yourdomain.com/install.php`
- Requirements check'te her şey yeşil olmalı
- DB bilgilerini gir, Site URL'ini yaz
- "Load demo data" checkbox'ı işaretli bırak
- Install Now'a tıkla

### 4. install.php'yi sil
Kurulum bittikten sonra FTP'den `install.php`'yi **hemen sil**.

### 5. Test et
- Ana sayfa: `/index.php`
- Giriş: `studio@gmail.com` / `password123`
- Board oluştur, profil gör

---

## Sorun giderme

**"config/ writable" FAIL görünüyorsa:**
```
chmod 755 config/
```

**Upload çalışmıyorsa:**
```
chmod 755 uploads/
chmod 755 uploads/boards/
chmod 755 uploads/avatars/
```

**404 sayfaları çalışmıyorsa:**
Apache'de `AllowOverride All` ayarlı olmalı. cPanel hosting'lerde genellikle otomatik aktif.

**Nginx kullanıyorsan** `.htaccess` çalışmaz, hosting ekibiyle veya aşağıdaki blokla:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~* \.(php)$ {
    if ($request_uri ~* "^/uploads/") { return 403; }
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
error_page 404 /404.php;
```

---

## Demo hesapları
Tüm seed kullanıcıları için şifre: `password123`

| Email | Kullanıcı |
|-------|-----------|
| studio@gmail.com | studio_void |
| elena@outlook.com | elena_design |
| zen@proton.me | digital_zen |
| neon@gmail.com | neon_archive |
