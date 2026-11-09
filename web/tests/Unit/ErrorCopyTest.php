<?php

use App\Services\Generator\ErrorCopy;

test('every service error code resolves to indonesian copy', function (string $code, int $status, string $expected) {
    expect(ErrorCopy::for($code, $status))->toBe($expected);
})->with([
    ['invalid_input', 422, 'Field wajib diisi.'],
    ['missing_field', 502, 'Field wajib diisi.'],
    ['invalid_url', 422, 'Format URL tidak valid.'],
    ['invalid_url', 502, 'URL tidak dapat diakses.'],
    ['llm_timeout', 502, 'Proses generate gagal, layanan tidak tersedia.'],
    ['llm_unavailable', 502, 'Proses generate gagal, layanan tidak tersedia.'],
    ['internal_error', 502, 'Proses generate gagal, layanan tidak tersedia.'],
    ['invalid_response', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['unsupported_action', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['unsupported_locator', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['invalid_stage_order', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['empty_steps', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['empty_scenarios', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['missing_navigation', 502, 'Gagal menghasilkan script. Coba lagi atau periksa isian skenario Anda.'],
    ['unknown_code', 500, 'Proses generate gagal, layanan tidak tersedia.'],
]);

test('auth-layer statuses never leak detail', function () {
    expect(ErrorCopy::for('', 401))->toBe('Proses generate gagal, layanan tidak tersedia.')
        ->and(ErrorCopy::for('', 503))->toBe('Proses generate gagal, layanan tidak tersedia.');
});
