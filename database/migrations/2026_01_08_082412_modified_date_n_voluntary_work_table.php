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
        Schema::table('nVoluntaryWork', function (Blueprint $table) {
            //
            $table->string('inclusive_date_from', 10)->nullable()->change();
            $table->string('inclusive_date_to', 10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nVoluntaryWork', function (Blueprint $table) {
            //

            $table->date('inclusive_date_from')->nullable()->change();
            $table->date('inclusive_date_to')->nullable()->change();
        });
    }
};
