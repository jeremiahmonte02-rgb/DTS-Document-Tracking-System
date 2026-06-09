<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. DEPARTMENTS
        Schema::create('departments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. ROLES
        Schema::create('roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. PERMISSIONS
        Schema::create('permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. ROLE PERMISSIONS (Pivot)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // 5. USERS
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->string('status')->default('active');
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        // 6. DOCUMENT TYPES
        Schema::create('document_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. DOCUMENTS
        Schema::create('documents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('id')->primary(); 
            $table->string('document_number')->unique();
            $table->string('title');
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->onDelete('set null');
            $table->foreignId('sender_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('current_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->enum('status', ['received', 'pending_transfer', 'in_transit', 'rejected', 'cancelled'])->default('pending_transfer');
            $table->text('description')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 8. DOCUMENT FILES
        Schema::create('document_files', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 9. DOCUMENT ROUTES
        Schema::create('document_routes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->unsignedInteger('route_order');
            $table->enum('status', ['pending', 'current', 'received', 'skipped', 'rejected'])->default('pending');
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'route_order']);
        });

        // 10. DOCUMENT EVENTS
        Schema::create('document_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('event_type');
            $table->string('event_label');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 11. DOCUMENT QR CODES
        Schema::create('document_qr_codes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->string('qr_token')->unique();
            $table->text('qr_payload');
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
        });

        // 12. DOCUMENT RECEIPTS
        Schema::create('document_receipts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('document_route_id')->constrained('document_routes')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('received_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('method', ['qr_scan', 'manual_entry', 'admin_mark_received']);
            $table->text('note')->nullable();
            $table->timestamp('received_at')->useCurrent();
        });

        // 13. DOCUMENT SCANS
        Schema::create('document_scans', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id')->nullable();
            $table->foreignId('scanned_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('scan_method')->default('qr_code');
            $table->enum('result', ['found', 'not_found', 'unauthorized', 'already_received', 'received']);
            $table->text('message')->nullable();
            $table->timestamp('scanned_at')->useCurrent();
        });

        // 14. DOCUMENT ISSUES
        Schema::create('document_issues', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('reported_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 15. DOCUMENT UPDATE REQUESTS
        Schema::create('document_update_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->text('message');
            $table->string('status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        // 16. NOTIFICATIONS
        Schema::create('notifications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('document_id')->nullable();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 17. DOCUMENT SHARES
        Schema::create('document_shares', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('shared_with_email')->nullable();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 18. DOCUMENT VIEWS
        Schema::create('document_views', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->timestamp('viewed_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });

        // 19. EXPORT LOGS
        Schema::create('export_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('export_type');
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
           // 20. WEB APP BROWSER SESSIONS
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_logs');
        Schema::dropIfExists('document_views');
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('document_update_requests');
        Schema::dropIfExists('document_issues');
        Schema::dropIfExists('document_scans');
        Schema::dropIfExists('document_receipts');
        Schema::dropIfExists('document_qr_codes');
        Schema::dropIfExists('document_events');
        Schema::dropIfExists('document_routes');
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('sessions');

        
    }
 
    
};