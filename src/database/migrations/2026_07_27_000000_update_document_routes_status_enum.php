<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE document_routes MODIFY COLUMN status ENUM('pending','next','current','received','skipped','rejected') NOT NULL DEFAULT 'pending'");

        DB::statement("UPDATE document_routes SET status = 'next' WHERE status = 'current'");

        DB::statement("
            UPDATE document_routes r
            JOIN documents d ON r.document_id = d.id
            SET r.status = 'current'
            WHERE r.status = 'received'
              AND d.status = 'in_transit'
              AND d.current_department_id = r.department_id
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE document_routes r
            JOIN documents d ON r.document_id = d.id
            SET r.status = 'received'
            WHERE r.status = 'current'
              AND d.status = 'in_transit'
              AND d.current_department_id = r.department_id
        ");

        DB::statement("UPDATE document_routes SET status = 'current' WHERE status = 'next'");

        DB::statement("ALTER TABLE document_routes MODIFY COLUMN status ENUM('pending','current','received','skipped','rejected') NOT NULL DEFAULT 'pending'");
    }
};
