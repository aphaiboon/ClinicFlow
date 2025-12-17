<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('medical_record_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender')->default(Gender::PreferNotToSay->value);
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country', 2)->default('US');
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('medical_record_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
