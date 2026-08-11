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
        Schema::table('tempRegAppointmentReorgExt', function (Blueprint $table) {
            //
            $table->date('signing_date')->nullable()->after('deliberation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tempRegAppointmentReorgExt', function (Blueprint $table) {
            //

            $table->dropColumn('signing_date');
        });
    }
};
