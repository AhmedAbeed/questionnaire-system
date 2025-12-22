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
        Schema::create('deployed_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployed_question_id')->constrained('deployed_questions')->onDelete('cascade');
            $table->string('option_text', 255);
            $table->string('value', 50);
            $table->tinyInteger('order');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployed_question_options');
    }
};
