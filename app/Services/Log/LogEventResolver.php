<?php

namespace App\Services\Log;

class LogEventResolver
{
    /**
     * @return list<array{slug:string,label:string,request_config:?array}>
     */
    public function catalog(): array
    {
        $out = [];
        foreach (config('armos_log.events', []) as $slug => $cfg) {
            $searchField = $cfg['search_field'] ?? null;
            $out[] = [
                'slug' => $slug,
                'label' => $cfg['label'] ?? $slug,
                'request_config' => $searchField === null && ($cfg['search_label'] ?? null) === null
                    ? null
                    : [
                        'label' => $cfg['search_label'] ?? 'Cari Reference',
                        'placeholder' => $cfg['placeholder'] ?? '',
                        'search_field' => $searchField,
                    ],
            ];
        }

        return $out;
    }

    public function label(string $slug): string
    {
        return (string) (config("armos_log.events.{$slug}.label") ?? $slug);
    }

    public function searchField(string $slug): ?string
    {
        $field = config("armos_log.events.{$slug}.search_field");

        return $field !== null && $field !== '' ? (string) $field : null;
    }

    public function isValidSlug(string $slug): bool
    {
        return array_key_exists($slug, config('armos_log.events', []));
    }

    /**
     * Map production event text → monitoring event_slug.
     */
    public function resolve(?string $event): string
    {
        if ($event === null || trim($event) === '') {
            return 'other';
        }

        $e = trim($event);

        foreach (config('armos_log.events', []) as $slug => $cfg) {
            if ($slug === 'other') {
                continue;
            }
            $match = $cfg['match'] ?? null;
            if (! is_array($match)) {
                continue;
            }
            if ($this->matches($e, $match)) {
                return $slug;
            }
        }

        return 'other';
    }

    /**
     * @param  array<string, mixed>  $match
     */
    protected function matches(string $event, array $match): bool
    {
        if (isset($match['equals'])) {
            return $event === $match['equals'];
        }

        if (isset($match['starts_with'])) {
            return str_starts_with($event, (string) $match['starts_with']);
        }

        if (isset($match['contains'])) {
            $needles = (array) $match['contains'];
            foreach ($needles as $needle) {
                if (! str_contains($event, (string) $needle)) {
                    return false;
                }
            }
            foreach ((array) ($match['not_contains'] ?? []) as $needle) {
                if (str_contains($event, (string) $needle)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
