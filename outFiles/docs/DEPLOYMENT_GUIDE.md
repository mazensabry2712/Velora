# دليل رفع المشروع على Hostinger

## الملفات التي لا يجب رفعها (موجودة في .gitignore):

### 1. مجلدات التبعيات
- `/node_modules` - سيتم تثبيتها على السيرفر
- `/vendor` - سيتم تثبيتها على السيرفر عبر `composer install`

### 2. ملفات البيئة والإعدادات
- `.env` - لا ترفع أبداً (تحتوي على بيانات حساسة)
- `.env.backup`
- `/auth.json`

### 3. ملفات التطوير والاختبار
- `/out` - مجلد يحتوي على ملفات الاختبار
- `test_*.php`
- `check_*.php`
- `fix_*.php`
- `cookies.txt`

### 4. ملفات IDE والمحررات
- `/.vscode`
- `/.idea`
- `/.fleet`

### 5. مجلدات التخزين المؤقت
- `/bootstrap/cache/*`
- `/storage/logs/*`
- `/storage/framework/cache/*`
- `/storage/framework/sessions/*`
- `/storage/framework/views/*`

### 6. Build files
- `/public/build` - سيتم إنشاؤها على السيرفر عبر `npm run build`
- `/public/hot`

## خطوات الرفع على Hostinger:

### 1. تحضير الملفات محلياً:
```bash
# تأكد من أن كل شيء محدث
composer install --optimize-autoloader --no-dev
npm run build
```

### 2. ضغط الملفات:
ضغط كل محتويات المشروع ماعدا الملفات الموجودة في `.gitignore`

### 3. رفع الملفات على Hostinger:
- ارفع الملفات داخل مجلد `public_html` أو `domains/yourdomain.com/public_html`
- محتويات مجلد `public` يجب أن تكون في جذر `public_html`
- باقي الملفات (app, config, routes, إلخ) في مجلد أعلى من public_html

### 4. على السيرفر، قم بـ:

```bash
# تثبيت التبعيات
composer install --optimize-autoloader --no-dev

# إنشاء ملف .env
cp .env.example .env
php artisan key:generate

# إعداد قاعدة البيانات في .env
# DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# تشغيل المايجريشن
php artisan migrate --force

# ضبط الصلاحيات
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage
php artisan storage:link
```

### 5. إعدادات .htaccess (إذا لزم الأمر):
تأكد من وجود `.htaccess` في مجلد public مع المحتوى التالي:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## ملاحظات مهمة:

1. **لا ترفع ملف .env أبداً** - أنشئ واحد جديد على السيرفر
2. **قاعدة البيانات**: أنشئ قاعدة بيانات جديدة على Hostinger وحدث بيانات الاتصال في .env
3. **APP_ENV**: اجعلها `production` في ملف .env على السيرفر
4. **APP_DEBUG**: اجعلها `false` في الإنتاج
5. **Permissions**: تأكد من صلاحيات مجلدات storage و bootstrap/cache

## هيكل المجلدات على Hostinger:

```
/home/username/
├── domains/
│   └── yourdomain.com/
│       ├── public_html/  (محتويات مجلد public)
│       │   ├── index.php
│       │   ├── .htaccess
│       │   └── build/
│       └── laravel/  (باقي ملفات Laravel)
│           ├── app/
│           ├── config/
│           ├── routes/
│           ├── storage/
│           └── ...
```

أو ضع كل شيء في public_html وعدل index.php لتوجيه المسارات.
