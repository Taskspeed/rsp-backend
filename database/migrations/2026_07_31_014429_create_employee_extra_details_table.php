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
         Schema::create('employee_extra_details', function (Blueprint $table) {
            $table->string('control_no')->primary();
            $table->string('rank')->nullable();
            $table->string('job_title')->nullable();
            $table->string('suffix')->nullable();
            $table->string('prefix')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_extra_details');
    }
};
