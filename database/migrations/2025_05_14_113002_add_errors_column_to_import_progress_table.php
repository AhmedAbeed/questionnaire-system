<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddErrorsColumnToImportProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('import_progress', function (Blueprint $table) {
            $table->json('errors')->nullable()->after('error_message');
            
            $table->unsignedBigInteger('user_id')->nullable()->after('errors');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamp('completed_at')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('import_progress', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['errors', 'user_id', 'completed_at']);
        });
    }
}