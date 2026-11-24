<?php

namespace App\Services\Generator;

final class ErrorCopy
{
    private const SERVICE_UNAVAILABLE = 'Test generation failed because the service is unavailable.';

    private const GENERIC_FAILURE = 'Failed to generate the script. Try again or review your scenario details.';

    private const AUTH_FAILED = 'Login failed. Check your credentials or Login URL.';

    /**
     * Machine-facing service error codes mapped to user-facing copy.
     *
     * @var array<string, string>
     */
    private const MAP = [
        'invalid_input' => 'This field is required.',
        'missing_field' => 'This field is required.',
        'invalid_url' => 'Please enter a valid URL.',
        'auth_failed' => self::AUTH_FAILED,
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
            return 'The URL could not be reached.';
        }

        return self::MAP[$code] ?? self::SERVICE_UNAVAILABLE;
    }
}