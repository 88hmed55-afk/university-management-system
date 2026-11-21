# 7. المعمارية التقنية وبنية Laravel

## خط الأساس التقني المستهدف
| الطبقة | الاختيار | ملاحظة |
|---|---|---|
| Framework | Laravel 13.x | يتطلب PHP 8.3+ |
| Runtime | PHP 8.3 أو 8.4 مدعوم | افحص الإضافات والتوافق |
| UI | Blade + Livewire 4 | واجهات تفاعلية داخل Laravel دون SPA منفصلة |
| CSS | Tailwind CSS 4 ومكونات Blade | دعم RTL وتباين واضح |
| Database | MySQL 8.4 LTS مفضل | استقرار طويل الأمد |
| Local | XAMPP للتطوير فقط | شرط تطابق PHP وMySQL |
| Build | Composer + Node/NPM + Vite | ثبّت إصدارات lock files |
| Git | GitHub/GitLab أو مستودع خاص | مراجعة PR وCI |
| Tests | PHPUnit أو Pest المتوافق + Feature/E2E | اختر مساراً أساسياً واحداً |

الإصدارات تتحقق عند إنشاء التطبيق. إذا كان PHP في XAMPP أقل من 8.3، فلا تبدأ Laravel 13 حتى تجهز PHP متوافقاً. لا تفترض أن نسخة XAMPP لديك ملائمة قبل الفحص.

## لماذا Modular Monolith؟
النظام في الإصدار الأول يحتاج معاملات متماسكة للقبول والدرجات والرسوم وقاعدة مركزية. Modular Monolith يقلل التعقيد التشغيلي ويحافظ على حدود واضحة. لا ننتقل إلى Microservices إلا عند وجود حاجة مستقلة مقاسة.

## الوحدات البرمجية
Core، Identity، Organization، People، HR، Admissions، Students، Academics، Registration، Timetabling، Attendance، Assessment، Finance، Documents، Workflow، Notifications، Postgraduate، Library، Reporting، Audit، Integrations.

## هيكل مجلدات مقترح
```text
app/Modules/{Admissions,Academics,Students,Finance,Organization,Identity}/
  Actions/ DTOs/ Enums/ Events/ Jobs/ Models/ Policies/ Queries/ Services/ Http/
app/Shared/{Audit,Documents,Exceptions,Support}/
resources/views/{modules,components,layouts,lang}/
database/{migrations,seeders,factories}/
tests/{Unit,Feature,Browser}/
docs/
```
كل وحدة تملك قواعدها؛ لا تكتب وحدة مباشرة في جداول أخرى. استخدم Actions/Services وعقود تكامل واضحة.

## قواعد الكود
- Controllers رفيعة؛ Form Requests للتحقق.
- Policies/Gates للتحكم بالوصول ونطاق السجل.
- Actions/Services للعمليات متعددة الخطوات.
- DB Transactions للعمليات الذرية.
- Events/Listeners للإشعارات والآثار الجانبية لا لإخفاء منطق أساسي.
- Jobs للعمليات الثقيلة؛ يجب أن تكون قابلة لإعادة المحاولة بلا مضاعفة.
- Queue قاعدة بيانات للتطوير، وRedis أو backend آخر في الإنتاج إذا برره الحمل.
- API فقط للتكاملات والبوابات التي تحتاجه؛ لا تنشئ API لكل صفحة.
- Migrations هي مصدر مخطط قاعدة البيانات؛ لا تعدل الإنتاج يدوياً من phpMyAdmin.

## عمليات حساسة
**تحويل متقدم إلى طالب:** التحقق من التكرار، إنشاء/ربط الشخص والطالب والبرنامج والخطة والرقم الجامعي في معاملة قابلة لإعادة المحاولة بأمان.

**التحصيل:** حركة دفع وإيصال وتخصيص داخل transaction؛ مرجع الدفع الخارجي idempotent؛ فشل طباعة الإيصال لا يلغي القبض.

**نشر النتائج:** التأكد من اكتمال المراجعة، إنشاء نسخة معتمدة/منشورة وإشعار، وعدم تغيير نتيجة منشورة صامتاً.

## إعداد Windows/XAMPP
1. شغّل MySQL فقط من XAMPP إذا ستستخدمه كقاعدة محلية.
2. افحص `php -v` و`where.exe php` وComposer وNode/NPM.
3. نفذ `SELECT VERSION();` لمعرفة MySQL.
4. تأكد أن PHP CLI هو نفس إصدار PHP المستخدم لتشغيل Laravel.
5. أنشئ قاعدة بيانات فارغة ومستخدم تطبيق مخصصاً؛ لا تستخدم root بلا كلمة مرور إلا في مختبر مؤقت.
6. أنشئ مشروع Laravel واختر Livewire starter kit؛ أنشئ `.env` ومفتاح التطبيق ثم نفذ migrations والاختبارات.
7. استخدم `php artisan serve` و`npm run dev` محلياً؛ لا يلزم نقل المشروع إلى `htdocs` مع خادم Laravel المحلي.
8. بيانات التطوير اصطناعية فقط.

## Git وCI
- `main` للإصدارات المعتمدة؛ feature branch لكل مهمة؛ Pull Request ومراجعة.
- امنع رفع `.env` والأسرار والنسخ الاحتياطية وبيانات الطلاب.
- CI: تثبيت dependencies من lock files، تنسيق الكود، اختبارات، build للواجهة.
- migrations وseeders وبيانات تجريبية موثقة.

## المصادر الرسمية
- Laravel 13: https://laravel.com/docs/13.x/releases
- Starter Kits: https://laravel.com/docs/13.x/starter-kits
- Deployment: https://laravel.com/docs/13.x/deployment
- Queues: https://laravel.com/docs/13.x/queues
- MySQL 8.4 LTS: https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html
