<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\UserRole;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create all roles from UserRole enum
        $roles = [
            UserRole::ADMIN->value,
            UserRole::FACULTY_DEAN->value,
            UserRole::QUALITY_MANAGER->value,
            UserRole::RESPONDENT->value,
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Define all permissions used in the application
        $allPermissions = [
            // Admin Dashboard permissions
            'dashboard.admin.view',
            'dashboard.admin.stats',
            'dashboard.admin.chart',
            
            // Faculty Dean Dashboard permissions
            'dashboard.faculty_dean.view',
            'dashboard.faculty_dean.stats',
            'dashboard.faculty_dean.chart',
            
            // Quality Manager Dashboard permissions
            'dashboard.quality_manager.view',
            'dashboard.quality_manager.stats',
            'dashboard.quality_manager.chart',
            
            // Respondent Dashboard permissions
            'dashboard.respondent.view',
            'dashboard.respondent.stats',
            'dashboard.respondent.chart',
            
            // Faculty permissions
            'faculty.view',
            'faculty.dataTable',
            'faculty.stats',
            'faculty.delete',
            
            // Faculty Member permissions
            'facultyMember.view',
            'facultyMember.dataTable',
            'facultyMember.select',
            'facultyMember.stats',
            'facultyMember.delete',
            
            // Course permissions
            'course.view',
            'course.dataTable',
            'course.stats',
            'course.delete',
            
            // Enrollment permissions
            'enrollment.view',
            'enrollment.import',
            'enrollment.dataTable',
            'enrollment.progress',
            'enrollment.stats',
            'enrollment.create',
            'enrollment.delete',
            
            // Student permissions
            'student.view',
            'student.show',
            'student.dataTable',
            'student.search',
            'student.stats',
            'student.delete',
            
            // Question permissions
            'question.view',
            'question.create',
            'question.dataTable',
            'question.stats',
            'question.delete',
            
            // Questionnaire Template permissions
            'questionnaireTemplate.view',
            'questionnaireTemplate.create',
            'questionnaireTemplate.dataTable',
            'questionnaireTemplate.stats',
            'questionnaireTemplate.delete',
            
            // Deployed Questionnaire permissions (using hyphenated format as in controller)
            'deployed-questionnaire.view',
            'deployed-questionnaire.create',
            'deployed-questionnaire.edit',
            'deployed-questionnaire.dataTable',
            'deployed-questionnaire.stats',
            'deployed-questionnaire.delete',
            'deployed-questionnaire.export',
            
            // User Management permissions
            'user.view',
            'user.create',
            'user.dataTable',
            'user.stats',
            'user.delete',
            
            // Response permissions
            'response.view',
            'response.create',
            'response.dataTable',
            'response.stats',
            'response.delete',
            
            // Session permissions
            'session.view',
            'session.create',
            'session.dataTable',
            'session.stats',
            'session.delete',
        ];

        // Create all permissions
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Get role instances
        $adminRole = Role::findByName(UserRole::ADMIN->value);
        $facultyDeanRole = Role::findByName(UserRole::FACULTY_DEAN->value);
        $qualityManagerRole = Role::findByName(UserRole::QUALITY_MANAGER->value);
        $respondentRole = Role::findByName(UserRole::RESPONDENT->value);

        // Admin gets all permissions
        $adminRole->syncPermissions($allPermissions);

        // Faculty Dean permissions
        $facultyDeanPermissions = [
            'dashboard.faculty_dean.view',
            'dashboard.faculty_dean.stats',
            'dashboard.faculty_dean.chart',
            'faculty.view',
            'faculty.dataTable',
            'faculty.stats',
            'course.view',
            'course.dataTable',
            'course.stats',
            'enrollment.view',
            'enrollment.dataTable',
            'enrollment.stats',
            'student.view',
            'student.show',
            'student.dataTable',
            'student.search',
            'student.stats',
            'question.view',
            'question.dataTable',
            'question.stats',
            'questionnaireTemplate.view',
            'questionnaireTemplate.dataTable',
            'questionnaireTemplate.stats',
            'deployed-questionnaire.view',
            'deployed-questionnaire.dataTable',
            'deployed-questionnaire.stats',
            'response.view',
            'response.dataTable',
            'response.stats',
        ];
        $facultyDeanRole->syncPermissions($facultyDeanPermissions);

        // Quality Manager permissions
        $qualityManagerPermissions = [
            'dashboard.quality_manager.view',
            'dashboard.quality_manager.stats',
            'dashboard.quality_manager.chart',
            'faculty.view',
            'faculty.dataTable',
            'faculty.stats',
            'facultyMember.view',
            'facultyMember.dataTable',
            'facultyMember.stats',
            'course.view',
            'course.dataTable',
            'course.stats',
            'enrollment.view',
            'enrollment.dataTable',
            'enrollment.stats',
            'student.view',
            'student.show',
            'student.dataTable',
            'student.search',
            'student.stats',
            'student.delete',
            'question.view',
            'question.create',
            'question.dataTable',
            'question.stats',
            'question.delete',
            'questionnaireTemplate.view',
            'questionnaireTemplate.create',
            'questionnaireTemplate.dataTable',
            'questionnaireTemplate.stats',
            'questionnaireTemplate.delete',
            'deployed-questionnaire.view',
            'deployed-questionnaire.create',
            'deployed-questionnaire.edit',
            'deployed-questionnaire.dataTable',
            'deployed-questionnaire.stats',
            'deployed-questionnaire.delete',
            'deployed-questionnaire.export',
            'response.view',
            'response.dataTable',
            'response.stats',
            'user.view',
            'user.create',
            'user.dataTable',
            'user.stats',
        ];
        $qualityManagerRole->syncPermissions($qualityManagerPermissions);

        // Respondent permissions (limited access)
        $respondentPermissions = [
            'dashboard.respondent.view',
            'dashboard.respondent.stats',
            'deployed-questionnaire.view',
            'response.create',
            'response.view',
        ];
        $respondentRole->syncPermissions($respondentPermissions);
    }
}