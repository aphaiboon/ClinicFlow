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
        Schema::table('organizations', function (Blueprint $table) {
            $table->time('operating_hours_start')->default('08:00')->after('is_active');
            $table->time('operating_hours_end')->default('18:00')->after('operating_hours_start');
            $table->integer('default_time_slot_interval')->default(15)->after('operating_hours_end');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'operating_hours_start',
                'operating_hours_end',
                'default_time_slot_interval',
            ]);
        });
    }
};
