<?php

namespace Statview\Satellite\Http\Controllers;

use Illuminate\Support\Arr;

/**
 * Exposes the Composer packages installed in the host application, read from
 * the locked `composer.lock` so the reported versions match what is actually
 * deployed. Statview polls this to build a per-package version history and
 * surface the upgrade path over time.
 *
 * Each package also carries a `position`: the authored order from
 * `composer.json` (direct dependencies first, in the order they are declared),
 * with transitive packages appended afterwards. Statview uses this as the
 * default sort so the list mirrors how the project declares its dependencies.
 */
class PackagesController
{
    /**
     * @return array{data: list<array{name: string, version: string, dev: bool, direct: bool, position: int}>}
     */
    public function __invoke(): array
    {
        return [
            'data' => $this->packages(),
        ];
    }

    /**
     * @return list<array{name: string, version: string, dev: bool, direct: bool, position: int}>
     */
    private function packages(): array
    {
        $lock = $this->readJson(base_path('composer.lock'));

        if ($lock === null) {
            return [];
        }

        $order = $this->authoredOrder();
        $next = count($order);

        return collect()
            ->concat($this->normalize(Arr::get($lock, 'packages', []), dev: false))
            ->concat($this->normalize(Arr::get($lock, 'packages-dev', []), dev: true))
            ->sortBy('name')
            ->map(function (array $package) use ($order, &$next): array {
                $package['direct'] = array_key_exists($package['name'], $order);
                $package['position'] = $order[$package['name']] ?? $next++;

                return $package;
            })
            ->sortBy('position')
            ->values()
            ->all();
    }

    /**
     * Map each directly-required package name to its authored index in
     * `composer.json` (`require` first, then `require-dev`).
     *
     * @return array<string, int>
     */
    private function authoredOrder(): array
    {
        $composer = $this->readJson(base_path('composer.json'));

        if ($composer === null) {
            return [];
        }

        $names = array_merge(
            array_keys(Arr::get($composer, 'require', [])),
            array_keys(Arr::get($composer, 'require-dev', [])),
        );

        return array_flip(array_values($names));
    }

    /**
     * @param  array<int, array<string, mixed>>  $packages
     * @return list<array{name: string, version: string, dev: bool}>
     */
    private function normalize(array $packages, bool $dev): array
    {
        return collect($packages)
            ->filter(fn ($package): bool => filled(Arr::get($package, 'name')))
            ->map(fn ($package): array => [
                'name' => (string) $package['name'],
                'version' => (string) Arr::get($package, 'version', ''),
                'dev' => $dev,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }
}
