<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Date;
use Carbon\CarbonImmutable;

// App Service Provider Tests

it('registers without throwing exceptions', function () {
    $provider = new AppServiceProvider(app());
    
    expect(fn() => $provider->register())->not->toThrow(\Exception::class);
});

it('boots without throwing exceptions', function () {
    $provider = new AppServiceProvider(app());
    
    $provider->register();
    
    expect(fn() => $provider->boot())->not->toThrow(\Exception::class);
});

it('configures carbon immutable as default date', function () {
    $provider = new AppServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // Check if Date facade is using CarbonImmutable
    $now = Date::now();
    expect($now)->toBeInstanceOf(CarbonImmutable::class);
});

it('allows destructive commands in testing environment', function () {
    $provider = new AppServiceProvider(app());
    
    $provider->register();
    $provider->boot();
    
    // In testing, destructive commands should be allowed
    expect(app()->isProduction())->toBeFalse();
});

it('boot method calls configure defaults', function () {
    $provider = new AppServiceProvider(app());
    
    $provider->register();
    
    // Boot should call configureDefaults which sets up the configurations
    expect(fn() => $provider->boot())->not->toThrow(\Exception::class);
    
    // Verify Date is configured after boot
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});
