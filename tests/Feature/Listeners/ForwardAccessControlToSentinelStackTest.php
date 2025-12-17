<?php

use App\Listeners\ForwardAccessControlToSentinelStack;
use App\Models\User;
use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->envelopeBuilder = mock(EventEnvelopeBuilder::class);
    $this->client = mock(SentinelStackClientInterface::class);
    $this->listener = new ForwardAccessControlToSentinelStack($this->client, $this->envelopeBuilder);

    $request = Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);
});

it('maps Login event to access_control with correct event_subtype', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $event = new Login('web', $user, false);

    $expectedEnvelope = [
        'event_type' => 'access_control',
        'payload' => [
            'event_subtype' => 'user.login',
            'user_id' => $user->id,
            'user_email' => $user->email,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', \Mockery::on(function ($payload) use ($user) {
            return $payload['event_subtype'] === 'user.login'
                && $payload['user_id'] === $user->id
                && $payload['user_email'] === $user->email
                && isset($payload['ip_address'])
                && isset($payload['user_agent']);
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps Logout event to access_control with correct event_subtype', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $event = new Logout('web', $user);

    $expectedEnvelope = [
        'event_type' => 'access_control',
        'payload' => [
            'event_subtype' => 'user.logout',
            'user_id' => $user->id,
            'user_email' => $user->email,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', \Mockery::on(function ($payload) use ($user) {
            return $payload['event_subtype'] === 'user.logout'
                && $payload['user_id'] === $user->id
                && $payload['user_email'] === $user->email
                && isset($payload['ip_address'])
                && isset($payload['user_agent']);
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps Failed event to access_control with correct event_subtype', function () {
    $event = new Failed('web', null, ['email' => 'test@example.com', 'password' => 'secret123']);

    $expectedEnvelope = [
        'event_type' => 'access_control',
        'payload' => [
            'event_subtype' => 'user.login_failed',
            'attempted_email' => 'test@example.com',
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', \Mockery::on(function ($payload) {
            return $payload['event_subtype'] === 'user.login_failed'
                && $payload['attempted_email'] === 'test@example.com'
                && !isset($payload['password'])
                && !isset($payload['credentials'])
                && isset($payload['ip_address'])
                && isset($payload['user_agent'])
                && isset($payload['reason']);
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('ensures password is never included in Failed event payload', function () {
    $event = new Failed('web', null, ['email' => 'test@example.com', 'password' => 'super-secret-password']);

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', \Mockery::on(function ($payload) {
            return !isset($payload['password'])
                && !isset($payload['credentials']);
        }))
        ->andReturn(['event_type' => 'access_control', 'payload' => []]);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->andReturn(true);

    $this->listener->handle($event);
});

it('handles Failed event without email in credentials', function () {
    $event = new Failed('web', null, ['username' => 'testuser', 'password' => 'secret123']);

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', \Mockery::on(function ($payload) {
            return $payload['event_subtype'] === 'user.login_failed'
                && (!isset($payload['attempted_email']) || $payload['attempted_email'] === null)
                && !isset($payload['password']);
        }))
        ->andReturn(['event_type' => 'access_control', 'payload' => []]);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->andReturn(true);

    $this->listener->handle($event);
});

it('includes login_method in Login event payload', function () {
    $user = User::factory()->create();
    $event = new Login('web', $user, false);

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', \Mockery::on(function ($payload) {
            return isset($payload['login_method'])
                && $payload['login_method'] === 'password';
        }))
        ->andReturn(['event_type' => 'access_control', 'payload' => []]);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->andReturn(true);

    $this->listener->handle($event);
});

it('uses envelope builder to wrap payloads', function () {
    $user = User::factory()->create();
    $event = new Login('web', $user, false);

    $expectedPayload = [
        'event_subtype' => 'user.login',
        'user_id' => $user->id,
        'user_email' => $user->email,
        'login_method' => 'password',
        'ip_address' => '127.0.0.1',
        'user_agent' => null,
    ];
    $expectedEnvelope = ['event_type' => 'access_control', 'payload' => $expectedPayload];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('access_control', $expectedPayload)
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

