<?php

namespace App\Services\Generator;

final class ErrorCopy
{
    private const SERVICE_UNAVAILABLE = 'Proses generate gagal, layanan tidak tersedia.';

    private const GENERIC_FAILURE = 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.';

    /**
     * Machine-facing service error codes mapped to frozen Indonesian copy.
     *
     * @var array<string, string>
     */
    private const MAP = [
        'invalid_input' => 'Field wajib diisi.',
        'missing_field' => 'Field wajib diisi.',
        'invalid_url' => 'Format URL tidak valid.',
        'llm_timeout' => self::SERVICE_UNAVAILABLE,
        'llm_unavailable' => self::SERVICE_UNAVAILABLE,
        'internal_error' => self::SERVICE_UNAVAILABLE,
        'invalid_response' => self::GENERIC_FAILURE,
        'unsupported_action' => self::GENERIC_FAILURE,
        'unsupported_locator' => self::GENERIC_FAILURE,
        'invalid_stage_order' => self::GENERIC_FAILURE,
        'empty_steps' => self::GENERIC_FAILURE,
        'empty_scenarios' => self::GENERIC_FAILURE,
        'missing_navigation' => self::GENERIC_FAILURE,
    ];

    public static function for(string $code, int $status): string
    {
        if (in_array($status, [401, 503], true)) {
            return self::SERVICE_UNAVAILABLE;
        }

        if ($code === 'invalid_url' && $status !== 422) {
            return 'URL tidak dapat diakses.';
        }

        return self::MAP[$code] ?? self::SERVICE_UNAVAILABLE;
    }
}
