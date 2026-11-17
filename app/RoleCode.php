<?php

namespace App;

enum RoleCode: string
{
    case SystemAdmin = 'system_admin';
    case IdentityAdmin = 'identity_admin';
    case UniversityPresident = 'university_president';
    case AcademicLeadership = 'academic_leadership';
    case FinancialLeadership = 'financial_leadership';
    case AdmissionsRegistrationDirector = 'admissions_registration_director';
    case AdmissionsOfficer = 'admissions_officer';
    case RegistrationOfficer = 'registration_officer';
    case CollegeDean = 'college_dean';
    case DepartmentHead = 'department_head';
    case ProgramDirector = 'program_director';
    case FacultyMember = 'faculty_member';
    case CampusManager = 'campus_manager';
    case FacilitiesOfficer = 'facilities_officer';
    case HrDirector = 'hr_director';
    case HrOfficer = 'hr_officer';
    case FinanceDirector = 'finance_director';
    case Cashier = 'cashier';
    case FinancialAuditor = 'financial_auditor';
    case GraduateStudiesCoordinator = 'graduate_studies_coordinator';
    case Librarian = 'librarian';
    case Student = 'student';
    case Applicant = 'applicant';
    case InternalAuditor = 'internal_auditor';
    case ReportReader = 'report_reader';

    public function label(): string
    {
        return trans('roles.'.$this->value);
    }
}
