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
        Schema::table('xService', function (Blueprint $table) {
            //
            $table->integer('itemNo')->nullable()->after('submission_id');
            $table->integer('Pages')->nullable()->after('itemNo');
            $table->integer('StructureID')->nullable()->after('Pages');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xService', function (Blueprint $table) {
            //
            $table->dropColumn(['itemNo','Pages','StructureID']);
        });
    }
};
