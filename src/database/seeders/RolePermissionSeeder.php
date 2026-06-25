<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Create Documents',        'slug' => 'documents.create',       'description' => 'Upload and register new documents'],
            ['name' => 'Receive Documents',       'slug' => 'documents.receive',      'description' => 'Receive incoming documents at department'],
            ['name' => 'Complete Documents',      'slug' => 'documents.complete',     'description' => 'Mark documents as completed'],
            ['name' => 'View All Documents',      'slug' => 'documents.view-all',     'description' => 'View any document regardless of department'],
            ['name' => 'View Department Docs',    'slug' => 'documents.view-dept',    'description' => 'View documents scoped to own department'],
            ['name' => 'Report Issues',           'slug' => 'documents.report-issue', 'description' => 'Report issues on any document'],
            ['name' => 'Reject Documents',        'slug' => 'documents.reject',       'description' => 'Reject documents and return to sender'],
            ['name' => 'Cancel Documents',        'slug' => 'documents.cancel',       'description' => 'Cancel document workflows'],
            ['name' => 'Manage Users',            'slug' => 'users.manage',           'description' => 'Create, update, and toggle user accounts'],
        ];

        $insertedIds = [];
        foreach ($permissions as $perm) {
            $id = DB::table('permissions')->updateOrInsert(
                ['slug' => $perm['slug']],
                $perm
            );
            $insertedIds[$perm['slug']] = DB::table('permissions')
                ->where('slug', $perm['slug'])
                ->value('id');
        }

        $adminSlugs = [
            'documents.create', 'documents.receive', 'documents.complete',
            'documents.view-all', 'documents.report-issue',
            'documents.reject', 'documents.cancel', 'users.manage',
        ];

        $userSlugs = [
            'documents.create', 'documents.receive', 'documents.complete',
            'documents.view-dept', 'documents.report-issue',
            'documents.reject', 'documents.cancel',
        ];

        $auditorSlugs = [
            'documents.view-all', 'documents.report-issue',
        ];

        $rolePermissions = [];
        foreach ($adminSlugs as $slug) {
            $rolePermissions[] = ['role_id' => 1, 'permission_id' => $insertedIds[$slug]];
        }
        foreach ($userSlugs as $slug) {
            $rolePermissions[] = ['role_id' => 2, 'permission_id' => $insertedIds[$slug]];
        }
        foreach ($auditorSlugs as $slug) {
            $rolePermissions[] = ['role_id' => 3, 'permission_id' => $insertedIds[$slug]];
        }

        DB::table('role_permissions')->upsert(
            $rolePermissions,
            ['role_id', 'permission_id']
        );

        $this->command->info('Seeded ' . count($permissions) . ' permissions and bound to roles.');
    }
}
