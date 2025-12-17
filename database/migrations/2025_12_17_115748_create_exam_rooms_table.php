<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->string('name');
            $table->integer('floor')->default(1);
            $table->json('equipment')->nullable();
            $table->integer('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_rooms');
    }
};
