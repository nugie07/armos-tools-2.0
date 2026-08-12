<?php

namespace App\Support;

use RuntimeException;

class ArmosEnvironment
{
    public const SESSION_KEY = 'armos_environment';

    /**
     * Raw session value: production|preprod|null
     */
    public static function sessionValue(): ?string
    {
        $value = session(self::SESSION_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function hasSelection(): bool
    {
        return self::sessionValue() !== null;
    }

    /**
     * API / TMS env code: prod|preprod
     *
     * @throws RuntimeException
     */
    public static function apiEnv(): string
    {
        $session = self::sessionValue();
        if ($session === null) {
            throw new RuntimeException('PILIH ENVIRONMENT TERLEBIH DAHULU');
        }

        return $session === 'production' ? 'prod' : 'preprod';
    }

    public static function label(): ?string
    {
        $session = self::sessionValue();
        if ($session === null) {
            return null;
        }

        return $session === 'production' ? 'Production' : 'Pre Production';
    }
}
