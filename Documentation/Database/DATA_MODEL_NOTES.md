# ملاحظات نموذج البيانات (ما قبل ERD النهائي) — UMS
**الإصدار:** 1.0 (مبدئي) — **التاريخ:** 17 نوفمبر 2026  
**تنبيه:** هذا تلخيص مفاهيمي للمصادر، **ليس** ERD منطقياً أو فعلياً. Migrations تبدأ في المرحلة 2 بعد إغلاق القرارات الحاجزة.

---

## 1. القواعد العامة المستخرجة (الوثيقة 6)
- InnoDB، `utf8mb4`، اختبار Collation على بحث عربي فعلي قبل الاعتماد.
- مفاتيح داخلية موحدة (BIGINT أو ULID بقرار واحد)؛ معرف عام غير متسلسل عند الحاجة؛ الرقم الجامعي/الوظيفي **رمز عمل لا PK**.
- `snake_case` للجداول والأعمدة؛ القيم المرجعية المترجمة في ملفات اللغة.
- FK/Unique/Indexes إلزامية؛ لا اعتماد على التحقق الأمامي وحده.
- الأموال `DECIMAL` + رمز عملة (لا FLOAT/DOUBLE).
- توحيد تخزين التوقيت وعرضه حسب منطقة الجامعة.
- الملفات في private storage؛ DB يحفظ metadata فقط.
- لا JSON بديلاً عن علاقة تحتاج بحثاً/تقارير/قيداً.
- لا soft-delete تلقائي للمعمليات الأكاديمية/المالية؛ حالة/أرشفة/عكس.

---

## 2. عناقيد الكيانات

### 2.1 النواة والهوية
`institutions`، `institution_settings`، `campuses`، `campus_college_assignments`، `buildings/floors/rooms`، `organizational_units`، `colleges`، `departments`، `people`، `person_identifiers`، `users`، `roles/permissions`، `role_permissions`، `user_role_assignments`، `delegations`، `employees`، `faculty_profiles`، `employment_assignments`، `audit_logs`، `documents/document_versions`، `approvals`.

### 2.2 الأكاديمي
`academic_years/terms`، `academic_programs`، `program_college_participations`، `program_versions`، `study_plan_versions`، `study_plan_courses`، `courses/course_versions`، `course_prerequisites`، `students`، `student_program_enrollments`، `student_plan_assignments`، `student_status_history`، `student_course_equivalencies`، `course_offerings`، `course_sections`، `section_meetings`، `teaching_assignments`، `course_enrollments`، `attendance_sessions/records`، `assessment_components/scores`، `grade_submissions/approvals`، `published_results`، `student_transcripts`، `graduation_audits`.

### 2.3 القبول والطالب
`admission_cycles`، `admission_program_availability`، `applications`، `application_choices`، `application_documents`، `admission_reviews`، `admission_decisions`، `applicant_events`، `students`، `student_contacts`، `student_guardians` (عند الحاجة)، `student_documents`.

### 2.4 المالية الطلابية
`currencies`، `fee_policies`، `fee_items`، `fee_policy_items`، `student_invoices`، `invoice_lines`، `student_payments`، `payment_allocations`، `receipts`، `refunds`، `financial_adjustments`، `cashboxes`، `cash_sessions`، `bank_reconciliations`.  
دفتر أستاذ/مشتريات/رواتب كاملة = نطاق محاسبي مستقل، **ليس** افتراضاً في الإصدار الأول (P-024).

### 2.5 خدمات ومرحلية
- بحث: `postgraduate_records`، `supervisor_assignments`، `research_proposals`، `proposal_versions`، `research_milestones`، `progress_reports`، `defense_committees`، `defense_sessions`، `thesis_submissions`.
- مكتبة: `library_branches`، `bibliographic_records`، `item_copies`، `circulation_transactions`، `reservations`، `memberships`.
- طلبات: `service_request_types`، `service_requests`، `attachments`، `workflow_steps`، `request_events`.
- إشعارات: `notification_templates`، `notifications`، `delivery_attempts`، `announcements`.
- صيانة: `maintenance_tickets`، `room_blocks`، `room_reservations`، `asset_register` (عند اعتماد إدارة الأصول).

---

## 3. القيود الأساسية الواجب تنفيذها على مستوى DB
1. رقم الطالب ورقم الموظف لا يُعاد استخدامهما.
2. البرنامج له كلية رئيسية واحدة، ومشاركات متعددة ممكنة.
3. القسم يتبع كلية واحدة في فترة سريان.
4. المقرر المركزي لا يُنسخ لكل برنامج.
5. لا تسجيلين نشطين للطالب في الشعبة نفسها (unique جزئي أو قيد معتمد لاحقاً).
6. لا حجز متعارض لقاعة/مدرس في الزمن نفسه — قيد + اختبار تزامن.
7. لا نشر درجات غير معتمدة؛ لا تجاوز حدود عناصر التقييم.
8. أرقام الفواتير والإيصالات فريدة؛ لا يعاد استخدام الملغى.
9. كل رد/عكس مرتبط بالحركة الأصلية.
10. كل ملف له checksum وmetadata وصلاحية تنزيل.

---

## 4. أنماط التاريخية المطلوبة
| النمط | أمثلة |
|---|---|
| علاقة مؤرخة | `campus_college_assignments`، `employment_assignments`، `user_role_assignments`، `program_college_participations` |
| إصدار كيان | `program_versions`، `study_plan_versions`، `course_versions`، `fee_policies` |
| لقطة زمنية | ربط الطالب بإصدار خطة؛ حفظ ما انطبق من مقرر/رسوم/سياسة في الفترة |
| سجل حدث | `student_status_history`، `applicant_events`، `request_events`، `audit_logs` |
| عكس موثق | `refunds`، `financial_adjustments`، اعتراضات الدرجات |

---

## 5. نقاط تنتظر الحسم قبل ERD الفعلي
معرفات الأشخاص والوثائق • الدراسة المتزامنة في أكثر من برنامج • نظام الساعات/الوحدات • التبعية الرسمية للأقسام • ملكية المقرر • قواعد المعدل والتقدير والمعادلة • العملة والضرائب والتقسيط • الاحتفاظ بالبيانات.  
انظر `Decisions/PENDING_DECISIONS_REGISTER.md` §3 و`Decisions/CONFLICT_REGISTER.md` C-002.

---

## 6. مرجع
`docs/06_data_model_dictionary.md`، `diagrams/03_conceptual_erd.mmd`، `MASTER_PLAN_AR.md` §6.
