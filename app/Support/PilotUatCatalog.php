<?php

namespace App\Support;

use App\RoleCode;

/**
 * Role-based UAT scenarios transcribed from the approved blueprint. They are a
 * checklist for human sign-off, not an automated approval and not a new policy.
 */
final class PilotUatCatalog
{
    /**
     * @return array<int, array{id: string, role: string, scenario: string, expected: string, source: string}>
     */
    public static function scenarios(): array
    {
        $reports = 'UMS-Master-Blueprint-v1.0/docs/11_testing_acceptance.md';
        $roles = 'UMS-Master-Blueprint-v1.0/docs/10_reports_analytics.md';

        return [
            [
                'id' => 'UAT-PRES-01',
                'role' => RoleCode::UniversityPresident->value,
                'scenario' => 'مقارنة لوحة الجامعة بتقرير التفاصيل لنطاق المؤسسة',
                'expected' => 'تطابق الأرقام؛ لا تصدير خارج نطاق المستخدم',
                'source' => $reports.' s24',
            ],
            [
                'id' => 'UAT-PRES-02',
                'role' => RoleCode::UniversityPresident->value,
                'scenario' => 'تشغيل تقرير معتمد يشمل المسودات وآخر لا يشملها',
                'expected' => 'المسودات لا تدخل التقرير الذي لم يصرّح بإدخالها',
                'source' => $reports.' s24',
            ],
            [
                'id' => 'UAT-ACAD-01',
                'role' => RoleCode::AcademicLeadership->value,
                'scenario' => 'طالب على خطة V1 بعد نشر V2',
                'expected' => 'يبقى الطالب على خطته المعتمدة',
                'source' => $reports.' s18',
            ],
            [
                'id' => 'UAT-ACAD-02',
                'role' => RoleCode::AcademicLeadership->value,
                'scenario' => 'محاولة نشر نتائج غير معتمدة',
                'expected' => 'لا تُنشر النتائج قبل الاعتماد',
                'source' => $reports.' s18',
            ],
            [
                'id' => 'UAT-CAMPUS-01',
                'role' => RoleCode::CampusManager->value,
                'scenario' => 'تغيير معرّف الحرم في الرابط إلى حرم آخر',
                'expected' => 'لا وصول إلى حرم B؛ الفشل مغلق',
                'source' => $reports.' s14',
            ],
            [
                'id' => 'UAT-CAMPUS-02',
                'role' => RoleCode::CampusManager->value,
                'scenario' => 'محاولة تعديل نتيجة أو رسوم من حساب مدير الحرم',
                'expected' => 'يُرفض التعديل؛ الصلاحية الخادمية هي المرجع',
                'source' => $reports.' s14',
            ],
            [
                'id' => 'UAT-DEAN-01',
                'role' => RoleCode::CollegeDean->value,
                'scenario' => 'مراجعة لوحة الكلية: الطلاب والأقسام والشعب ونتائج المقررات',
                'expected' => 'تظهر بيانات الكلية فقط ضمن نطاق العميد',
                'source' => $roles.' s9',
            ],
            [
                'id' => 'UAT-HEAD-01',
                'role' => RoleCode::DepartmentHead->value,
                'scenario' => 'مراجعة لوحة القسم: المقررات والشعب والمدرسون والدرجات المطلوب تسليمها',
                'expected' => 'تظهر بيانات القسم فقط ضمن نطاق رئيس القسم',
                'source' => $roles.' s10',
            ],
            [
                'id' => 'UAT-ADM-01',
                'role' => RoleCode::AdmissionsOfficer->value,
                'scenario' => 'إرسال طلب مرتين بسبب انقطاع الشبكة',
                'expected' => 'لا يتكرر الطلب؛ idempotency',
                'source' => $reports.' s16',
            ],
            [
                'id' => 'UAT-ADM-02',
                'role' => RoleCode::AdmissionsOfficer->value,
                'scenario' => 'تحويل متقدم بقبول غير معتمد',
                'expected' => 'لا يُنشأ طالب',
                'source' => $reports.' s16',
            ],
            [
                'id' => 'UAT-FIN-01',
                'role' => RoleCode::FinanceDirector->value,
                'scenario' => 'تكرار طلب دفع أو webhook',
                'expected' => 'لا يتكرر القبض؛ الرد مرتبط بالأصل',
                'source' => $reports.' s22',
            ],
            [
                'id' => 'UAT-FIN-02',
                'role' => RoleCode::FinanceDirector->value,
                'scenario' => 'محاولة غير مالي فتح كشف حساب',
                'expected' => 'يُرفض الوصول',
                'source' => $reports.' s22',
            ],
            [
                'id' => 'UAT-STU-01',
                'role' => RoleCode::Student->value,
                'scenario' => 'مراجعة لوحة الطالب: الجدول والإشعارات والنتائج المنشورة والرسوم المستحقة',
                'expected' => 'تظهر بيانات الطالب نفسه فقط',
                'source' => $roles.' s15',
            ],
            [
                'id' => 'UAT-STU-02',
                'role' => RoleCode::Student->value,
                'scenario' => 'تشغيل تقرير فواتير الطالب المعتمد',
                'expected' => 'لا تظهر فواتير مسودة أو فواتير طالب آخر',
                'source' => $reports.' s24',
            ],
            [
                'id' => 'UAT-FAC-01',
                'role' => RoleCode::FacultyMember->value,
                'scenario' => 'عرض الشعب والدرجات',
                'expected' => 'المدرس يرى شعبه فقط',
                'source' => $reports.' s14',
            ],
            [
                'id' => 'UAT-FAC-02',
                'role' => RoleCode::FacultyMember->value,
                'scenario' => 'مراجعة لوحة عضو هيئة التدريس: الشعب والحضور والدرجات المعلقة',
                'expected' => 'تظهر بياناته فقط ضمن نطاق الشعبة',
                'source' => $roles.' s16',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function roles(): array
    {
        return array_values(array_unique(array_column(self::scenarios(), 'role')));
    }
}
