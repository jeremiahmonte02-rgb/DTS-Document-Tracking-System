<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->year('year_year');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->unique('year_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
