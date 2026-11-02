<?php

use App\Services\Generator\ScenarioParser;

test('criteria with scenario headings yield one name per heading', function () {
    $criteria = "Feature: Login\n\nSkenario: Login berhasil\nGiven saya di halaman login\n"
        ."When saya menekan masuk\nThen saya melihat dasbor\n\n"
        ."Scenario: Login gagal\nWhen password salah\nThen muncul pesan galat";

    expect(ScenarioParser::names($criteria, 'Login saya'))
        ->toBe(['Login berhasil', 'Login gagal']);
});

test('criteria without scenario headings yield one scenario', function () {
    expect(ScenarioParser::names('Given saya di beranda Then saya melihat judul', 'Checkout'))
        ->toBe(['Checkout']);
});

test('empty criteria yield one scenario', function () {
    expect(ScenarioParser::names('   ', 'Checkout'))->toBe(['Checkout']);
});
