<?php

use App\Services\Generator\ErrorCopy;

test('every service error code resolves to user-facing copy', function (string $code, int $status, string $expected) {
    expect(ErrorCopy::for($code, $status))->toBe($expected);
})->with([
    ['invalid_input', 422, 'This field is required.'],
    ['missing_field', 502, 'This field is required.'],
    ['invalid_url', 422, 'Please enter a valid URL.'],
    ['invalid_url', 502, 'The URL could not be reached.'],
    ['auth_failed', 502, 'Login failed. Check your credentials or Login URL.'],
    ['llm_timeout', 502, 'Test generation failed because the service is unavailable.'],
    ['llm_unavailable', 502, 'Test generation failed because the service is unavailable.'],
    ['internal_error', 502, 'Test generation failed because the service is unavailable.'],
    ['invalid_response', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['unsupported_action', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['unsupported_locator', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['invalid_stage_order', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['empty_steps', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['empty_scenarios', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['missing_navigation', 502, 'Failed to generate the script. Try again or review your scenario details.'],
    ['unknown_code', 500, 'Test generation failed because the service is unavailable.'],
]);

test('auth-layer statuses never leak detail', function () {
    expect(ErrorCopy::for('', 401))->toBe('Test generation failed because the service is unavailable.')
        ->and(ErrorCopy::for('', 503))->toBe('Test generation failed because the service is unavailable.');
});
