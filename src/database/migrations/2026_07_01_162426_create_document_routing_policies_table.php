<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("document_routing_policies", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("document_type_id")->unique();
            $table->boolean("is_immutable")->default(false);
            $table->json("predefined_route")->nullable();
            $table->timestamps();
            $table->foreign("document_type_id")->references("id")->on("document_types")->onDelete("cascade");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("document_routing_policies");
    }
};
