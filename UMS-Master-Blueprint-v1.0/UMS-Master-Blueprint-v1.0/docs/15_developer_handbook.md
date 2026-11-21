# 15. دليل المطور والتسليم

## التسمية
PHP classes `StudlyCase`، methods `camelCase`، جداول وأعمدة `snake_case`، Routes مثل `admissions.applications.index`، Permissions مثل `finance.payment.reverse`. Enum codes ثابتة بالإنجليزية والترجمة في ملفات اللغة. رموز العمل مثل student_no مستقلة عن المفتاح الأساسي.

## قواعد قاعدة البيانات
- Migration لكل تغيير مستقل.
- لا تعدل قاعدة الإنتاج يدوياً في phpMyAdmin؛ المخطط في migrations.
- راجع تأثير migration على الفهارس والقفل والجداول الكبيرة.
- استخدم FK/unique/index؛ لا cascade delete على بيانات مالية/أكاديمية معتمدة.
- التحقق في DB ضروري للتفرد والتزامن بجانب Laravel validation.

## قواعد Laravel
- Controllers رفيعة؛ لا تضع منطق الأعمال متعدد الخطوات في Blade أو Livewire component.
- كل إدخال يمر عبر Form Request/Validator.
- كل مورد يمر عبر Policy ونطاق استعلام.
- كل عملية حساسة لها transaction وidempotency عند إعادة المحاولة.
- Jobs قابلة لإعادة المحاولة دون مضاعفة الأثر.
- لا تستخدم `env()` خارج config.
- لا ترفع `.env` أو أسراراً أو بيانات حقيقية إلى Git.

## مكونات تسليم كل ميزة
متطلب/تصميم، migration، model، validation، policy، action/service، route/UI، tests، ترجمة، تحديث الوثائق، نتيجة اختبار.

## مراجعة Pull Request
- تغطية حالات النجاح والفشل والاستثناء.
- تحقق الصلاحية والنطاق في الخادم.
- منع التكرار والتعارض.
- تدقيق العملية الحساسة.
- معاملة قابلة للاستئناف/إعادة المحاولة بأمان.
- واجهة RTL مفهومة وحالات فراغ وخطأ.
- الاختبارات ناجحة والوثائق/ERD محدثة.

## حزمة التسليم
مصدر Git مع Tag، migrations/seeders، قائمة routes والصلاحيات، وصف العمليات والشاشات، اختبارات، تعليمات تثبيت/ترقية/تراجع، دليل مستخدم وتشغيل، وسجل تغييرات.
