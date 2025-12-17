<?php

use App\Enums\AppointmentType;

it('has all required values', function () {
    expect(AppointmentType::cases())->toHaveCount(4)
        ->and(AppointmentType::Routine)->toBeInstanceOf(AppointmentType::class)
        ->and(AppointmentType::FollowUp)->toBeInstanceOf(AppointmentType::class)
        ->and(AppointmentType::Consultation)->toBeInstanceOf(AppointmentType::class)
        ->and(AppointmentType::Emergency)->toBeInstanceOf(AppointmentType::class);
});

it('has correct string values', function () {
    expect(AppointmentType::Routine->value)->toBe('routine')
        ->and(AppointmentType::FollowUp->value)->toBe('follow_up')
        ->and(AppointmentType::Consultation->value)->toBe('consultation')
        ->and(AppointmentType::Emergency->value)->toBe('emergency');
});

it('can be created from value', function (string $value, AppointmentType $expected) {
    expect(AppointmentType::from($value))->toBe($expected);
})->with([
    ['routine', AppointmentType::Routine],
    ['follow_up', AppointmentType::FollowUp],
    ['consultation', AppointmentType::Consultation],
    ['emergency', AppointmentType::Emergency],
]);

it('throws exception for invalid value', function () {
    expect(fn () => AppointmentType::from('invalid'))
        ->toThrow(ValueError::class);
});
