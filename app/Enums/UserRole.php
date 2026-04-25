<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CompanyAdmin = 'company_admin';
    case HrManager = 'hr_manager';
    case DepartmentManager = 'department_manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::CompanyAdmin => 'Company Admin',
            self::HrManager => 'HR Manager',
            self::DepartmentManager => 'Department Manager',
            self::Employee => 'Employee',
        };
    }
}
