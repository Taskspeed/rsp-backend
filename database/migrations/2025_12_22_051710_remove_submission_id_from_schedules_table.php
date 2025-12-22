<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {

            // 1️⃣ drop foreign key first
            $table->dropForeign(['submission_id']);

            // 2️⃣ then drop column
            $table->dropColumn('submission_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {

            // restore column
            $table->foreignId('submission_id')
                ->constrained('submission')
                ->onDelete('cascade');
        });
    }
};
