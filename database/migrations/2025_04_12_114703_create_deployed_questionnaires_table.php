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
        Schema::create('deployed_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('questionnaire_templates')->onDelete('cascade');
            $table->string('name', 100);
            $table->foreignId('target_type_id')->constrained('questionnaire_target_types')->onDelete('cascade');
            $table->dateTime('open_date');
            $table->dateTime('close_date');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployed_questionnaires');
    }
};
