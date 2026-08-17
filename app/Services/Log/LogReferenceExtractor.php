<?php

namespace App\Services\Log;

class LogReferenceExtractor
{
    /**
     * @param  mixed  $request  JSON string, array, or null
     * @return array{value:?string,invalid_json:bool}
     */
    public function extract(mixed $request, ?string $searchField): array
    {
        if ($searchField === null || $searchField === '') {
            return ['value' => null, 'invalid_json' => false];
        }

        $decoded = $this->decode($request);
        if ($decoded['invalid_json']) {
            return ['value' => null, 'invalid_json' => true];
        }

        $value = $this->getNestedValue($decoded['data'], $searchField);
        if ($value === null || $value === '') {
            return ['value' => null, 'invalid_json' => false];
        }

        if (is_scalar($value)) {
            return ['value' => mb_substr((string) $value, 0, 500), 'invalid_json' => false];
        }

        return ['value' => mb_substr(json_encode($value) ?: '', 0, 500), 'invalid_json' => false];
    }

    /**
     * @return array{data:?array,invalid_json:bool}
     */
    public function decode(mixed $request): array
    {
        if ($request === null || $request === '') {
            return ['data' => null, 'invalid_json' => false];
        }

        if (is_array($request)) {
            return ['data' => $request, 'invalid_json' => false];
        }

        if (! is_string($request)) {
            return ['data' => null, 'invalid_json' => true];
        }

        $trim = trim($request);
        if ($trim === '') {
            return ['data' => null, 'invalid_json' => false];
        }

        try {
            $decoded = json_decode($trim, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['data' => null, 'invalid_json' => true];
        }

        return [
            'data' => is_array($decoded) ? $decoded : null,
            'invalid_json' => ! is_array($decoded),
        ];
    }

    public function getNestedValue(?array $data, string $path): mixed
    {
        if ($data === null || $path === '') {
            return null;
        }

        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
