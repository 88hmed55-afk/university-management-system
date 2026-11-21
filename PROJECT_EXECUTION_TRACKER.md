# PROJECT_EXECUTION_TRACKER — UMS

**الإصدار:** 1.0  
**تاريخ التحديث:** 17 نوفمبر 2026  
**المرحلة الحالية:** Gate 0 → 3 — `TASK-001` و`TASK-002` و`TASK-003` مكتملان؛ بوابة اعتماد التصميم اكتملت (E-004)؛ بانتظار أمر المتابعة  
**المبدأ الحاكم:** مهمة صغيرة واحدة (أو مجموعة غير قابلة للفصل منطقياً) في كل دورة عمل، ثم مراجعة واختبار وتوثيق وتوقف.

---

## 1. دليل الاستخدام

### 1.1 حالات المهام
| الحالة | المعنى |
|---|---|
| `NOT_STARTED` | لم تبدأ؛ المتطلبات السابقة غير مكتملة أو لم يُشرع فيها. |
| `IN_PROGRESS` | جارية في دورة العمل الحالية. |
| `BLOCKED` | متوقفة بسبب قرار جامعي أو بيئة أو اعتماد خارجي. |
| `IN_REVIEW` | أنجزت الكود/المخرجات وتنتظر المراجعة أو الاعتماد. |
| `COMPLETED` | أنجزت واجتازت معايير القبول وُحُدّثت الوثائق. |

### 1.2 قاعدة الانتقال
لا أنتقل من مهمة إلى التالية تلقائياً. الانتقال يتم فقط عند استلام أمر «تابع المهمة التالية» من صاحب المشروع، ويكون للانتقال أولوية على أساس: `BLOCKED` تُحل أولاً، ثم أقدم مهمة `NOT_STARTED` مستوفية المتطلبات السابقة.

### 1.3 معايير القبول الإلزامية لكل مهمة
- [ ] مطابقة المتطلبات الموثقة.
- [ ] سلامة التصميم والتناسق البصري.
- [ ] صحة RTL (إذا كانت المهمة تمس الواجهة).
- [ ] استجابة الواجهة.
- [ ] عمل الأزرار والإجراءات المطلوبة.
- [ ] الصلاحيات من جهة الخادم (لا إخفاء واجهة فقط).
- [ ] سلامة البيانات والقيود.
- [ ] حالات الخطأ والنجاح والفراغ واضحة.
- [ ] الاختبارات المطلوبة نُفّذت فعلياً وسُجّلت نتيجتها.
- [ ] عدم كسر الوظائف السابقة (اختبار انحداري).
- [ ] تحديث الوثائق ذات الصلة.
- [ ] تحديث هذا الملف.

---

## 2. ملخص حالة المرحلة التمهيدية

| المعرّف | البند المطلوب (من أمر البداية) | الحالة | المخرج الرئيسي |
|---|---|---|---|
| PRE-A | قراءة جميع ملفات الحزمة | `COMPLETED` | تقرير الفهم §3 + سجل المصادر المقروءة |
| PRE-B | فحص المجلد والبيئة المحلية | `COMPLETED` | تقرير البيئة §4 + سجل التعارضات C-001/C-002 |
| PRE-C | تقرير فهم شامل | `COMPLETED` | `Documentation/00_PROJECT_UNDERSTANDING_REPORT.md` |
| PRE-D | ملف متابعة التنفيذ | `COMPLETED` | هذا الملف |
| PRE-E | سجل القرارات المعلقة | `COMPLETED` | `Documentation/Decisions/PENDING_DECISIONS_REGISTER.md` |
| PRE-F | التصور المعماري المبدئي | `COMPLETED` | `Documentation/Architecture/ARCHITECTURAL_VISION.md` |
| PRE-G | الهوية البصرية وDesign System مبدئي | `COMPLETED` | `Documentation/UI-UX/DESIGN_SYSTEM.md` + `design-tokens.css` |
| PRE-H | نماذج الواجهات المرجعية (15 بنداً) | `COMPLETED` | `Documentation/UI-UX/design-approval-mockups.html` |
| PRE-I | تحديد أول مهمة تنفيذية صغيرة | `COMPLETED` | `TASK-001` في §6 |

> **ملاحظة صدق (مُحدَّثة بعد TASK-003):** المرحلة التمهيدية A–I كانت وثائق ونماذج فقط. بعدها أُنشئ مشروع Laravel فعلي في `UMS/`، ونُفّذت migrations على SQLite، وثُبّت Design System مع صفحة معاينة Livewire. قاعدة MySQL لم تُنشأ بعد (R-006).

---

## 3. تفاصيل مهام المرحلة التمهيدية

### PRE-A — قراءة حزمة المخطط الرئيسي
- **الوصف:** فحص جميع المجلدات والملفات، قراءة الوثائق كاملة، مراجعة المخططات وقوائم المهام، استخراج القرارات المعتمدة والمعلقة، اكتشاف النواقص والتعارضات.
- **المرحلة:** تمهيدي. **المتطلبات السابقة:** لا شيء.
- **الملفات المتأثرة (مقروءة):** `MASTER_PLAN_AR.md` (1,804 سطر)، `MASTER_PLAN_AR.docx` (84 KB — النسخة المطابقة للقراءة والطباعة، اكتفينا بالنص Markdown المكافئ)، `README_AR.md`، `CHANGELOG.md`، `TECHNICAL_REFERENCES.md`، `docs/01`–`docs/15` (15 فصلاً)، `docs/04_screen_catalog.md` (99 شاشة)، `diagrams/01`–`04` (Mermaid)، `checklists/01`–`02`.
- **الحالة:** `COMPLETED`.
- **الاختبارات:** جرد ملفات فعلي عبر PowerShell؛ مطابقة عناوين MASTER_PLAN_AR مع فصول `docs/`؛ عدّ الشاشات SCR-001…SCR-099.
- **معايير القبول:** ✓ فُحصت كل الملفات ✓ لم يُدعَ قراءة ملف غير مقروء ✓ سُجّل ما لم يُفحص محتواه تفصيلياً (DOCX اكتفى بالنص المكافئ).
- **الملاحظات:** DOCX لم يُفكّ ترميزه مباشرة؛ النسخة Markdown هي المصدر النصي الكامل المعلن في README، وتمت قراءتها كاملة.
- **المهمة التالية:** PRE-B.

### PRE-B — فحص المشروع والبيئة المحلية
- **الوصف:** تحديد أنظمة التشغيل والإصدارات الفعلية قبل أي بناء.
- **المرحلة:** تمهيدي. **المتطلبات السابقة:** PRE-A.
- **الملفات المتأثرة:** تقرير الفهم، سجل التعارضات.
- **الحالة:** `COMPLETED`.
- **الاختبارات المنفذة:** `php -v`، `php -m`، `composer --version`، `node -v`، `npm -v`، `mysql --version`، `git --version`، فحص `C:\xampp` و`C:\Program Files\MySQL`، تحقق من مرجع Laravel الرسمي عبر الويب.
- **النتيجة:** PHP 8.2.12 (ZTS) في XAMPP، MariaDB 10.4.32، MySQL 8.0.38 منفصل، Composer غير مثبّت، Node 26.4.0، NPM 11.17.0، Git 2.55.0. **لا يطابق خط الأساس المستهدف.**
- **معايير القبول:** ✓ فُحصت الإصدارات الفعلية ✓ لم يُفترض توافق ✓ سُجّل التعارض.
- **المخاطر:** التعارض C-001 يعمل على Gate 0؛ لا يمكن إنشاء مشروع Laravel 13 قبل حسمه.
- **المهمة التالية:** PRE-C.

### PRE-C — تقرير الفهم الشامل
- **الوصف:** توثيق الرؤية، النطاق، الحوكمة، الوحدات، سير العمل، الكيانات، الشاشات، القيود، والقرارات.
- **الحالة:** `COMPLETED` → `Documentation/00_PROJECT_UNDERSTANDING_REPORT.md`.
- **الاختبارات:** مطابقة المستخرج مع الوثائق المصدرية؛ إبراز السياسات غير المعتمدة دون تحويلها إلى حقائق.
- **معايير القبول:** ✓ لم تُخترع سياسة أكاديمية/مالية ✓ فُصل المعتمد عن المعلق ✓ سُجّلت الافتراضات.

### PRE-D — ملف متابعة التنفيذ
- **الوصف:** إنشاء هذا الملف بجميع الحقول المطلوبة.
- **الحالة:** `COMPLETED`.

### PRE-E — سجل القرارات المعلقة
- **الوصف:** نقل واستكمال P-001…P-024 مع الأثر والمالك ومتطلبات الإغلاق، وإضافة قرارات بيئية جديدة.
- **الحالة:** `COMPLETED` → `Documentation/Decisions/PENDING_DECISIONS_REGISTER.md` و`CONFLICT_REGISTER.md`.

### PRE-F — التصور المعماري المبدئي
- **الوصف:** Modular Monolith بحدود وحدات، طبقات، نطاقات بيانات، وتاريخية.
- **الحالة:** `COMPLETED` → `Documentation/Architecture/ARCHITECTURAL_VISION.md` + `Documentation/Architecture/ADR/ADR_LOG.md`.

### PRE-G — الهوية البصرية وDesign System مبدئي
- **الوصف:** رموز ألوان، طباعة، شبكة، مكونات، RTL، حالات، استجابة، وصول.
- **الحالة:** `COMPLETED` → `Documentation/UI-UX/DESIGN_SYSTEM.md` + `design-tokens.css`.

### PRE-H — نماذج الواجهات المرجعية
- **الوصف:** تنفيذ 15 بنداً من قسم «اعتماد التصميم» كنماذج ثابتة تفاعلية التنقل (بلا ربط Backend).
- **الحالة:** `COMPLETED` → `Documentation/UI-UX/design-approval-mockups.html`.
- **الاختبارات:** فتح الملف في متصفح ومراجعة RTL والتبويبات — **لم تُنفّذ بعد** (مطلوب من صاحب المشروع أثناء Design Approval؛ سُجّل بنداً مفتوحاً).
- **معايير القبول:** ✓ كل البنود الخمسة عشر موجودة ✓ عربي RTL أصلي ✓ لا خط خارجي ✓ لا قالب جاهز منقول.

### PRE-I — تحديد أول مهمة تنفيذية
- **الوصف:** اختيار أصغر مهمة قابلة للبدء بعد اعتماد التصميم.
- **الحالة:** `COMPLETED` → `TASK-001` أدناه (حالتها `BLOCKED` بسبب التعارض البيئي).

---

## 4. خريطة المراحل التنفيذية (مرجعية من الوثيقة 13)

| المرحلة | الاسم | الحالة | ملاحظة |
|---|---|---|---|
| Gate 0 | فحص البيئة والتأسيس | `IN_REVIEW` | Laravel 13 + Livewire 4 + Tailwind 4 جاهزة؛ R-006 يمنع migrations على MySQL؛ R-008 لا remote |
| 1 | تحليل المتطلبات | `IN_REVIEW` | المخطط يغطي التحليل؛ ورش الجامعة لم تُعقد |
| 2 | تصميم البيانات والمعمارية | `IN_REVIEW` | ERD مفاهيمي موجود؛ المنطقي/الفعلي لم يعتمد |
| 3 | النواة والهوية والهيكل | `IN_PROGRESS` | Design System مُثبّت ومعتمد (E-004)؛ النواة والهوية (users/roles/ campuses) لم تُنشأ بعد |
| 4 | الأشخاص وHR الأساسي | `NOT_STARTED` | |
| 5 | القبول والطلاب | `NOT_STARTED` | |
| 6 | الكتالوج الأكاديمي | `NOT_STARTED` | |
| 7 | التسجيل والجداول | `NOT_STARTED` | |
| 8 | الحضور والاختبارات والنتائج | `NOT_STARTED` | |
| 9 | المالية الطلابية | `NOT_STARTED` | |
| 10 | الخدمات | `NOT_STARTED` | |
| 11 | التقارير | `NOT_STARTED` | |
| 12 | QA والتدريب والترحيل | `NOT_STARTED` | |
| 13 | تشغيل تجريبي وإطلاق | `NOT_STARTED` | |

> بوابة «اعتماد التصميم» (قسم 13 من أمر المشروع) تقع بين المرحلة 2 والمرحلة 3؛ لا يُعمّم التصميم قبل اعتماد الأساس البصري.

---

## 5. سجل المخاطر والأخطاء المفتوحة

| المعرّف | الخطر/الخطأ | الأثر | الحالة | المالك |
|---|---|---|---|---|
| C-001 | PHP 8.2.12 < 8.3 المطلوب لـ Laravel 13؛ Composer غير مثبّت | كان يمنع Gate 0 | `RESOLVED` — PHP 8.3.36 + Composer 2.11.2 | صاحب المشروع + DevOps |
| C-002 | MariaDB 10.4.32 مقابل MySQL 8.4 LTS المستهدف، ووجود MySQL 8.0.38 | اختلاف دلالات SQL/المحرك؛ توافق migrations | `RESOLVED` — اختير 8.0.38 موثّقاً؛ انظر R-006 | DevOps + DBA |
| C-003 | تعارض توجيهي: «هوية استثنائية» مقابل «واجهة هادئة بلا بطاقات كثيرة» | خطر الإفراط في الزخرفة | **حُلّ هندسياً** في Design System: تمييز عبر الطباعة والشبكة وتصور البيانات لا عبر التدرجات | UI/UX Director |
| C-004 | غياب 4 لوحات دورية مُسمّاة في كتالوج الـ99 شاشة (مدير حرم، عميد، رئيس قسم، مساحة القبول التشغيلية) | فجوة تتبع بين أمر المشروع والكتالوج | `RESOLVED` — اعتُمد الحل في 17 نوفمبر 2026؛ تُسجَّل SCR-100… في RTM عند إنشائها | Business Analyst |
| R-005 | لم تُفتح نماذج HTML فعلياً في متصفح بعد | اعتماد التصميم غير مكتمل | `CLOSED` — فُتحت النماذج في لوحة المراجعة واعتمد المالك البنود الخمسة عشر | صاحب المشروع |
| R-006 | خدمة `MYSQL80` متوقفة ولا يمكن بدؤها دون صلاحية إدارة | يمنع migrations على MySQL واختبار collation العربي | مفتوح | DevOps / مسؤول النظام |
| R-007 | XAMPP Apache ما زال على PHP 8.2.12 | استخدامه بالخطأ يسبب أخطاء توافق | مُخفَّف — استخدم `artisan serve` بالـ PHP المحمول | المطور |
| R-008 | المستودع محلي فقط ولا يوجد remote خاص | لا نسخة بعيدة ولا PR/CI | مفتوح | صاحب المشروع / DevOps |
| R-009 | Livewire 4 وTailwind 4 لم يُثبّتا بعد | لا واجهات مُحدَّثة قبل اعتماد التصميم | `CLOSED` — Livewire 4.4.8 + Tailwind 4.3.5 + Vite 8.3.8 مُثبّتة ومبنية | فريق التطوير |
| R-010 | تحذيرات `npm audit`: 6 ثغرات عالية في سلسلة أدوات التطوير (`picomatch`/`vite`/`laravel-vite-plugin`) | ReDoS في أدوات البناء المحلية فقط؛ لا تؤثر على وقت تشغيل التطبيق | مفتوح — الإصلاح يتطلب تخفيض Vite إلى 6 (كسر توافق Tailwind 4)؛ يلزم قرار المالك | DevOps |

---

## 6. قائمة المهام القابلة للتنفيذ (Near-term Queue)

### TASK-001 — حسم سلسلة الأدوات المحلية (Toolchain Resolution)
- **الوصف:** اختيار وتثبيت مسار متوافق: إما PHP 8.3+ وComposer مع الإبقاء على Laravel 13، أو تطبيق ADR بتغيير خط الأساس إلى Laravel 12 (يدعم PHP 8.2) مع تثبيت Composer؛ وتحديد محرك قاعدة البيانات المحلي.
- **المرحلة:** Gate 0. **المتطلبات السابقة:** PRE-A…PRE-I + قرار المالك (وصل 17 نوفمبر 2026).
- **الملفات المتأثرة فعلياً:** `Documentation/Architecture/ADR/ADR_LOG.md`، `Documentation/Decisions/PENDING_DECISIONS_REGISTER.md`، `Documentation/Decisions/CONFLICT_REGISTER.md`، `Documentation/Installation/LOCAL_SETUP.md`، `Documentation/Progress-Reports/2026-11-17_TASK-001.md`، هذا الملف.
- **الحالة:** `COMPLETED` (مع R-006 موثّقة).
- **القرار المنفَّذ:** PHP 8.3.36 + Composer 2.11.2 + Laravel 13؛ MySQL 8.0.38 كبديل موثّق لعدم توفر 8.4 LTS محلياً.
- **الاختبارات المنفذة:** `php.exe -v` ✓، `php.exe -m` ✓ (كل الإضافات بما فيها gd)، `composer.phar --version` ✓، `composer.bat --version` ✓، `php.bat -v` ✓، `mysql.exe --version` ✓، `SELECT VERSION();` ⚠ (خدمة متوقفة)، مقارنة CLI/خادم التطوير ✓ عبر `artisan serve` المخطط له.
- **معايير القبول:** ✓ كل إصدار مُثبت ومُوثّق ✓ لا افتراضات ✓ ADR-005 مُسجّل ✓ الفروق موثّقة ⚠ `SELECT VERSION()` مؤجل إلى ما بعد تشغيل الخدمة.
- **الملاحظات:** «أول مهمة تنفيذية صغيرة» المطلوبة في PRE-I. لم يُنشأ مشروع Laravel بعد.
- **المهمة التالية:** TASK-002 — لا تُبدأ إلا بأمر «تابع المهمة التالية» وبعد تشغيل `MYSQL80` بواسطة مسؤول.

### TASK-002 — إنشاء هيكل Laravel والتحقق الصحي
- **الوصف:** إنشاء مشروع Laravel بالإصدار المُعتمد في TASK-001، مستودع Git خاص، `.env` آمن، migration تجريبية واحدة، واختبار Feature بسيط ناجح.
- **المرحلة:** Gate 0. **المتطلبات السابقة:** TASK-001 `COMPLETED`.
- **المخرجات:** مشروع `UMS/` بـ Laravel 13.43.0، Boost 2.10.4، `.env` مستبعد من Git، migration `2026_11_17_185757_create_system_health_checks_table`، اختبار `SystemHealthTest`، مستودع Git محلي بالالتزام `7da07c7`.
- **الاختبارات المنفذة:** `migrate --force` ✓ (4 migrations على SQLite)، Feature ✓ (3 tests / 5 assertions)، كامل الاختبارات ✓ (5 tests / 7 assertions)، Pint ✓، `about` ✓، `route:list` ✓، `git check-ignore .env` ✓.
- **الحالة:** `COMPLETED` (مع R-006 وR-008 وR-009 موثّقة).
- **الملاحظات:** MySQL ما زال متوقفاً؛ التطوير الحالي على SQLite. لا يوجد remote بعد. لم يُثبّت Livewire/Tailwind بعد.
- **المهمة التالية:** TASK-003 — لا تُبدأ إلا بأمر «تابع المهمة التالية» وبعد بوابة اعتماد التصميم.

### TASK-003 — تثبيت Design System في المشروع
- **الوصف:** تحويل `design-tokens.css` ونماذج المكونات إلى Blade/Livewire/Tailwind بعد اعتماد التصميم.
- **المرحلة:** Gate 0 → 3. **المتطلبات السابقة:** TASK-002 `COMPLETED` + اعتماد Design Approval (تم 17 نوفمبر 2026 — E-004).
- **الملفات المتأثرة فعلياً:** `composer.json` / `composer.lock` (Livewire 4.4.8)، `package.json` / `package-lock.json` (Tailwind 4.3.5، Vite 8.3.8)، `vite.config.js` (إزالة تحميل الخط الخارجي `bunny`)، `resources/css/app.css` (رموز Tailwind 4)، `resources/views/components/{brand-mark,layouts/app,ui/button,ui/badge,ui/card,ui/field,ui/ltr}.blade.php`، `resources/views/livewire/design-system-preview.blade.php`، `resources/views/design-system.blade.php`، `app/Livewire/DesignSystemPreview.php`، `routes/web.php`، `tests/Feature/DesignSystemPreviewTest.php`.
- **الاختبارات المنفذة:** `npm run build` ✓ (CSS 48.30 kB)؛ فحص الـ CSS المبني ✓ (وجود `bg-brand-900` و`text-h1` و`shadow-1` و`.ltr`، وغياب أي رابط خط خارجي)؛ Feature ✓ (4 tests / 20 assertions)؛ كامل الاختبارات ✓ (9 tests / 27 assertions)؛ Pint ✓؛ `route:list` ✓ (مسار `/design-system`)؛ فحص متصفح فعلي ✓ (RTL، لا أخطاء console، زر تبديل اللغة يعمل عبر Livewire).
- **معايير القبول:** ✓ الرموز المعتمدة مُنقولة إلى `@theme` ✓ خطوط محلية فقط بلا CDN ✓ هيكل RTL أصلي بخصائص منطقية ✓ مكوّنات قابلة لإعادة الاستخدام ✓ لا قالب Admin جاهز ✓ لا تدرجات/glassmorphism/ظلال ثقيلة ✓ صفحة معاينة واحدة فقط (لم يُعمَّم على الـ99 شاشة) ✓ اختبارات وانحدار ✓ وثائق ومتابع محدَّثان.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** الصفحة `/design-system` للمراجعة فقط. لم تُنشأ أدوار أو جداول هوية بعد. R-010 يوثّق تحذيرات أدوات البناء.
- **المهمة التالية:** TASK-004 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-004 — نواة المؤسسة والحرم (Institution & Campus Foundation)
- **الوصف:** أول مخطط.domain في المرحلة 3: جداول المؤسسة وإعداداتها والأحرم مع قيود فريدة وتاريخية، ونماذجها، ونطاق استعلام أساسي، واختبارات «إضافة حرم دون تعديل كود». لا أسماء أو عناوين حقيقية (P-001 بانتظار الجامعة).
- **المرحلة:** 3. **المتطلبات السابقة:** TASK-003 `COMPLETED` + بوابة التصميم معتمدة.
- **الملفات المتأثرة فعلياً:** `database/migrations/2026_11_17_191945_create_institutions_table.php`، `…191946_create_institution_settings_table.php`، `…191947_create_campuses_table.php`، `app/Models/{Institution,InstitutionSetting,Campus}.php`، `app/RecordStatus.php` (أنشأه `make:enum` في جذر `app/` لا في `app/Enums/`)، `app/Actions/ActivateInstitution.php`، `lang/{ar,en}/values.php`، `tests/Feature/InstitutionFoundationTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/InstitutionFoundationTest.php` ✓ (7 tests / 23 assertions)؛ `php artisan migrate --force` على SQLite ✓ (3 migrations)؛ `php artisan test --compact` ✓ (16 tests / 50 assertions، منها 9 اختبارات انحدار)؛ Pint `--dirty --format agent` ✓.
- **معايير القبول:** ✓ مخطط يحترم قاموس البيانات (رمز فريد، أرشفة بتاريخ بدل الحذف، إعدادات مؤرخة بـ `effective_from/effective_to`) ✓ enum codes إنجليزية بترجمة عربية/إنجليزية ✓ إضافة حرم جديد دون تعديل كود ✓ استعلام نطاق `forInstitution()` + `active()` ✓ منع تكرار الرمز على مستوى DB ✓ مؤسسة نشطة واحدة عبر معاملة `ActivateInstitution` ✓ لا سياسات أكاديمية/مالية مُخترعة ولا أسماء/عناوين حقيقية ✓ migrations واختبارات وانحدار وPint ✓ تقرير ومتابع محدَّثان.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** نطاق مدير الحرم (المعيار الثاني للمرحلة 3) مؤجل إلى TASK-005 لأنه يحتاج مستخدمين وأدواراً. R-006 يمنع اختبار collation على MySQL؛ استُخدم SQLite للأداء الحالي. `date` في SQLite يُخزَّن بطابع زمني، لذا تتحقق الاختبارات عبر `whereDate` لا بمطابقة نصية. لم تُنشأ كليات/أقسام أو مبانٍ/قاعات بعد.
- **المهمة التالية:** TASK-005 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-005 — أساس الهوية والصلاحيات (Identity & RBAC Foundation)
- **الوصف:** users/roles/permissions ونطاقات البيانات (data scopes) كأساس لاختبار «مدير الحرم لا يرى غير نطاقه»، مع تفويض صريح وتدقيق للعمليات الحساسة. لا تُخترع أدوار أو سياسات جامعية؛ يُعتمد ما درج في المخطط (رئيس الجامعة، مدير الحرم، عميد، رئيس قسم، القبول/التسجيل، عضو هيئة تدريس، طالب، متقدم) كمعرفات تقنية قابلة للربط بالقرارات المؤسسية لاحقاً.
- **المرحلة:** 3. **المتطلبات السابقة:** TASK-004 `COMPLETED`.
- **معايير القبول:** ✓ جداول وعلاقات هوية بقيود فريدة ✓ صلاحيات ونطاقات تُطبَّق خادمياً لا في الواجهة ✓ اختبار عزل نطاق مدير الحرم ✓ تدقيق على منح/سحب الأدوار ✓ لا سياسات مُخترعة ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_192911…192915_*`، `app/{DataScopeCode,RoleCode}.php`، `app/Models/{DataScope,Role,AuditLog,User,Campus,Institution}.php`، `app/Policies/CampusPolicy.php`، `app/Actions/{AssignRole,RevokeRole}.php`، `database/factories/{DataScope,Role,AuditLog,Campus,Institution}Factory.php`، `database/seeders/{IdentityReferenceSeeder,DatabaseSeeder}.php`، `lang/{ar,en}/{roles,scopes}.php`، `tests/Feature/IdentityRbacFoundationTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/IdentityRbacFoundationTest.php` ✓ (13 tests / 35 assertions)؛ `php artisan migrate --force` ✓ (5 migrations)؛ `php artisan test --compact` ✓ (29 tests / 85 assertions، منها 16 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ (بعد الإصلاح أُعيد تشغيل الاختبارات ✓).
- **الحالة:** `COMPLETED`.
- **الملاحظات:** الأكواد والنطاقات والأدوار منسوخة من `docs/05_roles_permissions.md` و`docs/09_security_privacy_audit.md`. لا تُمنح صلاحيات حساسة بالاسم؛ العزل مطبَّق على مستوى الاستعلام + Policy + فشل مغلق عند غياب دور نشط. اكتُشفت ثغرة أثناء الاختبار (مستخدم بلا دور نشط يرى حرمه المسند) وأُصلحت قبل الإغلاق. لم تُنشأ جدول صلاحيات نهائي لأن مصفوفة الصلاحيات معلّقة على اعتماد الجامعة (docs/05 سطر 48). التفويض متعدد الأدوار/الوحدات التنظيمية ومستندات Document base تبقى لمهام لاحقة.
- **المهمة التالية:** TASK-006 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-006 — الهيكل التنظيمي: الكليات والأقسام وعلاقاتها (Organization Structure)
- **الوصف:** استكمال المرحلة 3 بجداول `colleges` و`departments` و`campus_college_assignments` مع الأرشفة والتاريخ، وربطها بنطاقات البيانات المُأسَّسة في TASK-005 (نطاق العميد = كلية، رئيس القسم = قسم). لا أسماء كليات أو أقسام حقيقية ولا قرارات إنشاء/دمج (P-001).
- **المرحلة:** 3. **المتطلبات السابقة:** TASK-005 `COMPLETED`.
- **معايير القبول:** ✓ كليات/أقسام بعلاقات وقيود فريدة وتاريخ ✓ إسناد كلية إلى أكثر من حرم دون تكرار ✓ عزل بيانات العميد/رئيس القسم عبر `visibleTo` ✓ لا سياسات أو أسماء حقيقية ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_193547…193552_create_*_table.php`، `app/Models/{College,Department,User}.php`، `database/factories/{College,Department}Factory.php`، `tests/Feature/OrganizationStructureTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/OrganizationStructureTest.php` ✓ (9 tests / 25 assertions)؛ `php artisan migrate --force` ✓ (6 migrations)؛ `php artisan test --compact` ✓ (38 tests / 110 assertions، منها 29 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ ثم أُعيد تشغيل الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** القيود منسوخة من `docs/06` سطور 20–24 و`docs/02` سطور 14 و56–59. فُصلت «الكلية في الحرم» و«الكلية المالكة للقسم» في علاقتين مؤرختين؛ إغلاق السريان يتم بـ `effective_to` لا بالحذف. تكليف العميد/رئيس القسم مُؤرخ في جدول الإسناد مع سبب وجهة اعتماد ومرجع قرار. عزل `visibleTo` يعتمد على الدور النشط + الإسناد الساري، ويفشل مغلقاً. لا أسماء كليات/أقسام حقيقية (P-001). `organizational_units` وتكليفات HR و`Document base` تبقى لمهام لاحقة.
- **المهمة التالية:** TASK-007 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-007 — الموارد المكانية: المباني والطوابق والقاعات (Spatial Resources)
- **الوصف:** استكمال المرحلة 3 بتسلسل `campus → building → floor → room` مع السعة ورموز التجهيزات المرجعية، والأرشفة بدل الحذف، وعزل النطاق حسب الحرم المُسند (مدير الحرم/موظف المرافق). لا قائمة تجهيزات أو سعات قياسية مُخترعة قبل اعتماد الجامعة.
- **المرحلة:** 3. **المتطلبات السابقة:** TASK-006 `COMPLETED`.
- **معايير القبول:** ✓ تسلسل هرمي بقيود FK وفريدة ✓ سعة قابلة للسريان وتاريخ ✓ لا حذف مع أرشفة ✓ عزل مدير الحرم لموارعه فقط ✓ لا سياسات/تجهيزات مُفترضة ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_193945…193950_create_*_table.php`، `app/Models/{Building,Floor,Room,RoomCapacityPeriod,RoomFeature}.php`، `app/Models/{User,Campus}.php` (مساعد `visibleCampusIds`)، `database/factories/{Building,Floor,Room,RoomFeature}Factory.php`، `tests/Feature/SpatialResourcesTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/SpatialResourcesTest.php` ✓ (9 tests / 31 assertions)؛ `php artisan migrate --force` ✓ (6 migrations)؛ `php artisan test --compact` ✓ (47 tests / 141 assertions، منها 38 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ ثم أُعيد تشغيل الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطر 21 و`docs/02` سطر 56. الرموز فريدة داخل الأب فقط (`campus+code`، `building+code`، `floor+code`) لتجنب افتراض نطاق رمز عالمي. السعة والتجهيزات علاقات مؤرخة بمرجع قرار؛ جدول `room_features` فارغ افتراضياً لأن قائمة التجهيزات تحتاج اعتماد الجامعة. مكان المحاضرة سيُحدد لاحقاً على مستوى اجتماع الشعبة لا هنا. لا أسماء/أرقام حقيقية (P-001).
- **المهمة التالية:** TASK-008 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-008 — قاعدة الوثائق والموافقات (Document & Approval Base)
- **الوصف:** إغلاق المرحلة 3 ببنية `documents` / `document_versions` / `approvals` وفق `docs/06` سطور 36–37: مالك ونوع ومفتاح تخزين خاص/checksum/رفع، ونسخ، وقرار بمعتمد ووقت ومرجع. لا محتوى ملف داخل DB، لا أسماء ملفات حقيقية، ولا سياسات احتفاظ أو تصنيف مُخترعة.
- **المرحلة:** 3. **المتطلبات السابقة:** TASK-007 `COMPLETED`.
- **معايير القبول:** ✓ بيانات وصفية فقط في DB والتخزين خاص ✓ checksum إلزامي ونسخ مؤرخة ✓ سجل موافقة بمعتمد/وقت/مرجع ✓ لا حذف عشوائي ✓ ربط بالمالك بنوع/معرف ✓ لا سياسات مُفترضة ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_194503…194505_create_*_table.php`، `app/Models/{Document,DocumentVersion,Approval}.php`، `database/factories/{Document,DocumentVersion,Approval}Factory.php`، `lang/{ar,en}/approvals.php`، `tests/Feature/DocumentApprovalBaseTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/DocumentApprovalBaseTest.php` ✓ (11 tests / 27 assertions)؛ `php artisan migrate --force` ✓ (3 migrations)؛ `php artisan test --compact` ✓ (58 tests / 168 assertions، منها 47 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ ثم أُعيد تشغيل الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 36–37 و`docs/09` سطور 24–25 و40–41. DB يحفظ البيانات الوصفية فقط؛ `storage_key` يجب أن يبدأ بـ `private/` ويرفض `public/` على مستوى النموذج؛ checksum إلزامي. النسخ والموافقات غير قابلة للتعديل أو الحذف، والوثائق تُؤرشف لا تُحذف. أنواع القرارات (`approved/rejected`) معرّفات تقنية بترجمة عربية/إنجليزية، وليست سياسة اعتماد. لا أنواع ملفات أو احتفاظ/تصنيف مُفترضة، ولا أسماء ملفات حقيقية.
- **المهمة التالية:** TASK-009 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-009 — دليل الأشخاص والمعرفات (People Directory & Identifiers)
- **الوصف:** أول مهمة في المرحلة 4: `people` و`person_identifiers` مع ربط `users.person_id`، وحماية المعرفات الرسمية بوصول مقيد وتخزين مشفر/محمي، دون أسماء أو أرقام هوية حقيقية أو أنواع معرفات مُفترضة.
- **المرحلة:** 4. **المتطلبات السابقة:** TASK-008 `COMPLETED` (إغلاق المرحلة 3).
- **معايير القبول:** ✓ سجل شخص موحد وربط اختياري بحساب الدخول ✓ معرفات رسمية بأنواع مرجعية قابلة للتوسعة وقيم محمية ✓ وصول مقيد للمعرفات ✓ لا بيانات حقيقية أو أنواع مُخترعة ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_195114…195117_create_* / add_person_id_*`، `app/Models/{Person,PersonIdentifierType,PersonIdentifier}.php`، `app/Models/User.php` (ربط اختياري) ، `app/Policies/PersonIdentifierPolicy.php`، `app/Actions/RevealPersonIdentifier.php`، `config/privacy.php`، `lang/{ar,en}/identifier_types.php`، `database/factories/Person*Factory.php`، `tests/Feature/PeopleDirectoryTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/PeopleDirectoryTest.php` ✓ (11 tests / 34 assertions)؛ `php artisan migrate --force` ✓ (4 migrations)؛ `php artisan test --compact` ✓ (69 tests / 202 assertions، منها 58 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ ثم أُعيد تشغيل الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 25–27 و`docs/09` سطور 36 و41 و46 و`docs/05` سطر 48. القيمة تُخزَّن مشفرة (`encrypted`) مع `value_hash` = HMAC بمفتاح التطبيق للبحث ومنع التكرار دون كشف القيمة؛ الحقول مخفية من التسلسل. الوصول fail-closed عبر قائمة معتمدين صريحة في `config/privacy.php` فارغة افتراضياً — لا يُمنح بالاسم (لا system_admin ولا identity_admin ولا hr_director). إجراء الاطلاع يسجّل الوصول في `audit_logs` دون القيمة. جدول الأنواع فارغ افتراضياً (P-006)؛ فترات السريان تُغلق بالتاريخ لا بالحذف؛ لا أسماء/أرقام حقيقية (P-001/P-002).
- **المهمة التالية:** TASK-010 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-010 — الموظفون وهيئة التدريس والتعيينات (Employees, Faculty & Assignments)
- **الوصف:** استكمال المرحلة 4 بملفات `employees` و`faculty_profiles` و`employment_assignments` مع التبعية الإدارية/التخصصية والتكليفات المؤرخة، وربط الموظف بالشخص الموحد. لا أرقام وظيفية بنمط مُخترع (P-006)، ولا لوائح HR أو درجات/رتب مُفترضة (P-015).
- **المرحلة:** 4. **المتطلبات السابقة:** TASK-009 `COMPLETED`.
- **معايير القبول:** ✓ ملف موظف وأستاذ مرتبطان بالشخص ✓ تعيينات وتبعيات بتواريخ سريان ومرجع قرار ✓ لا حذف مع أرشفة/إغلاق فترة ✓ لا سياسات أو أرقام أو أسماء حقيقية ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_195554…195556_create_*_table.php`، `app/Models/{Employee,FacultyProfile,EmploymentAssignment}.php`، `app/Actions/AssignEmployee.php`، `database/factories/{Employee,FacultyProfile,EmploymentAssignment}Factory.php`، `tests/Feature/HrCoreTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/HrCoreTest.php` ✓ (12 tests / 41 assertions)؛ `php artisan migrate --force` ✓ (3 migrations)؛ `php artisan test --compact` ✓ (81 tests / 243 assertions، منها 69 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ ثم أُعيد تشغيل الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 32–34 و85 و`docs/02` سطور 27 و32 و`docs/03` سطر 59 و`docs/04` سطور 203–227. الرقم الوظيفي يُدخل ولا يُولَّد ولا يُعاد استخدامه (قيد فريد + أرشفة بلا حذف)؛ ملف موظف واحد لكل شخص، وملف أستاذ واحد لكل موظف. لكل تكليف مدير إداري مباشر واحد في الفترة (قيد على مستوى النموذج) مع إشراف تخصصي موازٍ مسموح، ووحدة/حرم ومدة ومرجع قرار. الرتب/التخصصات/أنواع التوظيف حقول فارغة حتى اعتماد الجامعة (P-015). إجراء `AssignEmployee` يقيّد التعيين بـ HR (docs/05 سطر 24) ويسجّل تدقيقاً؛ مدير الحرم ممنوع ويُختبر ذلك. عزل `visibleTo` حسب الحرم المسند مع فشل مغلق. لا أسماء أو أرقام حقيقية (P-001/P-006).
- **المهمة التالية:** TASK-011 — لا تُبدأ إلا بأمر «تابع المهمة التالية».

### TASK-011 — أساس القبول: الدورات والمتقدمون والطلبات (Admission Foundation)
- **الوصف:** أول مهمة في المرحلة 5: `admission_cycles` و`applicants` و`applications` مع مرفقات ومراجعات وقرارات، ومفتاح idempotency لمنع تكرار الطلب. تحويل المتقدم إلى طالب والرقم الجامعي في مهمة لاحقة؛ لا سياسات قبول أو أسماء/بيانات حقيقية (P-001/P-005/P-006).
- **المرحلة:** 5. **المتطلبات السابقة:** TASK-010 `COMPLETED` (إغلاق المرحلة 4).
- **معايير القبول:** ✓ دورة بتواريخ ✓ متقدم مرتبط بالشخص ✓ طلب بمفتاح idempotency ومراجعة/قرار ✓ لا حذف مع أرشفة ✓ لا سياسات أو بيانات حقيقية ✓ اختبارات وانحدار وPint وتقرير.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_200152…200156_create_*_table.php`، `app/Models/{AdmissionCycle,Application,AdmissionReview,AdmissionDecision,ApplicantEvent}.php`، `app/Actions/{SubmitApplication,DecideApplication}.php`، المصانع الخمسة، `tests/Feature/AdmissionFoundationTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/AdmissionFoundationTest.php` ✓ (8 tests / 29 assertions)؛ `php artisan migrate --force` ✓ (5 migrations)؛ `php artisan test --compact` ✓ (89 tests / 272 assertions، منها 81 اختبار انحدار)؛ Pint `--dirty --format agent` ✓ ثم أُعيد تشغيل الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطر 68 و`docs/13` سطر 19. المتقدَّم ممثَّل بالشخص الموحد (`person_id`) على الطلب دون جدول `applicants` منفصل. مفتاح idempotency فريد: إعادة الإرسال بالمفتاح نفسه تُعيد الطلب الأصلي ولا تُنشئ حدثاً ثانياً. القرارات والأحداث غير قابلة للتعديل/الحذف؛ المراجعات قابلة للتحديث. قرار القبول مقصور على `AdmissionsRegistrationDirector` مع تدقيق وحدث، ومصطلحاته مترجمة عربي/إنجليزي. لا معايير قبول أو أسماء أو أرقام حقيقية (P-001/P-005). ربط البرنامج برمز نصي مؤقت حتى تُنشأ البرامج في TASK-012.
- **المهمة التالية:** TASK-012 — تُستأنف تلقائياً في دورة العمل نفسها.

### TASK-012 — التقويم الأكاديمي والبرامج (Calendar & Programs)
- **الوصف:** `academic_years` / `academic_terms` / `academic_programs` / `program_college_participations` / `program_versions` بسريان وتاريخ وكلية رئيسية واحدة؛ مقدَّمة على تحويل الطالب لأن تحويله يعتمد على البرنامج. لا مستويات أو سياسات اعتماد مُخترعة (P-007/P-011).
- **المرحلة:** 6 (مقدَّمة جزئياً). **المتطلبات السابقة:** TASK-011 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_200805…200809_create_*_table.php`، `app/Models/{AcademicYear,AcademicTerm,AcademicProgram,ProgramCollegeParticipation,ProgramVersion}.php`، `app/Actions/{DefineProgram,CreateAcademicTerm}.php`، المصانع الخمسة، `tests/Feature/AcademicCalendarAndProgramsTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/AcademicCalendarAndProgramsTest.php` ✓ (10 tests / 26 assertions)؛ `php artisan migrate --force` ✓ (5 migrations)؛ `php artisan test --compact` ✓ (99 tests / 298 assertions، منها 89 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 42–45 و`docs/02` سطور 34–38 و52–53 و`docs/04` سطر 41 و`docs/05` سطر 12. السنوات والفصول بسريان مؤرخ مع منع التداخل خادمياً وفحص وقوع الفصل داخل سنته؛ كل برنامج يسجل مرة وله كلية رئيسية واحدة (تمنع ككلية مشاركة)، والمشاركات مؤرخة بقرار؛ نسخ البرنامج مرقمة ونسخة معتمدة غير قابلة للتعديل. المستوى/نوع الفصل/نوع المشاركة حقول مرجعية فارغة حتى اعتماد الجامعة (P-007/P-011). الإجراءات مقصورة على `AcademicLeadership` مع تدقيق. لا أسماء برامج أو سنوات حقيقية (P-001).
- **المهمة التالية:** TASK-013 — تُستأنف تلقائياً.

### TASK-013 — تحويل المتقدم إلى طالب والسجل الأساسي (Student Conversion)
- **الوصف:** `students` و`student_program_enrollments` و`student_status_history` و`student_documents` مع تحويل idempotent من طلب القبول، ورقم جامعي فريد يُدخل لا يُولَّد، وحفظ معرف الطلب الأصلي. لا تكرار شخص/طالب عند إعادة المحاولة (docs/06 سطر 70، P-006).
- **المرحلة:** 5. **المتطلبات السابقة:** TASK-012 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_201043…201046_create_*_table.php`، `app/StudentStatus.php`، `app/Models/{Student,StudentProgramEnrollment,StudentStatusHistory,StudentDocument}.php`، `app/Actions/{ConvertApplicantToStudent,ChangeStudentStatus}.php`، المصانع الأربعة، `lang/{ar,en}/values.php`، `tests/Feature/StudentConversionTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/StudentConversionTest.php` ✓ (10 tests / 34 assertions)؛ `php artisan migrate --force` ✓ (4 migrations)؛ `php artisan test --compact` ✓ (109 tests / 332 assertions، منها 99 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 50–53 و70 و85 و97 و`docs/05` سطور 14–16. الرقم الجامعي يُدخل ولا يُولَّد (قيد فريد)； التحويل idempotent عبر `source_application_id` ويُرفض لطلب غير مقبول أو لشخص لديه سجل طالب. سجل الطالب لا يُحذف؛ التحاق البرنامج يُغلق بالأرشفة؛ تاريخ الحالات غير قابل للتعديل/الحذف ويُسجَّل مع السبب والمرجع والفاعل. وثائق الطالب تربط بيانات TASK-008 بلا حذف. حالات الطالب منصوص عليها في `docs/06` سطر 53 (تأجيل/عودة/انسحاب/تخرج) ومترجمة عربي/إنجليزي؛ لا أرقام أو أسماء حقيقية (P-001/P-006).
- **المهمة التالية:** TASK-014 — تُستأنف تلقائياً.

### TASK-014 — كتالوج المقررات وخطط الدراسة (Course Catalog & Study Plans)
- **الوصف:** `courses` / `course_versions` / `study_plan_versions` / `study_plan_courses` / `course_prerequisites` بنسخ اعتماد وسريان ومتطلبات، دون ساعات أو قواعد نجاح مُفترضة (P-007).
- **المرحلة:** 6. **المتطلبات السابقة:** TASK-013 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_201443…201448_create_*_table.php`، `app/Models/{Course,CourseVersion,StudyPlanVersion,StudyPlanCourse,CoursePrerequisite,StudentPlanAssignment}.php`، `app/Actions/{DefineCourse,ApproveStudyPlanVersion,AssignStudentPlan}.php`، المصانع الستة، `tests/Feature/CourseCatalogAndStudyPlansTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/CourseCatalogAndStudyPlansTest.php` ✓ (8 tests / 25 assertions)؛ `php artisan migrate --force` ✓ (6 migrations)؛ `php artisan test --compact` ✓ (117 tests / 357 assertions، منها 109 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/02` سطور 40–53 و`docs/06` سطور 46–49 و52 و`docs/04` سطور 155–170. تعريف المقرر مستقل عن الشعبة؛ نسخ المقرر/الخطة مرقمة ونسخة معتمدة غير قابلة للتعديل، والانتقال لنسخة جديدة يحتاج قراراً (لا نقل تلقائي). الساعات/الوحدات والمستويات وأنواع المقررات حقول فارغة حتى اعتماد الجامعة (P-007/P-011) — لا قيمة ساعات مُفترضة. المتطلبات تمنع الذات والدورات. ربط الطالب بخطة معتمدة فقط بفترة ومرجع قرار، بلا تداخل؛ لا حذف في كل الجداول. الإجراءات مفوّضة ومُدقَّقة.
- **المهمة التالية:** TASK-015 — تُستأنف تلقائياً.

### TASK-015 — الطرح والشعب والجداول (Offerings, Sections & Schedules)
- **الوصف:** `course_offerings` / `course_sections` / `section_meetings` / `teaching_assignments` مع ربط القاعة من TASK-007 ومنع تعارض الاجتماعات والسعة، دون سياسة نصاب مُفترضة.
- **المرحلة:** 7. **المتطلبات السابقة:** TASK-014 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_201802…201805_create_*_table.php`، `app/Models/{CourseOffering,CourseSection,SectionMeeting,TeachingAssignment}.php`، `app/Actions/{OfferCourse,CreateCourseSection,ScheduleSectionMeeting,AssignInstructor}.php`، المصانع الأربعة، `tests/Feature/OfferingsSectionsAndSchedulesTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/OfferingsSectionsAndSchedulesTest.php` ✓ (7 tests / 20 assertions)؛ `php artisan migrate --force` ✓ (4 migrations)؛ `php artisan test --compact` ✓ (124 tests / 377 assertions، منها 117 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/02` سطور 44–48 و`docs/06` سطور 55–58 و`docs/04` سطور 171–190. الطرح فريد لكل مقرر/فصل؛ الشعب مرتبطة بالحرم والسعة **حقل اختياري بلا قيمة مُفترضة** (لا سياسة نصاب). اجتماعات الشعب تربط القاعة من TASK-007 وتتحقق خادمياً من ترتيب الوقت ويوم الأسبوع ومنع التعارض على نفس الشعبة/القاعة في الفترة المتداخلة. إسناد المدرس مرتبط بالموظف من TASK-010 بقرار وفترة، مع منع التكرار. كل الإجراءات مفوّضة ومُدقَّقة؛ لا حذف، وعزل الشعب بحرمها.
- **المهمة التالية:** TASK-016 — تُستأنف تلقائياً.

### TASK-016 — تسجيل الطلاب (Course Registration)
- **الوصف:** `course_enrollments` بتسجيل ذاتي/إداري، ومنع التكرار النشط، والتحقق من السعة والمتطلبات وتعارض الجدول داخل معاملة؛ لا حدود تسجيل مُفترضة (P-009).
- **المرحلة:** 7. **المتطلبات السابقة:** TASK-015 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** `app/EnrollmentStatus.php`، migration `2026_11_17_202030_create_course_enrollments_table.php`، `app/Models/CourseEnrollment.php`، `app/Actions/{EnrollStudentInSection,DropCourseEnrollment}.php`، المصنع، `tests/Feature/CourseRegistrationTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/CourseRegistrationTest.php` ✓ (9 tests / 17 assertions)؛ `php artisan migrate --force` ✓ (migration واحدة)؛ `php artisan test --compact` ✓ (133 tests / 394 assertions، منها 124 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطر 48 و`docs/02` سطر 48 و`docs/04` سطور 171–190. التسجيل ذاتي (`source=self`، مقصور على الشخص عبر `person_id`) أو إداري (التسجيل/القيادة الأكاديمية). **مفتاح idempotency فريد** يمنع التكرار، مع منع تسجيل نشط مكرر في نفس الطرح على مستوى النموذج. السعة تُفحص فقط إذا حُدِّدت (لا قيمة مُفترضة — P-009). المتطلب بنيوي: يشترط وجود تسجيل `completed` في المقرر المتطلب (لا قاعدة نجاح/درجة مُخترعة). يُمنع التعارض مع جداول شعب الطالب المسجلة. الانسحاب يُغلق التسجيل بالسبب والتدقيق بلا حذف. كل الإجراءات داخل معاملة ومُدقَّقة.
- **المهمة التالية:** TASK-017 — تُستأنف تلقائياً.


### TASK-017 — الحضور وعناصر التقييم (Attendance & Assessment)
- **الوصف:** `attendance_sessions` / `attendance_records` / `assessment_components` مع تسجيل الدرجات الخام، دون قواعد حضور أو إنذارات مُفترضة (P-008).
- **المرحلة:** 8. **المتطلبات السابقة:** TASK-016 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** `app/AttendanceStatus.php`، `app/Support/SectionAccess.php`، migrations `2026_11_17_202601…202603_create_*_table.php`، `app/Models/{AttendanceSession,AttendanceRecord,AssessmentComponent}.php`، `app/Actions/{OpenAttendanceSession,RecordAttendance,ApproveAttendanceSession,DefineAssessmentComponent}.php`، المصانع الثلاثة، `tests/Feature/AttendanceAndAssessmentTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/AttendanceAndAssessmentTest.php` ✓ (8 tests / 23 assertions)؛ `php artisan migrate --force` ✓ (3 migrations)؛ `php artisan test --compact` ✓ (141 tests / 417 assertions، منها 133 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 60–61 و91 و`docs/04` سطور 518–552 (SCR-057/058/060). الجلسة فريدة لكل (شعبة، تاريخ) وتبدأ مفتوحة؛ اعتمادها يُقفل السجلات ضد التعديل (إجراء تصحيح منفصل). سجل الحضور مرتبط بتسجيل الطالب (لا بشخصه)، ويُمنع لطالب غير مسجل في شعبة الجلسة؛ حالة العذر تتطلب سبباً؛ التعديل يُسجَّل بالقيمة السابقة/الجديدة في التدقيق (سجل تغييرات). عناصر التقييم بسعة درجة قابلة للفارغ ولا أوزان/حدود مُفترضة. التفويض عبر `SectionAccess`: أعضاء هيئة التدريس مقصورون على الشعب المسندة إليهم (`teaching_assignments`)، والتسجيل/القيادة الأكاديمية/رؤساء الأقسام مؤسسياً. لا حذف، ولا قواعد حضور أو إنذارات أو درجات مُفترضة (P-008/P-007). `assessment_scores` والتقديم/الاعتماد/النشر في TASK-018.
- **المهمة التالية:** TASK-018 — تُستأنف تلقائياً.

### TASK-018 — الدرجات والاعتماد والنشر والكشف (Results & Transcript)
- **الوصف:** `assessment_scores` / `grade_submissions` / `grade_approvals` / `published_results` / `student_transcripts` / `graduation_audits` مع فصل المُدخل عن المُعتمد ولقطات منشورة غير قابلة للتعديل؛ لا قواعد تقدير/نجاح مُفترضة (P-007/P-011).
- **المرحلة:** 8. **المتطلبات السابقة:** TASK-017 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_203011…203016_create_*_table.php`، `app/Models/{AssessmentScore,GradeSubmission,GradeApproval,PublishedResult,StudentTranscript,GraduationAudit}.php`، `app/Actions/{RecordScore,SubmitGrades,ReviewGradeSubmission,PublishResults,WithdrawPublishedResult,GenerateStudentTranscript,AuditGraduation}.php`، علاقات `scores()` في `CourseEnrollment` و`courseEnrollments()` في `Student`، المصانع الستة، `tests/Feature/ResultsAndTranscriptTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/ResultsAndTranscriptTest.php` ✓ (10 tests / 27 assertions)؛ `php artisan migrate --force` ✓ (6 migrations)؛ `php artisan test --compact` ✓ (151 tests / 444 assertions، منها 141 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 61–65 و91 و`docs/04` سطور 554–581 (SCR-061/062/063). الدرجات الخام قابلة للفارغ وغير مُقيّدة بحدود أو أوزان مُفترضة؛ التعديل مسجّل بالقيمة السابقة/الجديدة وممنوع بعد اعتماد التقديم. تقديم الدرجات فريد لكل شعبة مفتوحة. **فصل الإدخال عن الاعتماد**: المعتمد يجب أن يختلف عن المُقدِّم، ويقتصر على رئيس القسم/القيادة الأكاديمية. النشر يتطلب تقديمًا معتمدًا وينشئ لقطات غير قابلة للتعديل؛ السحب بإجراء استثنائي بسبب مُدقَّق. الكشف وفحص التخرج لقطات غير قابلة للتعديل، وفحص التخرج يتم على نسخة خطة معتمدة وبنيوياً على التسجيلات المكتملة (لا قواعد نجاح/ساعات مُخترعة — P-007/P-011). لا حذف صامت، وكل الإجراءات داخل معاملات ومُدقَّقة.
- **المهمة التالية:** TASK-019 — تُستأنف تلقائياً.

### TASK-019 — سياسات الرسوم والفواتير (Fees & Invoices)
- **الوصف:** `currencies` / `fee_policies` / `fee_items` / `fee_policy_items` / `student_invoices` / `invoice_lines` بمبالغ `DECIMAL` مؤرخة وعملة، دون قيم أو ضرائب أو تقسيط مُفترض (P-012/P-013).
- **المرحلة:** 9. **المتطلبات السابقة:** TASK-018 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_203926…203931_create_*_table.php`، `app/Support/MoneyMath.php`، `app/Models/{Currency,FeeItem,FeePolicy,FeePolicyItem,StudentInvoice,InvoiceLine}.php`، `app/Actions/{DefineCurrency,DefineFeeItem,CreateFeePolicy,ApproveFeePolicy,PublishFeePolicy,CreateStudentInvoice,IssueStudentInvoice,CancelStudentInvoice}.php`، المصانع الستة، `tests/Feature/FeesAndInvoicesTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan test --compact tests/Feature/FeesAndInvoicesTest.php` ✓ (6 tests / 34 assertions)؛ `php artisan migrate --force` ✓ (6 migrations)؛ `php artisan test --compact` ✓ (157 tests / 478 assertions، منها 151 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 8 و73 و92 و`docs/04` سطور 644–669 (SCR-071/072/073). المبالغ `DECIMAL(16,2)` مع عملة، والحساب عبر BCMath بلا FLOAT. سياسة الرسوم مُؤرَّخة ومُنسَّخة (`code`+`version_no`) تمر بـ `draft→approved→published` بفصل إدخال عن اعتماد؛ النشر لا يُطبِّق بأثر رجعي. الفاتورة تُنشأ فقط من سياسة منشورة وتُنسخ مبالغ البنود إلى `invoice_lines` كلقطة (لا تتأثر بتعديل السياسة). رقم الفاتورة فريد ولا يُعاد استخدامه، ومنع التكرار بمفتاح idempotency وبفحص استحقاق مفتوح. الإصدار والإلغاء بإجراءات مُدقَّقة بالإلغاء بسبب وفصل مهام؛ لا حذف. لا عملة/مبلغ/ضريبة/خصم/تقسيط مُفترض (P-012/P-013). التحصيل والرد والتسوية في TASK-020.
- **المهمة التالية:** TASK-020 — تُستأنف تلقائياً.

### TASK-020 — التحصيل والإيصالات والردود والتسوية (Payments & Reconciliation)
- **الوصف:** `student_payments` / `payment_allocations` / `receipts` / `refunds` / `financial_adjustments` / `cashboxes` / `cash_sessions` / `bank_reconciliations` بمعاملات ومفاتيح idempotency ومنع الحذف، ودفع جزئي/مكرر/رد/عكس مُختبر (P-014).
- **المرحلة:** 9. **المتطلبات السابقة:** TASK-019 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_17_204605…204612_create_*_table.php`، `app/Support/FinanceAccess.php`، `app/Models/{Cashbox,CashSession,StudentPayment,Receipt,PaymentAllocation,Refund,FinancialAdjustment,BankReconciliation}.php`، `app/Actions/{OpenCashSession,CaptureStudentPayment,IssueReceipt,AllocatePaymentToInvoice,RequestRefund,ApproveRefund,ExecuteRefund,CloseCashSession,ApproveCashSessionClosure,RecordBankReconciliation}.php`، المصانع الثمانية، `tests/Feature/PaymentsAndReconciliationTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan migrate:fresh --force` ✓ (8 migrations)؛ `php artisan test --compact tests/Feature/PaymentsAndReconciliationTest.php` ✓ (7 tests / 36 assertions)؛ `php artisan test --compact` ✓ (164 tests / 514 assertions، منها 157 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 73 و92–93 و`docs/04` SCR-071–076. المبالغ `DECIMAL(16,2)` مع عملة وحساب BCMath بلا FLOAT. التحصيل idempotency بمفتاح ومرجع خارجي فريد، والإيصال لقطة غير قابلة للتعديل (إيصال واحد لكل دفعة، وفشل الطباعة لا يُلغي القبض SCR-074). التخصيص سجل_append-only يمنع التجاوز عن المتبقي من الدفعة أو الفاتورة بنفس العملة (لا قيد فريد على زوج دفعة/فاتورة كي تُسمح التخصيصات الجزئية المتكررة والتصحيحية). الرد يرتبط بالحركة الأصلية ويُقيد بالمتبقي، بفصل بين الطلب والاعتماد والتنفيذ، وينشئ تسوية عكسية (SCR-075). الورديات تحسب المتوقع من الدفعات، وتتطلب تفسير الفرق وموافقة مشرف يخالف أمين الصندوق؛ المطابقة البنكية تحسب الفرق وتُسجَّل كسجل غير قابل للتعديل. لا عملة/مبلغ/ضريبة/قواعد رد مُفترضة (P-014). الخدمات والإشعارات في TASK-021.
- **المهمة التالية:** TASK-021 — تُستأنف تلقائياً.

### TASK-021 — الطلبات والإشعارات والمستندات (Services & Notifications)
- **الوصف:** `service_request_types` / `service_requests` / `workflow_steps` / `request_events` / `notification_templates` / `notifications` / `delivery_attempts` / `announcements` مع مرفقات من TASK-008 وتدفق موثّق، دون لوائح خدمات مُفترضة.
- **المرحلة:** 10. **المتطلبات السابقة:** TASK-020 `COMPLETED`.
- **الملفات المتأثرة فعلياً:** migrations `2026_11_21_150000…150007_create_*_table.php`، `app/Support/{ServicesAccess,RequestAudit}.php`، `app/Models/{ServiceRequestType,ServiceRequest,WorkflowStep,RequestEvent,NotificationTemplate,Notification,DeliveryAttempt,Announcement}.php`، `app/Actions/{DefineServiceRequestType,DefineWorkflowVersion,PublishWorkflowVersion,SubmitServiceRequest,TransitionServiceRequest,WithdrawServiceRequest,DefineNotificationTemplate,ApproveNotificationTemplate,PublishNotificationTemplate,SendNotification,RecordDeliveryAttempt,MarkNotificationRead,CreateAnnouncement,PublishAnnouncement,ActivateScheduledAnnouncement,StopAnnouncement}.php`، المصانع الثمانية، `lang/{ar,en}/{services,notifications,announcements}.php`، `tests/Feature/ServicesAndNotificationsTest.php`، هذا الملف.
- **الاختبارات المنفذة:** `php artisan migrate --force` ✓ (8 migrations)؛ `php artisan test --compact tests/Feature/ServicesAndNotificationsTest.php` ✓ (8 tests / 50 assertions)؛ `php artisan test --compact` ✓ (172 tests / 564 assertions، منها 164 اختبار انحدار)؛ Pint ثم إعادة الاختبارات ✓.
- **الحالة:** `COMPLETED`.
- **الملاحظات:** من `docs/06` سطور 80–81 و94 و`docs/03` سطر 68 و`docs/04` SCR-005/006/077/079/080/081. كل طلب له نوع ومقدم وحالة ومرفقات (قاعدة TASK-008 morph) ومسؤول وسجل أحداث append-only وقرار موثّق؛ المسارات مُنسَّخة (`type`+`version_no`) والمنشورة غير قابلة للتعديل، ونشر إصدار جديد مع طلبات جارية يتطلب مرجع ترحيل صريح (SCR-079). انتقال الخطوة يتحقق من دور الفاعل ونطاق بياناته، والرفض/الإرجاع يتطلبان سبباً؛ السحب قبل المعالجة فقط وبسبب موثّق، بلا حذف. قوالب الإشعارات بإصدارات وفصل إنشاء عن اعتماد ونشر؛ الإرسال بقناة داخلية فقط ولقطة من القالب المنشور ومستلم واحد مع تحقق نطاق، ومحاولات الإرسال سجل append-only بمفتاح idempotency وقفل المزود الخارجي لحين P-021. الإعلانات بجمهور ونطاق ولغة عربية/إنجليزية وفصل إنشاء عن نشر وجدولة وتوقف موثّق، وبلا رابط سجل subject كي لا تُسرَّب تفاصيل (SCR-006). لا لوائح خدمات أو قوالب رسمية أو مزودي رسائل مُفترضين (P-018/P-021/P-022/P-023). المكتبة والدراسات العليا في TASK-022.
- **المهمة التالية:** TASK-022 — تُستأنف تلقائياً.

### TASK-022 — المكتبة والدراسات العليا (Library & Postgraduate Base)
- **الوصف:** هياكل `library_branches` / `bibliographic_records` / `item_copies` / `circulation_transactions` / `reservations` و`postgraduate_records` / `supervisor_assignments` / `research_proposals` / `research_milestones` / `defense_sessions` دون لوائح إعارة أو إشراف مُفترضة (P-016/P-017).
- **المرحلة:** 10. **المتطلبات السابقة:** TASK-021 `COMPLETED`. **الحالة:** `NOT_STARTED`.

### TASK-023 — التقارير والتصدير الآمن (Reports & Secure Export)
- **الوصف:** تعريفات تقارير ولوحات تُنتج استعلاماتها من نطاق المستخدم نفسه، مع تصدير مُدقَّق وممنوع دون صلاحية؛ لا مؤشرات أو عتبات مُفترضة.
- **المرحلة:** 11. **المتطلبات السابقة:** TASK-022 `COMPLETED`. **الحالة:** `NOT_STARTED`.

### TASK-024 — ضمان الجودة والترحيل (QA & Migration Readiness)
- **الوصف:** توسيع اختبارات الأمان/الانحدار، فحوصات سلامة القيود، ومخطط استيراد تجريبي وبيانات مرجعية معتمدة (بدون بيانات حقيقية)، وتوثيق RPO/RTO المعلّق.
- **المرحلة:** 12. **المتطلبات السابقة:** TASK-023 `COMPLETED`. **الحالة:** `NOT_STARTED`.

### TASK-025 — التشغيل التجريبي والإطلاق (Pilot & Launch Runbook)
- **الوصف:** دليل النشر والتشغيل والنسخ والاستعادة والرجوع وخطة تشغيل تجريبي وقوائم UAT حسب الدور، مع إبقاء البنود المعتمدة على الجامعة معلّقة بوضوح.
- **المرحلة:** 13. **المتطلبات السابقة:** TASK-024 `COMPLETED`. **الحالة:** `NOT_STARTED`.

---

## 7. سجل التحديثات

| التاريخ | التغيير | بواسطة |
|---|---|---|
| 17 نوفمبر 2026 | إنشاء الملف؛ إكمال المرحلة التمهيدية A–I؛ تحديد TASK-001 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-001 بعد قرار المالك؛ تحديث ADR والقرارات والتعارضات؛ إضافة تقرير المهمة وLOCAL_SETUP | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-002؛ إنشاء مشروع Laravel 13 والاختبارات والالتزام Git الأولي؛ إضافة تقرير المهمة | فريق UMS الهندسي |
| 17 نوفمبر 2026 | اعتماد E-004 (بوابة التصميم)؛ تنفيذ TASK-003: Livewire 4 وTailwind 4 ورموز التصميم ومكوّنات Blade وصفحة المعاينة؛ إغلاق R-005/R-009 وفتح R-010 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-004: نواة المؤسسة والحرم (جداول وإعدادات مؤرخة وأحرم ونطاق استعلام ومؤسسة نشطة واحدة)؛ 16 اختباراً ناجحاً؛ تحديد TASK-005 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-005: هوية وأدوار ونطاقات وتفويض وتدقيق؛ عزل مدير الحرم + فشل مغلق؛ 29 اختباراً ناجحاً؛ تحديد TASK-006 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-006: كليات وأقسام وإسنادات مؤرخة للحرم والكلية وتكليفات العميد/رئيس القسم؛ عزل نطاق العميد ورئيس القسم؛ 38 اختباراً ناجحاً؛ تحديد TASK-007 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-007: موارد مكانية (مباني/طوابق/قاعات) بسعة وتجهيزات مؤرخة وعزل نطاق الحرم؛ 47 اختباراً ناجحاً؛ تحديد TASK-008 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-008: قاعدة الوثائق والموافقات (بيانات وصفية فقط، تخزين خاص، نسخ وموافقات غير قابلة للتعديل/الحذف)؛ إغلاق المرحلة 3؛ 58 اختباراً ناجحاً؛ تحديد TASK-009 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-009: دليل الأشخاص والمعرفات (تشفير + HMAC، أنواع فارغة، وصول fail-closed وتدقيق)؛ بدء المرحلة 4؛ 69 اختباراً ناجحاً؛ تحديد TASK-010 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-010: الموظفون وهيئة التدريس والتعيينات (تبعية إدارية واحدة، إشراف موازٍ، تفويض HR وتدقيق)؛ إغلاق المرحلة 4؛ 81 اختباراً ناجحاً؛ تحديد TASK-011 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-011: أساس القبول (دورات، طلبات بمفتاح idempotency، مراجعات وقرارات غير قابلة للتعديل مع تدقيق)؛ بدء المرحلة 5؛ 89 اختباراً ناجحاً؛ تحديد TASK-012 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-012: التقويم والبرامج (سنوات/fصول بمنع تداخل، كلية رئيسية واحدة، مشاركات ونسخ مؤرخة، تفويض أكاديمي وتدقيق)؛ 99 اختباراً ناجحاً؛ تحديد TASK-013 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-013: تحويل المتقدم إلى طالب (idempotent، رقم جامعي مُدخل، تحاق برنامج، تاريخ حالات غير قابل للتعديل، عزل بالكلية)؛ 109 اختبارات ناجحة؛ تحديد TASK-014 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-014: كتالوج المقررات وخطط الدراسة (نسخ معتمدة immutable، متطلبات بلا دورات، ربط طالب بخطة معتمدة، لا ساعات مُفترضة)؛ 117 اختباراً ناجحاً؛ تحديد TASK-015 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-015: الطرح والشعب والجداول (قاعة/وقت بلا تعارض، إسناد مدرس بقرار، سعة بلا قيمة مُفترضة، تفويض وتدقيق)؛ 124 اختباراً ناجحاً؛ تحديد TASK-016 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-016: تسجيل الطلاب (ذاتي/إداري بمفتاح idempotency، منع التكرار والتعارض، متطلب بنيوي، انسحاب موثّق بلا حذف)؛ 133 اختباراً ناجحاً؛ تحديد TASK-017 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-017: الحضور وعناصر التقييم (جلسات وسجلات مقيّدة بالشعبة مع سجل تغييرات، عناصر بلا حدود مُفترضة، تفويض المدرس المسند)؛ 141 اختباراً ناجحاً؛ تحديد TASK-018 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-018: الدرجات والاعتماد والنشر والكشف (فصل الإدخال عن الاعتماد، لقطات منشورة/كشف/تخرج غير قابلة للتعديل، سحب مُدقَّق، بلا قواعد تقدير مُفترضة)؛ 151 اختباراً ناجحاً؛ تحديد TASK-019 | فريق UMS الهندسي |
| 17 نوفمبر 2026 | تنفيذ TASK-019: سياسات الرسوم والفواتير (DECIMAL+عملة عبر BCMath، سياسات مؤرخة/مُنسَّخة بفصل الاعتماد، فواتير بلقطات بنود وبأرقام فريدة/idempotency، إلغاء موثّق بلا حذف)؛ 157 اختباراً ناجحاً؛ تحديد TASK-020 | فريق UMS الهندسي |
| 21 نوفمبر 2026 | تنفيذ TASK-020: التحصيل والإيصالات والردود والتسوية (قبض idempotent، إيصالات لقطات، تخصيصات بلا تجاوز، رد بفصل مهام وتسوية عكسية، ورديات ومطابقة بنكية، كلها مُدقَّقة وغير قابلة للحذف)؛ 164 اختباراً ناجحاً؛ تحديد TASK-021 | فريق UMS الهندسي |
| 21 نوفمبر 2026 | تنفيذ TASK-021: الطلبات والإشعارات والمستندات (مسارات مُنسَّخة بترحيل موثّق للطلبات الجارية، أحداث append-only، قوالب بإصدارات وفصل مهام، إشعارات بمستلم واحد وقناة داخلية، محاولات إرسال آمنة، إعلانات بجمهور نطاقي وجدولة)؛ 172 اختباراً ناجحاً؛ تحديد TASK-022 | فريق UMS الهندسي |
