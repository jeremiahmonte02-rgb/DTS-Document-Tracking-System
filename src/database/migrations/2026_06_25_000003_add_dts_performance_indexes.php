<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix: add 'completed' to documents.status enum (required by completeDocument())
        Schema::table('documents', function (Blueprint $table) {
            $table->index('status');
            $table->index('sender_department_id');
            $table->index('current_department_id');
        });

        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('received', 'pending_transfer', 'in_transit', 'rejected', 'cancelled', 'completed') DEFAULT 'pending_transfer' NOT NULL");

        Schema::table('document_routes', function (Blueprint $table) {
            $table->index(['document_id', 'route_order']);
            $table->index('department_id');
            $table->index('status');
        });

        Schema::table('document_events', function (Blueprint $table) {
            $table->index('document_id');
            $table->index('event_type');
        });

        Schema::table('document_files', function (Blueprint $table) {
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['sender_department_id']);
            $table->dropIndex(['current_department_id']);
        });

        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('received', 'pending_transfer', 'in_transit', 'rejected', 'cancelled') DEFAULT 'pending_transfer' NOT NULL");

        Schema::table('document_routes', function (Blueprint $table) {
            $table->dropIndex(['document_id', 'route_order']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('document_events', function (Blueprint $table) {
            $table->dropIndex(['document_id']);
            $table->dropIndex(['event_type']);
        });

        Schema::table('document_files', function (Blueprint $table) {
            $table->dropIndex(['document_id']);
        });
    }
};
