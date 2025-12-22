<?php

// database/migrations/xxxx_xx_xx_xxxxxx_modify_question_id_foreign_on_question_responses.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });

        Schema::table('question_responses', function (Blueprint $table) {
            $table->foreign('question_id')
                  ->references('id')
                  ->on('deployed_questions')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });

        Schema::table('question_responses', function (Blueprint $table) {
            $table->foreign('question_id')
                  ->references('id')
                  ->on('deployed_questions')
                  ->onDelete('cascade');
        });
    }
};
