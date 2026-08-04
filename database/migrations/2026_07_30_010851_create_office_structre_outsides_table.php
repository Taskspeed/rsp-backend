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
        Schema::create('office_structre_outsides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lib_office_id')->constrained('lib_offices')->onDelete('cascade');
            $table->string('office')->nullable();
            $table->string('office2')->nullable();
            $table->string('group')->nullable();
            $table->string('division')->nullable();
            $table->string('section')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_structre_outsides');
    }
};
