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
        Schema::create('question_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('responses')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('deployed_questions')->onDelete('cascade');
            $table->text('text_response')->nullable();
            $table->foreignId('option_id')->nullable()->constrained('deployed_question_options')->onDelete('set null');
            $table->integer('numeric_value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_responses');
    }
};
