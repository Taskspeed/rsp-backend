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
        Schema::table('nWorkExperience', function (Blueprint $table) {
            //
            $table->string('work_date_from', 10)->nullable()->change();
            $table->string('work_date_to', 10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nWorkExperience', function (Blueprint $table) {
            //
            $table->date('work_date_from')->nullable()->change();
            $table->date('work_date_to')->nullable()->change();
        });
    }
};
