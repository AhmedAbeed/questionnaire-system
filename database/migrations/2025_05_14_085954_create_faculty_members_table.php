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
        Schema::create('faculty_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('national_id', 20)->unique();
            $table->string('academic_email')->unique(); 
            $table->string('personal_email')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');              
            $table->string('position')->nullable(); 
            $table->timestamps();                   
            $table->softDeletes();                  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_members');
    }
};
