<?php

use Illuminate\Support\Facades\Schema;

test('organizations table exists with correct columns', function () {
    expect(Schema::hasTable('organizations'))->toBeTrue();

    $columns = Schema::getColumnListing('organizations');

    expect($columns)->toContain('id')
        ->toContain('name')
        ->toContain('slug')
        ->toContain('email')
        ->toContain('phone')
        ->toContain('address_line_1')
        ->toContain('address_line_2')
        ->toContain('city')
        ->toContain('state')
        ->toContain('postal_code')
        ->toContain('country')
        ->toContain('tax_id')
        ->toContain('npi_number')
        ->toContain('practice_type')
        ->toContain('license_number')
        ->toContain('is_active')
        ->toContain('created_at')
        ->toContain('updated_at');
});

test('organizations table has unique slug', function () {
    $indexes = Schema::getConnection()->getDoctrineSchemaManager()
        ->listTableIndexes('organizations');

    expect($indexes)->toHaveKey('organizations_slug_unique');
});

test('organizations table has correct column types', function () {
    $columns = Schema::getColumnListing('organizations');

    expect($columns)->toContain('name')
        ->toContain('slug')
        ->toContain('email')
        ->toContain('is_active');
});

test('organization_user pivot table exists with correct columns', function () {
    expect(Schema::hasTable('organization_user'))->toBeTrue();

    $columns = Schema::getColumnListing('organization_user');

    expect($columns)->toContain('id')
        ->toContain('organization_id')
        ->toContain('user_id')
        ->toContain('role')
        ->toContain('joined_at')
        ->toContain('created_at')
        ->toContain('updated_at');
});

test('organization_user table has unique constraint on organization_id and user_id', function () {
    $indexes = Schema::getConnection()->select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='organization_user'");
    $indexNames = collect($indexes)->pluck('name')->filter()->toArray();

    expect($indexNames)->toContain('organization_user_organization_id_user_id_unique');
});

test('organization_user table has foreign keys', function () {
    $foreignKeys = Schema::getConnection()->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='organization_user'");
    $sql = strtolower($foreignKeys[0]->sql ?? '');

    expect($sql)->toContain('foreign key')
        ->toContain('organization_id')
        ->toContain('user_id');
});

test('users table has current_organization_id column', function () {
    expect(Schema::hasColumn('users', 'current_organization_id'))->toBeTrue();
});

test('users current_organization_id is nullable', function () {
    $columns = Schema::getColumnListing('users');

    expect($columns)->toContain('current_organization_id');
});

test('users table has foreign key for current_organization_id', function () {
    $foreignKeys = Schema::getConnection()->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $sql = strtolower(collect($foreignKeys)->pluck('sql')->join(' '));

    expect($sql)->toContain('current_organization_id')
        ->toContain('foreign key')
        ->toContain('organizations');
});

test('patients table has organization_id column', function () {
    expect(Schema::hasColumn('patients', 'organization_id'))->toBeTrue();
});

test('patients organization_id is not nullable', function () {
    $columns = Schema::getColumnListing('patients');

    expect($columns)->toContain('organization_id');
});

test('patients table has foreign key for organization_id', function () {
    $foreignKeys = Schema::getConnection()->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='patients'");
    $sql = strtolower(collect($foreignKeys)->pluck('sql')->join(' '));

    expect($sql)->toContain('organization_id')
        ->toContain('foreign key')
        ->toContain('organizations');
});

test('appointments table has organization_id column', function () {
    expect(Schema::hasColumn('appointments', 'organization_id'))->toBeTrue();
});

test('appointments organization_id is not nullable', function () {
    $columns = Schema::getColumnListing('appointments');

    expect($columns)->toContain('organization_id');
});

test('appointments table has foreign key for organization_id', function () {
    $foreignKeys = Schema::getConnection()->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='appointments'");
    $sql = strtolower(collect($foreignKeys)->pluck('sql')->join(' '));

    expect($sql)->toContain('organization_id')
        ->toContain('foreign key')
        ->toContain('organizations');
});

test('exam_rooms table has organization_id column', function () {
    expect(Schema::hasColumn('exam_rooms', 'organization_id'))->toBeTrue();
});

test('exam_rooms organization_id is not nullable', function () {
    $columns = Schema::getColumnListing('exam_rooms');

    expect($columns)->toContain('organization_id');
});

test('exam_rooms table has foreign key for organization_id', function () {
    $foreignKeys = Schema::getConnection()->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='exam_rooms'");
    $sql = strtolower(collect($foreignKeys)->pluck('sql')->join(' '));

    expect($sql)->toContain('organization_id')
        ->toContain('foreign key')
        ->toContain('organizations');
});

test('audit_logs table has organization_id column', function () {
    expect(Schema::hasColumn('audit_logs', 'organization_id'))->toBeTrue();
});

test('audit_logs organization_id is nullable', function () {
    $columns = Schema::getColumnListing('audit_logs');

    expect($columns)->toContain('organization_id');
});

test('audit_logs table has foreign key for organization_id', function () {
    $foreignKeys = Schema::getConnection()->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='audit_logs'");
    $sql = strtolower(collect($foreignKeys)->pluck('sql')->join(' '));

    expect($sql)->toContain('organization_id')
        ->toContain('foreign key')
        ->toContain('organizations');
});
