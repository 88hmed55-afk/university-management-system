<?php

namespace Database\Seeders;

use App\DataScopeCode;
use App\Models\DataScope;
use App\Models\Role;
use App\RoleCode;
use Illuminate\Database\Seeder;

class IdentityReferenceSeeder extends Seeder
{
    /**
     * Reference data copied from the approved blueprint (docs/05_roles_permissions.md).
     * No permission matrix is granted here; sensitive powers are never inferred from a role name.
     */
    public function run(): void
    {
        foreach (DataScopeCode::cases() as $scope) {
            DataScope::firstOrCreate(['code' => $scope->value]);
        }

        foreach ($this->roleScopeMap() as $roleCode => $scopeCode) {
            Role::firstOrCreate([
                'code' => $roleCode,
            ], [
                'data_scope_id' => $scopeCode === null
                    ? null
                    : DataScope::where('code', $scopeCode)->value('id'),
                'is_system' => true,
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function roleScopeMap(): array
    {
        return [
            RoleCode::SystemAdmin->value => null,
            RoleCode::IdentityAdmin->value => DataScopeCode::Institution->value,
            RoleCode::UniversityPresident->value => DataScopeCode::Institution->value,
            RoleCode::AcademicLeadership->value => DataScopeCode::Institution->value,
            RoleCode::FinancialLeadership->value => DataScopeCode::Institution->value,
            RoleCode::AdmissionsRegistrationDirector->value => DataScopeCode::Institution->value,
            RoleCode::AdmissionsOfficer->value => null,
            RoleCode::RegistrationOfficer->value => null,
            RoleCode::CollegeDean->value => DataScopeCode::College->value,
            RoleCode::DepartmentHead->value => DataScopeCode::Department->value,
            RoleCode::ProgramDirector->value => DataScopeCode::Program->value,
            RoleCode::FacultyMember->value => DataScopeCode::Section->value,
            RoleCode::CampusManager->value => DataScopeCode::Campus->value,
            RoleCode::FacilitiesOfficer->value => DataScopeCode::Campus->value,
            RoleCode::HrDirector->value => DataScopeCode::Institution->value,
            RoleCode::HrOfficer->value => null,
            RoleCode::FinanceDirector->value => DataScopeCode::Institution->value,
            RoleCode::Cashier->value => null,
            RoleCode::FinancialAuditor->value => null,
            RoleCode::GraduateStudiesCoordinator->value => DataScopeCode::Program->value,
            RoleCode::Librarian->value => null,
            RoleCode::Student->value => DataScopeCode::Self->value,
            RoleCode::Applicant->value => DataScopeCode::Self->value,
            RoleCode::InternalAuditor->value => null,
            RoleCode::ReportReader->value => null,
        ];
    }
}
