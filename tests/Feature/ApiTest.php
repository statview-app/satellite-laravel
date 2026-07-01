<?php

use Illuminate\Support\Facades\Config;

it('can expose about information via a route', function () {
    $token = '5816cc49-5722-48a7-8f33-53d2f69943c8';

    Config::set('statview.api_key', $token);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
        ->get('/statview/satellite/about');

    $response->assertSuccessful();

    expect($response->json())
        ->toHaveKeys(['data'])
        ->and($response->json()['data'])
        ->toHaveKeys(['environment', 'cache', 'drivers']);
});

it('can expose composer packages via a route', function () {
    $token = '5816cc49-5722-48a7-8f33-53d2f69943c8';

    Config::set('statview.api_key', $token);

    $lockPath = base_path('composer.lock');
    $jsonPath = base_path('composer.json');
    $lockBackup = is_file($lockPath) ? file_get_contents($lockPath) : null;
    $jsonBackup = is_file($jsonPath) ? file_get_contents($jsonPath) : null;

    file_put_contents($lockPath, json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v11.0.0'],
            ['name' => 'nesbot/carbon', 'version' => '3.0.0'],
            // Only in the lock — a transitive subdependency, not in composer.json.
            ['name' => 'psr/log', 'version' => '3.0.0'],
        ],
        'packages-dev' => [
            ['name' => 'pestphp/pest', 'version' => 'v3.0.0'],
        ],
    ]));

    // Authored order deliberately differs from the lockfile's alphabetical
    // order so we can prove `position` follows composer.json.
    file_put_contents($jsonPath, json_encode([
        'require' => [
            'nesbot/carbon' => '^3.0',
            'laravel/framework' => '^11.0',
        ],
        'require-dev' => [
            'pestphp/pest' => '^3.0',
        ],
    ]));

    try {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->get('/statview/satellite/packages');

        $response->assertSuccessful();

        expect($response->json('data'))
            ->toHaveCount(4)
            // Ordered by composer.json: carbon (0), framework (1), pest (2),
            // then the lock-only subdependency after the direct ones.
            ->and($response->json('data.0'))
            ->toMatchArray(['name' => 'nesbot/carbon', 'position' => 0, 'direct' => true])
            ->and($response->json('data.1'))
            ->toMatchArray(['name' => 'laravel/framework', 'position' => 1, 'dev' => false, 'direct' => true])
            ->and($response->json('data.2'))
            ->toMatchArray(['name' => 'pestphp/pest', 'position' => 2, 'dev' => true, 'direct' => true])
            ->and($response->json('data.3'))
            ->toMatchArray(['name' => 'psr/log', 'position' => 3, 'direct' => false]);
    } finally {
        $lockBackup !== null ? file_put_contents($lockPath, $lockBackup) : @unlink($lockPath);
        $jsonBackup !== null ? file_put_contents($jsonPath, $jsonBackup) : @unlink($jsonPath);
    }
});

it('can authenticate a request', function () {
    $token = '5816cc49-5722-48a7-8f33-53d2f69943c8';

    Config::set('statview.api_key', $token);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer b8d8ef9e-ae96-443b-957a-3d2939e904ae',
        ])
        ->get('/statview/satellite/about');

    $response->assertStatus(403);
});
