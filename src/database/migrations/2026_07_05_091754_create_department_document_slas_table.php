<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_types', 'default_processing_time')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->integer('default_processing_time')->nullable()->after('description')->comment('Default processing time in minutes');
            });
        }

        Schema::create('department_document_slas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('document_type_id');
            $table->integer('processing_time_minutes')->comment('Processing time limit in minutes');
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');

            $table->unique(['department_id', 'document_type_id'], 'dept_doc_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_document_slas');

        if (Schema::hasColumn('document_types', 'default_processing_time')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->dropColumn('default_processing_time');
            });
        }
    }
};

