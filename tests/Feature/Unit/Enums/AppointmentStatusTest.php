<?php

use App\Enums\AppointmentStatus;

it('has all required values', function () {
    expect(AppointmentStatus::cases())->toHaveCount(5)
        ->and(AppointmentStatus::Scheduled)->toBeInstanceOf(AppointmentStatus::class)
        ->and(AppointmentStatus::InProgress)->toBeInstanceOf(AppointmentStatus::class)
        ->and(AppointmentStatus::Completed)->toBeInstanceOf(AppointmentStatus::class)
        ->and(AppointmentStatus::Cancelled)->toBeInstanceOf(AppointmentStatus::class)
        ->and(AppointmentStatus::NoShow)->toBeInstanceOf(AppointmentStatus::class);
});

it('has correct string values', function () {
    expect(AppointmentStatus::Scheduled->value)->toBe('scheduled')
        ->and(AppointmentStatus::InProgress->value)->toBe('in_progress')
        ->and(AppointmentStatus::Completed->value)->toBe('completed')
        ->and(AppointmentStatus::Cancelled->value)->toBe('cancelled')
        ->and(AppointmentStatus::NoShow->value)->toBe('no_show');
});

it('can be created from value', function (string $value, AppointmentStatus $expected) {
    expect(AppointmentStatus::from($value))->toBe($expected);
})->with([
    ['scheduled', AppointmentStatus::Scheduled],
    ['in_progress', AppointmentStatus::InProgress],
    ['completed', AppointmentStatus::Completed],
    ['cancelled', AppointmentStatus::Cancelled],
    ['no_show', AppointmentStatus::NoShow],
]);

it('throws exception for invalid value', function () {
    expect(fn () => AppointmentStatus::from('invalid'))
        ->toThrow(ValueError::class);
});

it('can check if status is cancellable', function () {
    expect(AppointmentStatus::Scheduled->isCancellable())->toBeTrue()
        ->and(AppointmentStatus::InProgress->isCancellable())->toBeFalse()
        ->and(AppointmentStatus::Completed->isCancellable())->toBeFalse()
        ->and(AppointmentStatus::Cancelled->isCancellable())->toBeFalse()
        ->and(AppointmentStatus::NoShow->isCancellable())->toBeFalse();
});

it('can check if status is active', function () {
    expect(AppointmentStatus::Scheduled->isActive())->toBeTrue()
        ->and(AppointmentStatus::InProgress->isActive())->toBeTrue()
        ->and(AppointmentStatus::Completed->isActive())->toBeFalse()
        ->and(AppointmentStatus::Cancelled->isActive())->toBeFalse()
        ->and(AppointmentStatus::NoShow->isActive())->toBeFalse();
});
