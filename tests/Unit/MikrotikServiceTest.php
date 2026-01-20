<?php

use App\Services\MikrotikService;
use RouterOS\Config;

it('builds mikrotik config with socket options', function () {
    config()->set('hotspot.mikrotik.socket_options', [
        'bindto' => '0:0',
    ]);

    $service = new MikrotikService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('resolveConfig');
    $method->setAccessible(true);

    $config = $method->invoke($service);

    expect($config)->toBeInstanceOf(Config::class);
    expect($config->get('socket_options'))->toBe([
        'bindto' => '0:0',
    ]);
});
