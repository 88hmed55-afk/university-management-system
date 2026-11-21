# 6. نموذج البيانات وقاموس الكيانات

## قواعد عامة
- InnoDB، ترميز `utf8mb4`، وتجربة Collation على بحث عربي فعلي قبل اعتماده.
- مفاتيح داخلية موحدة (`BIGINT` أو ULID وفق قرار واحد)، مع معرف عام غير متسلسل للروابط التي تظهر خارج النظام عند الحاجة. الرقم الجامعي/الوظيفي رمز عمل وليس مفتاحاً أساسياً.
- أسماء الجداول والأعمدة `snake_case`، والقيم المرجعية المترجمة في ملفات اللغة.
- FK وUnique وIndexes إلزامية حسب العلاقات؛ لا تعتمد على التحقق الأمامي وحده.
- أموال `DECIMAL` مع رمز العملة؛ لا FLOAT/DOUBLE.
- تخزين التوقيت موحد وعرضه حسب منطقة الجامعة.
- لا تخزن ملفات الطالب داخل MySQL؛ خزّن metadata في DB والملف في private storage.
- لا تضع JSON بديلاً عن علاقة تحتاج بحثاً أو تقارير أو قيداً.
- لا soft-delete تلقائي للعمليات الأكاديمية والمالية؛ استخدم الحالة/الأرشفة والعكس.

## نواة المؤسسة والهوية
| الجدول المقترح | الغرض | القيود والعلاقات |
|---|---|---|
| institutions | تعريف الجامعة | صف نشط واحد في الإصدار الأول |
| institution_settings | إعدادات عامة | مفتاح/نوع/قيمة/تاريخ سريان |
| campuses | الأحرم | رمز فريد؛ لا حذف مع تاريخ |
| campus_college_assignments | مواقع الكليات | college + campus + dates |
| buildings / floors / rooms | الموارد المكانية | تسلسل حرم-مبنى-طابق-قاعة؛ سعة وتجهيزات |
| organizational_units | إدارات ووحدات | parent اختياري، نوع، تاريخ سريان |
| colleges | الكليات | رمز، وحدة، عميد بتكليف مؤرخ |
| departments | الأقسام | كلية مالكة في الفترة |
| people | سجل الشخص | مصدر موحد للشخص |
| person_identifiers | معرفات رسمية | نوع/قيمة محمية؛ وصول مقيد |
| users | حساب الدخول | person_id، username/email، status، MFA |
| roles / permissions | الأدوار والأذونات | codes فريدة |
| role_permissions | ربط الدور بالإذن | unique pair |
| user_role_assignments | أدوار المستخدم ونطاقاتها | scope_type/id + dates |
| delegations | التفويض | المفوض/البديل/النطاق/المهام/المدة/المعتمد |
| employees | ملف الموظف | person_id، employee_no فريد |
| faculty_profiles | بيانات هيئة التدريس | employee_id، التخصص/الرتبة |
| employment_assignments | التعيينات | manager، supervisor، unit، campus، dates |
| audit_logs | سجل التدقيق | actor/action/entity/time/correlation_id |
| documents / document_versions | وثائق ونسخها | owner/type/checksum/storage_key/uploader |
| approvals | قرارات وموافقات | subject, decision, approver, time, reference |

## البيانات الأكاديمية
| الجدول | الغرض والقيود |
|---|---|
| academic_years / academic_terms | السنوات والفترات وتواريخها |
| academic_programs | رمز ومستوى وكلية رئيسية وحالة |
| program_college_participations | كليات مشاركة بفترة ونوع مشاركة |
| program_versions | نسخ اعتماد تعريف البرنامج |
| study_plan_versions | نسخة الخطة وحالة اعتمادها وسريانها |
| study_plan_courses | مقررات الخطة ونوعها وساعاتها ومستواها ومتطلباتها |
| courses / course_versions | كتالوج المقرر وإصدارات التوصيف |
| course_prerequisites | متطلبات/مكافئات وشروطها |
| students | الرقم الجامعي والحالة الأساسية والشخص |
| student_program_enrollments | التحاق الطالب بالبرنامج والدفعة والفترة |
| student_plan_assignments | ربط الطالب بنسخة خطة محددة |
| student_status_history | تاريخ التأجيل والعودة والانسحاب وغيرها |
| student_course_equivalencies | معادلة/استبدال مقررات بقرار |
| course_offerings | طرح مقرر في فصل |
| course_sections | الشعب والسعة والحرم |
| section_meetings | اجتماعات الشعبة والوقت والقاعة |
| teaching_assignments | المدرسون المسندون إلى الشعب |
| course_enrollments | تسجيل الطالب في الشعبة؛ منع التكرار النشط |
| attendance_sessions / attendance_records | جلسات الحضور وسجلات الطلبة |
| assessment_components / assessment_scores | عناصر التقييم والدرجات |
| grade_submissions / grade_approvals | تقديم الدرجات واعتمادها |
| published_results | نسخة النتيجة المنشورة وتاريخها |
| student_transcripts | نسخ كشف الدرجات الرسمية |
| graduation_audits | فحص متطلبات التخرج على نسخة الخطة |

## القبول والطالب
`admission_cycles`, `admission_program_availability`, `applications`, `application_choices`, `application_documents`, `admission_reviews`, `admission_decisions`, `applicant_events`, `students`, `student_contacts`, `student_guardians` عند الحاجة، `student_documents`.

تحويل متقدم إلى طالب يجب أن يكون idempotent: إذا تكررت المحاولة لا ينشأ شخص أو طالب آخر. يحتفظ التطبيق بمعرف طلب القبول الأصلي.

## المالية الطلابية
`currencies`, `fee_policies`, `fee_items`, `fee_policy_items`, `student_invoices`, `invoice_lines`, `student_payments`, `payment_allocations`, `receipts`, `refunds`, `financial_adjustments`, `cashboxes`, `cash_sessions`, `bank_reconciliations`.

هذه تغطي التحصيل والاستحقاقات الطلابية. إذا أرادت الجامعة دفتر أستاذ ومشتريات وموردين وموازنة وأصولاً ورواتب كاملة، يضاف نطاق محاسبي مستقل مع Chart of Accounts وJournal Entries وGeneral Ledger ومراكز تكلفة؛ لا نفترض أن ذلك كله مطلوب تلقائياً.

## الدراسات العليا والمكتبة والخدمات
- البحث: postgraduate_records، supervisor_assignments، research_proposals، proposal_versions، research_milestones، progress_reports، defense_committees، defense_sessions، thesis_submissions.
- المكتبة: library_branches، bibliographic_records، item_copies، circulation_transactions، reservations، memberships.
- الطلبات: service_request_types، service_requests، attachments، workflow_steps، request_events.
- الإشعارات: notification_templates، notifications، delivery_attempts، announcements.
- الصيانة: maintenance_tickets، room_blocks، room_reservations، asset_register عند اعتماد إدارة الأصول.

## قيود أساسية
- رقم الطالب ورقم الموظف لا يعاد استخدامهما.
- البرنامج له كلية رئيسية واحدة، وله مشاركات متعددة ممكنة.
- القسم يتبع كلية واحدة في فترة سريان.
- المقرر المركزي لا ينسخ لكل برنامج.
- لا تسجيلين نشطين للطالب في الشعبة نفسها.
- لا حجز متعارض لقاعة/مدرس في الزمن نفسه؛ اختبر القيود والتزامن.
- لا تنشر درجات غير معتمدة ولا تتجاوز حدود عناصر التقييم.
- أرقام الفواتير والإيصالات فريدة ولا يعاد استخدام الملغى.
- كل حركة رد/عكس ترتبط بالحركة الأصلية.
- كل ملف له checksum وmetadata وصلاحية تنزيل.

## نقاط تنتظر الحسم عند ERD الفعلي
معرفات الأشخاص والوثائق؛ الدراسة المتزامنة في أكثر من برنامج؛ نظام الساعات/الوحدات؛ التبعية الرسمية للأقسام؛ ملكية المقرر؛ قواعد المعدل والتقدير والمعادلة؛ العملة والضرائب والتقسيط؛ الاحتفاظ بالبيانات.
