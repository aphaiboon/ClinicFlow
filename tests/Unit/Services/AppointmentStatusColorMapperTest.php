<?php

use App\Enums\AppointmentStatus;
use App\Services\AppointmentStatusColorMapper;

test('returns correct color for scheduled status', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getColor(AppointmentStatus::Scheduled))->toBe('#3b82f6');
});

test('returns correct color for in_progress status', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getColor(AppointmentStatus::InProgress))->toBe('#f97316');
});

test('returns correct color for completed status', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getColor(AppointmentStatus::Completed))->toBe('#10b981');
});

test('returns correct color for cancelled status', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getColor(AppointmentStatus::Cancelled))->toBe('#ef4444');
});

test('returns correct color for no_show status', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getColor(AppointmentStatus::NoShow))->toBe('#6b7280');
});

test('returns correct background color for all statuses', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getBackgroundColor(AppointmentStatus::Scheduled))->toBe('#dbeafe')
        ->and($mapper->getBackgroundColor(AppointmentStatus::InProgress))->toBe('#fed7aa')
        ->and($mapper->getBackgroundColor(AppointmentStatus::Completed))->toBe('#d1fae5')
        ->and($mapper->getBackgroundColor(AppointmentStatus::Cancelled))->toBe('#fee2e2')
        ->and($mapper->getBackgroundColor(AppointmentStatus::NoShow))->toBe('#f3f4f6');
});

test('returns correct text color for all statuses', function () {
    $mapper = new AppointmentStatusColorMapper;

    expect($mapper->getTextColor(AppointmentStatus::Scheduled))->toBe('#1e40af')
        ->and($mapper->getTextColor(AppointmentStatus::InProgress))->toBe('#c2410c')
        ->and($mapper->getTextColor(AppointmentStatus::Completed))->toBe('#065f46')
        ->and($mapper->getTextColor(AppointmentStatus::Cancelled))->toBe('#991b1b')
        ->and($mapper->getTextColor(AppointmentStatus::NoShow))->toBe('#374151');
});
