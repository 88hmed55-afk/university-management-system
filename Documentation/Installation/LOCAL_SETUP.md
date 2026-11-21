# الإعداد المحلي — UMS Toolchain

**الإصدار:** 1.0 — **التاريخ:** 17 نوفمبر 2026  
**المرجع:** قرار المالك E-001/E-002، ADR-005، `TASK-001`.

---

## 1. القرار المعتمد
| الطبقة | القرار | الحالة |
|---|---|---|
| PHP | ترقية محمولة إلى **8.3.36** (ZTS x64) مع الإبقاء على XAMPP المثبّت كما هو | مُثبّت ومُتحقّق |
| Composer | **2.11.2** محمول على شكل `composer.phar` + `composer.bat` | مُثبّت ومُتحقّق |
| Framework | **Laravel 13** كما في خط الأساس | مُعتمد؛ التثبيت في `TASK-002` |
| قاعدة البيانات | **MySQL 8.0.38** كبديل موثّق لأن 8.4 LTS غير متاح محلياً | مُختار؛ الخدمة تحتاج صلاحية إدارة لتشغيلها |

> لم يُثبّت MySQL 8.4 LTS: غير موجود محلياً، ومثبّت الويندوز يتطلب صلاحيات إدارة غير متاحة في هذه الجلسة. وفقاً لقرار المالك، استُخدم 8.0.38 مع توثيق الفروق.

---

## 2. المسارات الفعلية

| العنصر | المسار |
|---|---|
| جذر الأدوات | `C:\Users\ZBook\AppData\Local\UMS-Toolchain` |
| PHP | `C:\Users\ZBook\AppData\Local\UMS-Toolchain\php-8.3.36\php.exe` |
| php.ini | `C:\Users\ZBook\AppData\Local\UMS-Toolchain\php-8.3.36\php.ini` |
| Composer phar | `C:\Users\ZBook\AppData\Local\UMS-Toolchain\bin\composer.phar` |
| Composer wrapper | `C:\Users\ZBook\AppData\Local\UMS-Toolchain\bin\composer.bat` |
| PHP wrapper | `C:\Users\ZBook\AppData\Local\UMS-Toolchain\bin\php.bat` |
| MySQL 8.0.38 | `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe` |

أُضيف مجلد `bin` إلى **PATH على مستوى المستخدم**. قد تحتاج إعادة فتح الطرفية قبل استخدام `php` و`composer` بالاسم المختصر. في هذه الجلسة استُخدمت المسارات الكاملة.

---

## 3. التحقق المنفّذ فعلياً

| الأمر | النتيجة |
|---|---|
| `php.exe -v` | `PHP 8.3.36 (cli) (built: Oct 26 2026 09:51:04) (ZTS Visual C++ 2019 x64)` |
| `php.exe -m` | `bcmath, curl, dom, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, SimpleXML, xml, xmlreader, xmlwriter, zip` |
| `composer.phar --version` | `Composer version 2.11.2 2026-11-10` / PHP 8.3.36 |
| `composer.bat --version` | نجح — نفس النتيجة |
| `php.bat -v` | نجح — نفس النتيجة |
| `mysql.exe --version` | `Ver 8.0.38 for Win64 on x86_64 (MySQL Community Server - GPL)` |
| `SELECT VERSION();` | **لم يُنفّذ**: خدمة `MYSQL80` متوقفة وبدءها يتطلب صلاحية إدارة |

### تجزئة التحقق (SHA-256)
- PHP: `php-8.3.36-1-Win32-vs16-x64.zip` → `126dd3cc8c19df5085b9420b2bc74ed591de3d98528858c52fc42739b7bba92e`
- Composer: `composer.phar` v2.11.2 → `dd158c0afd41d15fac89e1b4e47b5d42911fe316c3687cd5edac98319b25d028`

---

## 4. قواعد استخدام صارمة

1. **لا تستخدم** `C:\xampp\php\php.exe` (8.2.12) لهذا المشروع. استخدم المسار المحمول 8.3.36 أو `php.bat`.
2. وحدة Apache في XAMPP ما زالت **PHP 8.2.12** (`php8apache2_4.dll`). لذلك يُشغَّل التطبيق أثناء التطوير عبر `php artisan serve` بالـ PHP المحمول نفسه؛ بهذا يتطابق CLI مع خادم التطوير ويُحسم C-005 لهذا المسار.
3. لا يُعدّل XAMPP أو MySQL النظامي في هذه الدورة. أي ترقية نظامية لاحقة تحتاج نافذة صيانة وصلاحية إدارة.
4. يجب تشغيل خدمة `MYSQL80` بواسطة DevOps/مسؤول قبل أول migration. لا تُخزّن كلمة مرور في الملفات.

---

## 5. فروق MySQL 8.0.38 مقابل 8.4 LTS (التزام توثيقي)

- تجنّب ميزات `8.4`-only في migrations.
- اختر Collation عربي صريحاً (`utf8mb4`) واختبره فعلياً قبل اعتماد البيانات العربية؛ لا تفترض تطابق الدلالات مع 8.4.
- لا تعتمد على سلوك `CHECK` أو أنواع زمنية خاصة بإصدار أحدث دون اختبار.
- سجّل أي فرق يظهر أثناء TASK-002 في هذا الملف وفي `CONFLICT_REGISTER.md`.

---

## 6. مشروع Laravel (نتيجة TASK-002 وTASK-003)
| العنصر | القيمة |
|---|---|
| مسار المشروع | `UMS/` داخل مساحة العمل |
| الإطار | Laravel **13.43.0** (skeleton 13.11.0) |
| PHPUnit | **12.5.44** |
| Laravel Boost | **2.10.4** — مُثبّت وفق `AGENTS.md` |
| الواجهة | **Livewire 4.4.8** + **Tailwind CSS 4.3.5** + **Vite 8.3.8** |
| صفحة معاينة التصميم | `http://localhost:8000/design-system` (اسم المسار `design-system.preview`) |
| قاعدة التطوير الحالية | SQLite (`database/database.sqlite`) حتى تشغيل MySQL |
| إضافات مطلوبة أُضيفت | `pdo_sqlite` في `php.ini` المحمول |
| المستودع | Git محلي على `main`، الالتزام `7da07c7`، بلا remote بعد |
| الأوامر الأساسية | `php artisan serve`، `npm run build`، `php artisan test --compact`، `php artisan migrate --force` |

> `.env` موجود محلياً ومستبعد من Git عبر `.gitignore`. لا يوجد remote خاص بعد؛ يلزم إعداده قبل أي تعاون أو CI.

---

## 7. المهمة التالية
`TASK-004` بعد أمر صريح من صاحب المشروع. تشغيل خدمة `MYSQL80` لا يزال مطلوباً قبل أي migration على MySQL.
