<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_room_id')->nullable()->constrained()->nullOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->integer('duration_minutes');
            $table->string('appointment_type')->default(AppointmentType::Routine->value);
            $table->string('status')->default(AppointmentStatus::Scheduled->value);
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['appointment_date', 'appointment_time']);
            $table->index(['user_id', 'appointment_date']);
            $table->index(['exam_room_id', 'appointment_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
