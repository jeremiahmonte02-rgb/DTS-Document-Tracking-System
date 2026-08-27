<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('received', 'pending_transfer', 'in_transit', 'rejected', 'cancelled', 'completed', 'returned_for_correction') NOT NULL DEFAULT 'pending_transfer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('received', 'pending_transfer', 'in_transit', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending_transfer'");
    }
};
