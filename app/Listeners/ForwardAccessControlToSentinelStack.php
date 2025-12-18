<?php

namespace App\Listeners;

use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Request;

class ForwardAccessControlToSentinelStack implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SentinelStackClientInterface $sentinelStackClient,
        protected EventEnvelopeBuilder $envelopeBuilder
    ) {}

    public function handle(Login|Logout|Failed $event): void
    {
        $payload = match (true) {
            $event instanceof Login => [
                'event_subtype' => 'user.login',
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
                'login_method' => 'password',
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ],
            $event instanceof Logout => [
                'event_subtype' => 'user.logout',
                'user_id' => $event->user?->id,
                'user_email' => $event->user?->email,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ],
            $event instanceof Failed => [
                'event_subtype' => 'user.login_failed',
                'attempted_email' => $event->credentials['email'] ?? null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'reason' => 'invalid_credentials',
            ],
        };

        $organizationId = auth()->user()?->current_organization_id;
        $envelope = $this->envelopeBuilder->buildEnvelope('access_control', $payload, $organizationId);

        $this->sentinelStackClient->ingestEvent($envelope);
    }
}
