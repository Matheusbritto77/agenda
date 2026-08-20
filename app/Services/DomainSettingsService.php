<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DomainSettingsService
{
    private const STORAGE_PATH = 'domain-settings.json';

    /**
     * @return array{subdomain:string,custom_domain:?string}
     */
    public function current(): array
    {
        $settings = $this->defaults();

        if (! Storage::disk('local')->exists(self::STORAGE_PATH)) {
            return $settings;
        }

        $decoded = json_decode(Storage::disk('local')->get(self::STORAGE_PATH), true);

        if (! is_array($decoded)) {
            return $settings;
        }

        return array_merge($settings, array_filter(
            $decoded,
            static fn ($value): bool => $value !== null && $value !== ''
        ));
    }

    /**
     * @param  array{subdomain:string,custom_domain:?string}  $settings
     */
    public function save(array $settings): void
    {
        Storage::disk('local')->put(
            self::STORAGE_PATH,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function subdomainIsDuplicated(string $subdomain): bool
    {
        if (! Storage::disk('local')->exists(self::STORAGE_PATH)) {
            return false;
        }

        $current = $this->current();

        return isset($current['subdomain']) && $current['subdomain'] === $subdomain;
    }

    public function hostMatchesConfiguredDomain(string $host): bool
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            return false;
        }

        $settings = $this->current();
        $customDomain = strtolower(trim((string) ($settings['custom_domain'] ?? '')));

        if ($customDomain !== '' && $host === $customDomain) {
            return true;
        }

        $subdomain = strtolower(trim((string) ($settings['subdomain'] ?? '')));

        if ($subdomain === '') {
            return false;
        }

        return $host === "{$subdomain}.agendae.app"
            || str_starts_with($host, "{$subdomain}.");
    }

    /**
     * @return array{subdomain:string,custom_domain:?string}
     */
    private function defaults(): array
    {
        return [
            'subdomain' => 'minhaempresa',
            'custom_domain' => null,
        ];
    }
}
