<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('province')->nullable();
            $table->string('zip_code', 5)->nullable();
            $table->string('customs_id')->nullable();
            $table->string('customs_first_name')->nullable();
            $table->string('customs_last_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'province', 
                'zip_code', 
                'customs_id', 
                'customs_first_name', 
                'customs_last_name'
            ]);
        });
    }
};
