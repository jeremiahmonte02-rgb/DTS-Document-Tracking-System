<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DepartmentAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. HARMONIZED PROTOTYPE DEPARTMENTS & UC CODES
        $departments = [
            ['name' => 'Executive Office', 'code' => 'OP', 'description' => 'Office of the President / Executive Office'],
            ['name' => 'Finance Department', 'code' => 'AFO', 'description' => 'Accounting and Finance Office'],
            ['name' => 'HR Department', 'code' => 'HRMO', 'description' => 'Human Resource Management Office'],
            ['name' => 'IT Department', 'code' => 'MISO', 'description' => 'Management Information Systems Office'],
            ['name' => 'Legal Department', 'code' => 'LEGAL', 'description' => 'Legal Affairs and Compliance'],
            ['name' => 'Marketing Department', 'code' => 'MKTG', 'description' => 'Communications and Marketing Department'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operations and Facilities Support Office'],
            ['name' => 'Customer Service', 'code' => 'CS', 'description' => 'Student and Public Help Services'],
            ['name' => 'Facilities', 'code' => 'FAC', 'description' => 'Physical Plant and Facilities Management'],
            ['name' => 'Central Services', 'code' => 'CSERVI', 'description' => 'Mailroom, Logistics, and Document Distribution'],
            ['name' => 'College of Computing and Information Sciences', 'code' => 'CCIS', 'description' => 'Academic Information Technology Faculty'],
            ['name' => 'Quality Assurance Office', 'code' => 'QAO', 'description' => 'Institutional Accreditation and Standards'],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(['code' => $dept['code']], [
                'name' => $dept['name'],
                'description' => $dept['description'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 💡 CHANGE 1: Pluck by unique 'code' instead of 'name'
        $deptIds = DB::table('departments')->pluck('id', 'code');

        // 2. SEED CORE REQUISITE INTERACTION ROLES
        $roles = [
            ['id' => 1, 'name' => 'Administrator', 'description' => 'Full tracking override and system control'],
            ['id' => 2, 'name' => 'Department User', 'description' => 'Standard action access (Upload, Route, Process)'],
            ['id' => 3, 'name' => 'Auditor', 'description' => 'Global read-only trail review access'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['id' => $role['id']], [
                'name' => $role['name'],
                'description' => $role['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. SEED THE 10 EXACT SAMPLE USERS FROM THE PROTOTYPE MAIN.JS
        // 💡 CHANGE 2: Update the key lookups to match the codes (OP, AFO, HRMO, MISO, etc.)
        $users = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['OP'], // Executive Office
                'role_id' => 1,
                'status' => 'active',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['AFO'], // Finance Department
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['HRMO'], // HR Department
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Robert Wilson',
                'email' => 'robert.wilson@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['MISO'], // IT Department
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'David Martinez',
                'email' => 'david.martinez@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['LEGAL'], // Legal Department
                'role_id' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Jennifer Lee',
                'email' => 'jennifer.lee@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['MKTG'], // Marketing Department
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['OPS'], // Operations
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Amanda White',
                'email' => 'amanda.white@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['CS'], // Customer Service
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['AFO'], // Finance Department
                'role_id' => 2,
                'status' => 'inactive',
            ],
            [
                'name' => 'Jessica Taylor',
                'email' => 'jessica.taylor@uc.edu.ph',
                'password' => Hash::make('P@ssword2026'),
                'department_id' => $deptIds['HRMO'], // HR Department
                'role_id' => 3,
                'status' => 'active',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(['email' => $user['email']], [
                'name' => $user['name'],
                'password' => $user['password'],
                'department_id' => $user['department_id'],
                'role_id' => $user['role_id'],
                'status' => $user['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}