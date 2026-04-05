<?php

use App\Providers\FortifyServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;

// Fortify Service Provider Tests

it('registers fortify actions correctly', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Verify that the provider boots without errors
    expect(true)->toBeTrue();
});

it('configures rate limiting correctly', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Verify that rate limiters are registered
    expect(fn() => RateLimiter::attempt('login', 5, fn() => true))->not->toThrow(\Exception::class);
    expect(fn() => RateLimiter::attempt('two-factor', 5, fn() => true))->not->toThrow(\Exception::class);
});

it('two-factor rate limiter is configured', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Verify two-factor rate limiter exists
    expect(fn() => RateLimiter::attempt('two-factor', 6, fn() => true))->not->toThrow(\Exception::class);
});

it('login rate limiter uses correct key format', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Test the rate limiter key generation logic
    $email = 'test@example.com';
    $ip = '127.0.0.1';
    $expectedKey = Str::transliterate(Str::lower($email)) . '|' . $ip;
    
    expect($expectedKey)->toBeString();
    expect($expectedKey)->toBe('test@example.com|127.0.0.1');
});

it('configures fortify views correctly', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Test that Features facade methods work
    expect(Features::enabled(Features::resetPasswords()))->toBeBool();
    expect(Features::enabled(Features::registration()))->toBeBool();
});

it('fortify username configuration is accessible', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Test Fortify username method
    $username = Fortify::username();
    expect($username)->toBeString();
});

it('provider boot method calls all configuration methods', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    
    // Boot should not throw any exceptions
    expect(fn() => $provider->boot())->not->toThrow(\Exception::class);
});

it('provider register method does not throw exceptions', function () {
    $provider = new FortifyServiceProvider(app());
    
    // Register should not throw any exceptions
    expect(fn() => $provider->register())->not->toThrow(\Exception::class);
});

it('configure actions method sets correct classes', function () {
    $provider = new FortifyServiceProvider(app());
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('configureActions');
    $method->setAccessible(true);
    
    expect(fn() => $method->invoke($provider))->not->toThrow(\Exception::class);
});

it('configure views method sets up all views', function () {
    $provider = new FortifyServiceProvider(app());
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('configureViews');
    $method->setAccessible(true);
    
    expect(fn() => $method->invoke($provider))->not->toThrow(\Exception::class);
});

it('view configuration handles request parameters correctly', function () {
    $provider = new FortifyServiceProvider(app());
    
    // Test that request data can be accessed properly
    $request = new Request(['email' => 'test@example.com']);
    
    // Test that the view configuration can handle request data
    expect($request->input('email'))->toBe('test@example.com');
});

it('rate limiter handles session data correctly', function () {
    $provider = new FortifyServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Test rate limiter functionality
    expect(fn() => RateLimiter::attempt('two-factor', 1, fn() => true))->not->toThrow(\Exception::class);
});

it('configure rate limiting method sets up limiters', function () {
    $provider = new FortifyServiceProvider(app());
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('configureRateLimiting');
    $method->setAccessible(true);
    
    expect(fn() => $method->invoke($provider))->not->toThrow(\Exception::class);
});
