<?php

use App\Http\Middleware\CaptureRequestContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

it('generates and stores request_id', function () {
    $request = Request::create('/test', 'GET');
    $middleware = new CaptureRequestContext();

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->get('request_id'))
        ->toBeString()
        ->not->toBeEmpty();
});

it('generates and stores trace_id', function () {
    $request = Request::create('/test', 'GET');
    $middleware = new CaptureRequestContext();

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->get('trace_id'))
        ->toBeString()
        ->not->toBeEmpty();
});

it('captures session_id from Laravel session', function () {
    Session::start();
    $sessionId = Session::getId();

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(Session::driver());

    $middleware = new CaptureRequestContext();

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->get('session_id'))->toBe($sessionId);
});

it('stores context in request attributes', function () {
    Session::start();

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(Session::driver());

    $middleware = new CaptureRequestContext();

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->has('request_id'))->toBeTrue()
        ->and($request->attributes->has('trace_id'))->toBeTrue()
        ->and($request->attributes->has('session_id'))->toBeTrue();
});

it('handles requests without session gracefully', function () {
    $request = Request::create('/test', 'GET');

    $middleware = new CaptureRequestContext();

    $response =     $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->get('request_id'))->not->toBeEmpty()
        ->and($request->attributes->get('trace_id'))->not->toBeEmpty()
        ->and($request->attributes->get('session_id'))->toBeNull();
});

it('reuses existing request_id if present', function () {
    $request = Request::create('/test', 'GET');
    $existingRequestId = 'existing-req-id';
    $request->attributes->set('request_id', $existingRequestId);

    $middleware = new CaptureRequestContext();

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->get('request_id'))->toBe($existingRequestId);
});

it('reuses existing trace_id if present', function () {
    $request = Request::create('/test', 'GET');
    $existingTraceId = 'existing-trace-id';
    $request->attributes->set('trace_id', $existingTraceId);

    $middleware = new CaptureRequestContext();

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($request->attributes->get('trace_id'))->toBe($existingTraceId);
});

it('generates different request_ids for different requests', function () {
    $request1 = Request::create('/test1', 'GET');
    $request2 = Request::create('/test2', 'GET');

    $middleware = new CaptureRequestContext();

    $middleware->handle($request1, fn ($req) => response('OK'));
    $middleware->handle($request2, fn ($req) => response('OK'));

    expect($request1->attributes->get('request_id'))
        ->not->toBe($request2->attributes->get('request_id'));
});

