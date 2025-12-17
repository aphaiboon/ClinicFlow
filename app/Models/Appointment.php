<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'user_id',
        'exam_room_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'appointment_type',
        'status',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'string',
            'appointment_type' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'duration_minutes' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examRoom(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class);
    }

    public function scopeUpcoming($query): void
    {
        $query->where('appointment_date', '>=', now()->toDateString())
            ->where('status', AppointmentStatus::Scheduled);
    }

    public function scopeByStatus($query, AppointmentStatus $status): void
    {
        $query->where('status', $status);
    }

    public function scopeByDateRange($query, \Carbon\Carbon|\Illuminate\Support\Carbon $start, \Carbon\Carbon|\Illuminate\Support\Carbon $end): void
    {
        $query->whereBetween('appointment_date', [$start, $end]);
    }

    public function scopeByClinician($query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeByPatient($query, int $patientId): void
    {
        $query->where('patient_id', $patientId);
    }
}
